<?php

namespace App\Services;

use App\Exceptions\NfeEmissionException;
use App\Models\Nfe;
use App\Models\Destinatario;
use App\Models\NaturezaOperacao;
use App\Models\Cliente;
use App\Models\ConfiguracaoEmissor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Make;
use NFePHP\NFe\Tools;
use NFePHP\DA\NFe\Danfe;
use SoapFault;

final class NfeEmissionService
{
    /**
     * Renderiza uma prévia de DANFE para um rascunho sem transmitir nada.
     * O ambiente 2 é usado somente no XML temporário para que o DANFE
     * identifique corretamente o documento como SEM VALOR FISCAL.
     */
    public function renderDraftDanfe(Nfe $nfe): string
    {
        if ($nfe->status !== 'rascunho') {
            throw new NfeEmissionException(
                'A prévia DANFE está disponível apenas para notas pendentes.',
                422,
                'erro',
            );
        }

        $payload = $nfe->payload ?? [];
        $natureza = NaturezaOperacao::query()->findOrFail($payload['id_natureza_operacao'] ?? $nfe->id_natureza_operacao);
        $cliente = !empty($payload['id_cliente'])
            ? Cliente::query()->find($payload['id_cliente'])
            : null;
        $destinatario = $cliente ? null : (!empty($payload['id_destinatario'])
            ? Destinatario::query()->find($payload['id_destinatario'])
            : null);

        if (!$cliente && !$destinatario) {
            throw new NfeEmissionException('O destinatário do rascunho não foi encontrado.', 422, 'erro');
        }

        $payload = $this->applyCatalogRules($payload, $natureza, $cliente, $destinatario);
        $payload['numero'] = $nfe->numero;
        $payload['serie'] = $nfe->serie;
        $payload['informacoes_complementares'] = trim(
            (string) ($payload['informacoes_complementares'] ?? '')
            . "\nDOCUMENTO SEM VALOR FISCAL - PRÉVIA DE NF-e NÃO TRANSMITIDA À SEFAZ."
        );

        $xml = $this->buildXml($payload, $natureza, 2);
        // O DANFE exige um Id de 44 dígitos para desenhar o código de barras.
        // Esta chave é apenas técnica e local: a marca SEM VALOR FISCAL deixa
        // explícito que ela não foi autorizada e não pode ser consultada na SEFAZ.
        $xml = $this->addDraftIdentity($xml);

        return (new Danfe($xml))->render();
    }

    public function emit(array $payload, ?int $usuarioId = null, ?Nfe $existing = null): Nfe
    {
        $nfe = null;

        try {
            $natureza = NaturezaOperacao::query()->findOrFail($payload['id_natureza_operacao']);
            $cliente = !empty($payload['id_cliente']) ? Cliente::query()->findOrFail($payload['id_cliente']) : null;
            $destinatario = $cliente ? null : Destinatario::query()->findOrFail($payload['id_destinatario']);
            $payload = $this->applyCatalogRules($payload, $natureza, $cliente, $destinatario);
            $attributes = [
                'numero' => $payload['numero'], 'serie' => $payload['serie'] ?? 1,
                'status' => 'gerando', 'payload' => $payload, 'id_usuario' => $usuarioId,
                'id_cliente' => $cliente?->id, 'id_destinatario' => $destinatario?->id, 'id_natureza_operacao' => $natureza->id,
                'destinatario_documento' => preg_replace('/\D+/', '', (string) ($payload['destinatario']['cnpj'] ?? $payload['destinatario']['cpf'] ?? '')),
            ];
            $nfe = $existing ?: Nfe::create($attributes);
            if ($existing) {
                $nfe->update($attributes);
            }

            $xml = $this->buildXml($payload, $natureza);

            if ($this->simulationEnabled()) {
                try {
                    $danfePath = 'nfe/'.$nfe->id.'-simulada-danfe.pdf';
                    Storage::disk('local')->put($danfePath, (new Danfe($xml))->render());
                    $nfe->danfe_path = $danfePath;
                } catch (\Throwable $exception) {
                    Log::warning('Não foi possível gerar o DANFE da simulação.', [
                        'nfe_id' => $nfe->id,
                        'exception' => $exception,
                    ]);
                }

                $nfe->update([
                    'xml' => $xml,
                    'status' => 'simulada',
                    'xmotivo' => 'Simulação de homologação concluída. Nenhum documento foi transmitido à SEFAZ.',
                    'danfe_path' => $nfe->danfe_path,
                ]);

                return $nfe->fresh();
            }

            $signedXml = $this->tools()->signNFe($xml);
            $nfe->update(['xml' => $signedXml, 'status' => 'assinado', 'chave_acesso' => $this->accessKey($signedXml)]);

            // Cada emissão desta tela contém uma única NF-e. O envio deve ser
            // síncrono (indSinc=1); assíncrono com lote de uma nota provoca o
            // cStat 452: "Solicitada resposta assíncrona para Lote com somente
            // 1 (uma) NF-e".
            $response = $this->tools()->sefazEnviaLote(
                [$signedXml],
                str_pad((string) $nfe->id, 15, '0', STR_PAD_LEFT),
                1,
            );
            $std = (new Standardize())->toStd($response);
            $cstat = (int) ($std->cStat ?? 0);

            // No modo síncrono, cStat 104 é o retorno do lote processado e o
            // cStat definitivo (100 autorizado ou uma rejeição) fica em
            // protNFe.infProt.
            if ($cstat === 104 && isset($std->protNFe)) {
                $protocol = $std->protNFe->infProt ?? null;
                $protocolStatus = (int) ($protocol->cStat ?? 0);
                $protocolReason = (string) ($protocol->xMotivo ?? $std->xMotivo ?? '');

                // Uma rejeição síncrona também vem dentro de protNFe, mas não
                // pode passar por toAuthorize(), pois não existe protocolo
                // autorizador para anexar ao XML.
                if ($protocolStatus !== 100) {
                    $nfe->update([
                        'status' => 'rejeitada',
                        'cstat' => $protocolStatus,
                        'xmotivo' => $this->sefazReason($protocolStatus, $protocolReason),
                    ]);

                    return $nfe->fresh();
                }

                $authorizedXml = Complements::toAuthorize($signedXml, $response);
                $nfe->update([
                    'status' => $protocolStatus === 100 ? 'autorizada' : 'rejeitada',
                    'cstat' => $protocolStatus,
                    'xmotivo' => $protocolStatus === 100
                        ? $protocolReason
                        : $this->sefazReason($protocolStatus, $protocolReason),
                    'protocolo' => $protocol->nProt ?? null,
                    'xml' => $authorizedXml,
                ]);

                if ($protocolStatus === 100) {
                    $danfePath = 'nfe/'.$nfe->chave_acesso.'-danfe.pdf';
                    Storage::disk('local')->put($danfePath, (new Danfe($authorizedXml))->render());
                    $nfe->update(['danfe_path' => $danfePath]);
                }

                return $nfe->fresh();
            }

            if ($cstat !== 103) {
                throw new NfeEmissionException(
                    $this->sefazReason($cstat, $std->xMotivo ?? null),
                    422,
                    'rejeitada',
                    $cstat,
                );
            }
            $recibo = (string) ($std->infRec->nRec ?? '');
            if ($recibo === '') {
                throw new NfeEmissionException(
                    'A SEFAZ recebeu o lote, mas não retornou o recibo para consulta.',
                    503,
                    'erro',
                    $cstat,
                );
            }
            $nfe->update([
                'recibo' => $recibo,
                'status' => 'aguardando_retorno',
                'cstat' => $cstat,
                'xmotivo' => $std->xMotivo ?? null,
            ]);
            return $nfe->fresh();
        } catch (NfeEmissionException $e) {
            $nfe?->update([
                'status' => $e->nfeStatus,
                'cstat' => $e->cstat,
                'xmotivo' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Falha técnica na emissão da NF-e.', [
                'exception' => $e,
                'id_usuario' => $usuarioId,
                'id_empresa' => auth()->user()?->id_empresa,
                'nfe_id' => $nfe?->id,
            ]);
            $message = $this->friendlyIntegrationMessage($e);
            $nfe?->update(['status' => 'erro', 'xmotivo' => $message]);
            throw new NfeEmissionException($message, 503, 'erro', null, $e);
        }
    }

    private function simulationEnabled(): bool
    {
        $emissor = $this->currentEmissor();
        $ambiente = (int) ($emissor?->ambiente ?? config('nfe.ambiente'));

        return (bool) config('nfe.simulate')
            && (int) config('nfe.ambiente') === 2
            && $ambiente === 2;
    }

    private function currentEmissor(): ?ConfiguracaoEmissor
    {
        $empresaId = auth()->user()?->id_empresa;

        return $empresaId
            ? ConfiguracaoEmissor::query()->where('id_empresa', $empresaId)->first()
            : null;
    }

    public function consultReceipt(Nfe $nfe): Nfe
    {
        try {
            $response = $this->tools()->sefazConsultaRecibo($nfe->recibo);
            $std = (new Standardize())->toStd($response);
            $cstat = (int) ($std->cStat ?? 0);
            $motivo = (string) ($std->xMotivo ?? '');
            $nfe->update(['cstat' => $cstat, 'xmotivo' => $motivo ?: null]);

            if ($cstat === 104 && isset($std->protNFe)) {
                $protocol = $std->protNFe->infProt ?? null;
                $protocolStatus = (int) ($protocol->cStat ?? 0);
                $protocolReason = (string) ($protocol->xMotivo ?? $motivo);
                $authorizedXml = Complements::toAuthorize($nfe->xml, $response);
                $path = 'nfe/'.$nfe->chave_acesso.'-procNFe.xml';
                Storage::disk('local')->put($path, $authorizedXml);
                $nfe->update([
                    'status' => $protocolStatus === 100 ? 'autorizada' : 'rejeitada',
                    'cstat' => $protocolStatus,
                    'xmotivo' => $protocolStatus === 100 ? $protocolReason : $this->sefazReason($protocolStatus, $protocolReason),
                    'protocolo' => $protocol->nProt ?? null,
                    'xml' => $authorizedXml,
                ]);
                if ($protocolStatus === 100) {
                    $danfePath = 'nfe/'.$nfe->chave_acesso.'-danfe.pdf';
                    Storage::disk('local')->put($danfePath, (new Danfe($authorizedXml))->render());
                    $nfe->update(['danfe_path' => $danfePath]);
                }
            } elseif (in_array($cstat, [105, 106, 107], true)) {
                $nfe->update(['status' => 'aguardando_retorno']);
            } else {
                $reason = $this->sefazReason($cstat, $motivo);
                $nfe->update(['status' => 'rejeitada', 'xmotivo' => $reason]);
            }
            return $nfe->fresh();
        } catch (NfeEmissionException $e) {
            $nfe->update([
                'status' => $e->nfeStatus,
                'cstat' => $e->cstat,
                'xmotivo' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            $message = $this->friendlyIntegrationMessage($e);
            $nfe->update(['status' => 'erro', 'xmotivo' => $message]);
            throw new NfeEmissionException($message, 503, 'erro', null, $e);
        }
    }

    /**
     * Transmits the cancellation event (110111) to SEFAZ.
     *
     * A previous version only changed the local status, which made the
     * screen say "cancelada" while the invoice remained authorized at SEFAZ.
     */
    public function cancel(Nfe $nfe, string $justificativa): Nfe
    {
        $justificativa = trim($justificativa);

        if (mb_strlen($justificativa) < 15 || mb_strlen($justificativa) > 255) {
            throw new NfeEmissionException(
                'A justificativa do cancelamento deve conter entre 15 e 255 caracteres.',
                422,
                'autorizada',
                $nfe->cstat,
            );
        }

        if (in_array((int) $nfe->cstat, [135, 136, 155], true)) {
            throw new NfeEmissionException(
                'Esta NF-e já possui cancelamento autorizado pela SEFAZ.',
                422,
                'cancelada',
                (int) $nfe->cstat,
            );
        }

        if (!in_array((string) $nfe->status, ['autorizada', 'cancelada'], true)) {
            throw new NfeEmissionException(
                'Somente uma NF-e autorizada pode ser cancelada na SEFAZ.',
                422,
                (string) $nfe->status,
                $nfe->cstat,
            );
        }

        if (!$nfe->chave_acesso || !$nfe->protocolo) {
            throw new NfeEmissionException(
                'A NF-e não possui chave de acesso e protocolo de autorização para cancelamento.',
                422,
                (string) $nfe->status,
                $nfe->cstat,
            );
        }

        if (!$nfe->xml) {
            throw new NfeEmissionException(
                'O XML autorizado da NF-e não está disponível para auditoria.',
                422,
                (string) $nfe->status,
                $nfe->cstat,
            );
        }

        try {
            $response = $this->tools()->sefazCancela(
                (string) $nfe->chave_acesso,
                $justificativa,
                (string) $nfe->protocolo,
                now('America/Sao_Paulo'),
                str_pad((string) $nfe->id, 15, '0', STR_PAD_LEFT),
            );

            $std = (new Standardize())->toStd($response);
            $infEvento = $std->retEvento->infEvento ?? $std->infEvento ?? null;
            $cstat = (int) ($infEvento->cStat ?? $std->cStat ?? 0);
            $motivo = trim((string) ($infEvento->xMotivo ?? $std->xMotivo ?? ''));

            if (!in_array($cstat, [135, 136, 155], true)) {
                $reason = $this->sefazReason($cstat, $motivo);
                // A nota pode ter sido marcada como cancelada somente pela
                // tela antiga. Se a SEFAZ recusou o evento, ela continua
                // autorizada e pode ser corrigida/reprocessada.
                $nfe->update([
                    'status' => 'autorizada',
                    'cstat' => $cstat ?: $nfe->cstat,
                    'xmotivo' => $reason,
                    'data_cancelamento' => null,
                    'motivo_cancelamento' => null,
                ]);

                throw new NfeEmissionException($reason, 422, 'autorizada', $cstat ?: $nfe->cstat);
            }

            $nfe->update([
                'status' => 'cancelada',
                'cstat' => $cstat,
                'xmotivo' => $motivo ?: 'Cancelamento homologado pela SEFAZ.',
                'data_cancelamento' => now('America/Sao_Paulo'),
                'motivo_cancelamento' => $justificativa,
            ]);

            return $nfe->fresh();
        } catch (NfeEmissionException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Falha na transmissão do cancelamento da NF-e.', [
                'nfe_id' => $nfe->id,
                'chave_acesso' => $nfe->chave_acesso,
                'exception' => $e,
            ]);

            throw new NfeEmissionException(
                $this->friendlyIntegrationMessage($e),
                503,
                (string) $nfe->status,
                $nfe->cstat,
                $e,
            );
        }
    }

    private function tools(): Tools
    {
        $user = auth()->user();
        if (!$user?->id_empresa) {
            throw new NfeEmissionException('Não foi possível identificar a empresa da sessão.', 401, 'erro');
        }
        $emissor = ConfiguracaoEmissor::query()
            ->where('id_empresa', $user->id_empresa)
            ->first();
        if (!$emissor || (int) $emissor->id_empresa !== (int) $user->id_empresa) {
            throw new NfeEmissionException('Configure o emissor da empresa antes de emitir uma NF-e.', 422, 'erro');
        }
        if (!$emissor->certificado_base64) {
            throw new NfeEmissionException('Certificado A1 não configurado para esta empresa.', 422, 'erro');
        }
        $certificate = base64_decode($emissor->certificado_base64, true);
        if ($certificate === false || $certificate === '') {
            throw new NfeEmissionException('O certificado A1 salvo para esta empresa está corrompido.', 422, 'erro');
        }
        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'), 'tpAmb' => $emissor?->ambiente ?? config('nfe.ambiente'),
            'razaosocial' => $emissor?->razao_social ?? config('nfe.razao_social'), 'cnpj' => $emissor?->cnpj ?? config('nfe.cnpj'),
            'ie' => $emissor?->inscricao_estadual ?? config('nfe.ie'), 'siglaUF' => $emissor?->uf ?? config('nfe.uf'),
            'schemes' => config('nfe.schemes'), 'versao' => config('nfe.versao'),
            'aProxyConf' => ['proxyIp' => '', 'proxyPort' => '', 'proxyUser' => '', 'proxyPass' => ''],
        ];
        $password = (string) $emissor->certificado_senha;
        if ($password === '') {
            throw new NfeEmissionException('A senha do certificado A1 desta empresa não está configurada.', 422, 'erro');
        }
        try {
            $certificateObject = Certificate::readPfx($certificate, $password);
        } catch (\Throwable $e) {
            throw new NfeEmissionException(
                'Não foi possível abrir o certificado A1. Confira a senha e o arquivo enviado.',
                422,
                'erro',
                null,
                $e,
            );
        }
        if ($certificateObject->isExpired()) {
            throw new NfeEmissionException(
                'O certificado digital A1 desta empresa está vencido. Envie um certificado válido.',
                422,
                'erro',
            );
        }
        return new Tools(json_encode($config, JSON_THROW_ON_ERROR), $certificateObject);
    }

    private function buildXml(array $p, NaturezaOperacao $natureza, ?int $ambienteOverride = null): string
    {
        $make = new Make();
        $version = config('nfe.versao');
        $emissor = $this->currentEmissor();
        $ambiente = $ambienteOverride ?? ($emissor?->ambiente ?? config('nfe.ambiente'));
        if ((int) $ambiente === 1 && trim((string) ($emissor?->inscricao_estadual ?? '')) === '') {
            throw new NfeEmissionException(
                'Informe a Inscrição Estadual do emitente antes de emitir em produção.',
                422,
                'erro',
            );
        }
        $codigoMunicipio = $emissor?->codigo_municipio_ibge ?: $p['destinatario']['endereco']['cMun'];

        // A data/hora informada na emissão é a hora local do estabelecimento.
        // Sem o timezone explícito, o Carbon usa UTC no container e transforma
        // 21:08 de São Paulo em 21:08 UTC (três horas adiantado no XML).
        $timezone = 'America/Sao_Paulo';
        $agoraLocal = \Carbon\Carbon::now($timezone);
        $emissao = trim(
            ($p['data_emissao'] ?? $agoraLocal->format('Y-m-d')).' '.
            ($p['hora_emissao'] ?? $agoraLocal->format('H:i')),
        );
        $now = \Carbon\Carbon::parse($emissao, $timezone)->toIso8601String();

        $saida = null;
        if (!empty($p['data_saida'])) {
            $saidaTexto = trim(
                $p['data_saida'].' '.($p['hora_saida'] ?? $agoraLocal->format('H:i')),
            );
            $saida = \Carbon\Carbon::parse($saidaTexto, $timezone)->toIso8601String();
        }
        $tipoNota = ($natureza->tipo_movimento ?? 'Saída') === 'Entrada' ? 0 : 1;
        $finalidade = (int) ($p['finalidade'] ?? 1);
        $indFinal = !empty($p['consumidor_final']) ? 1 : 0;
        $indPres = (int) ($p['ind_pres'] ?? 9);
        $this->tag($make, 'taginfNFe', ['versao' => $version, 'Id' => null, 'pk_nItem' => '']);
        $this->tag($make, 'tagide', ['cUF' => config('nfe.cuf'), 'cNF' => random_int(10000000, 99999999), 'natOp' => $p['natureza_operacao'], 'mod' => 55, 'serie' => $p['serie'] ?? ($emissor?->serie_padrao ?? 1), 'nNF' => $p['numero'], 'dhEmi' => $now, 'dhSaiEnt' => $saida, 'tpNF' => $tipoNota, 'idDest' => 1, 'cMunFG' => $codigoMunicipio, 'tpImp' => 1, 'tpEmis' => 1, 'tpAmb' => $ambiente, 'finNFe' => $finalidade, 'indFinal' => $indFinal, 'indPres' => $indPres, 'procEmi' => 0, 'verProc' => '1.0']);
        // CRT=4 é o código oficial do MEI na NF-e. Não converter para CRT=1:
        // a SEFAZ compara este valor com o regime cadastrado do emitente.
        $crt = (int) ($emissor?->crt ?? config('nfe.crt'));
        $emitente = [
            'xNome' => $emissor?->razao_social ?? config('nfe.razao_social'),
            'CNPJ' => $emissor?->cnpj ?? config('nfe.cnpj'),
            'CRT' => $crt,
        ];
        $inscricaoEstadual = trim((string) ($emissor?->inscricao_estadual ?? config('nfe.ie', '')));
        if ($inscricaoEstadual !== '') {
            $emitente['IE'] = $inscricaoEstadual;
        }
        $this->tag($make, 'tagemit', $emitente);
        $this->tag($make, 'tagenderEmit', $this->emitterAddress($p));
        $d = $p['destinatario'];
        $this->tag($make, 'tagdest', ['xNome' => $d['nome'], 'CNPJ' => $d['cnpj'] ?? null, 'CPF' => $d['cpf'] ?? null, 'IE' => $d['ie'] ?? null, 'indIEDest' => $d['ie'] ? 1 : 9]);
        $enderecoDest = $d['endereco'];
        $enderecoDest['CEP'] = $enderecoDest['CEP'] ?? $enderecoDest['cep'] ?? null;
        $enderecoDest['UF'] = $enderecoDest['UF'] ?? $enderecoDest['uf'] ?? null;
        $enderecoDest['cPais'] = $enderecoDest['cPais'] ?? '1058';
        $enderecoDest['xPais'] = $enderecoDest['xPais'] ?? 'BRASIL';
        unset($enderecoDest['cep'], $enderecoDest['uf']);
        $this->tag($make, 'tagenderDest', $enderecoDest);
        $valoresProdutos = array_map(
            static fn (array $item): float => round((float) $item['quantidade'] * (float) $item['valor_unitario'], 2),
            $p['produtos'],
        );
        $total = round(array_sum($valoresProdutos), 2);
        $desconto = round((float) ($p['desconto'] ?? 0), 2);
        $outrasDespesas = round((float) ($p['outras_despesas'] ?? 0), 2);
        if ($desconto > $total) {
            throw new NfeEmissionException(
                'O desconto não pode ser maior que o total dos produtos.',
                422,
                'erro',
            );
        }
        $descontoDistribuido = 0.0;
        foreach ($p['produtos'] as $i => $item) {
            $n = $i + 1;
            $value = $valoresProdutos[$i];
            $itemDesconto = $i === array_key_last($p['produtos'])
                ? round($desconto - $descontoDistribuido, 2)
                : round($total > 0 ? $desconto * ($value / $total) : 0, 2);
            $descontoDistribuido = round($descontoDistribuido + $itemDesconto, 2);
            $this->tag($make, 'tagprod', ['item' => $n, 'cProd' => $item['codigo'], 'cEAN' => 'SEM GTIN', 'cEANTrib' => 'SEM GTIN', 'xProd' => $item['descricao'], 'NCM' => $item['ncm'], 'CFOP' => $item['cfop'], 'uCom' => $item['unidade'], 'qCom' => $item['quantidade'], 'vUnCom' => $item['valor_unitario'], 'vProd' => $value, 'vDesc' => $itemDesconto > 0 ? $itemDesconto : null, 'uTrib' => $item['unidade'], 'qTrib' => $item['quantidade'], 'vUnTrib' => $item['valor_unitario'], 'indTot' => 1]);
            $this->tag($make, 'tagimposto', ['item' => $n, 'vTotTrib' => 0]);
            $this->appendTaxes($make, $item, $natureza, $n);
        }
        $valorNota = max(0, round($total - $desconto + $outrasDespesas, 2));
        $this->tag($make, 'tagICMSTot', ['vBC'=>0,'vICMS'=>0,'vICMSDeson'=>0,'vBCST'=>0,'vST'=>0,'vProd'=>$total,'vFrete'=>0,'vSeg'=>0,'vDesc'=>$desconto,'vII'=>0,'vIPI'=>0,'vPIS'=>0,'vCOFINS'=>0,'vOutro'=>$outrasDespesas,'vNF'=>$valorNota,'vTotTrib'=>0]);
        $this->appendTransport($make, $p['transportadora'] ?? [], $p['volumes'] ?? []);
        $tipoPagamento = (string) ($p['pagamento']['tpag'] ?? '90');
        // O schema NF-e 4.00 exige vPag no detPag. Para tPag=90
        // (sem pagamento), o valor correto é zero.
        $detalhePagamento = ['indPag' => 0, 'tPag' => $tipoPagamento];
        if ($tipoPagamento !== '90') {
            $detalhePagamento['vPag'] = $valorNota;
        } else {
            $detalhePagamento['vPag'] = 0;
        }
        $this->tag($make, 'tagpag', []);
        $this->tag($make, 'tagdetPag', $detalhePagamento);
        $informacoesComplementares = trim((string) ($p['informacoes_complementares'] ?? $natureza->informacoes_complementares ?? ''));
        if ($informacoesComplementares !== '') {
            $this->tag($make, 'taginfAdic', ['infCpl' => $informacoesComplementares]);
        }

        $xml = $make->getXML();
        return $xml;
    }

    private function appendTransport(Make $make, array $transportadora, array $volumes): void
    {
        $modFrete = (int) ($transportadora['modalidade_frete'] ?? 9);
        $this->tag($make, 'tagtransp', ['modFrete' => $modFrete]);

        if (!empty($transportadora['nome'])) {
            $documento = preg_replace('/\D+/', '', (string) ($transportadora['documento'] ?? ''));
            $this->tag($make, 'tagtransporta', [
                'CNPJ' => strlen($documento) === 14 ? $documento : null,
                'CPF' => strlen($documento) === 11 ? $documento : null,
                'xNome' => $transportadora['nome'],
                'IE' => $transportadora['inscricao_estadual'] ?? null,
                'xEnder' => $transportadora['endereco'] ?? null,
                'xMun' => $transportadora['municipio'] ?? null,
                'UF' => $transportadora['uf_veiculo'] ?? null,
            ]);
        }

        if (!empty($transportadora['placa'])) {
            $this->tag($make, 'tagveicTransp', [
                'placa' => strtoupper($transportadora['placa']),
                'UF' => strtoupper((string) ($transportadora['uf_veiculo'] ?? '')),
                'RNTC' => $transportadora['rntc'] ?? null,
            ]);
        }

        if ((int) ($volumes['quantidade'] ?? 0) > 0) {
            $this->tag($make, 'tagvol', [
                'qVol' => (int) $volumes['quantidade'],
                'esp' => $volumes['especie'] ?? null,
                'marca' => $volumes['marca'] ?? null,
                'pesoL' => $volumes['peso_liquido'] ?? null,
                'pesoB' => $volumes['peso_bruto'] ?? null,
            ]);
        }
    }

    /** Monta somente os grupos fiscais compatíveis com a regra da natureza. */
    private function appendTaxes(Make $make, array $item, NaturezaOperacao $natureza, int $number): void
    {
        $csosn = str_pad((string) ($item['csosn'] ?? $natureza->csosn_padrao ?? ''), 4, '0', STR_PAD_LEFT);
        if ($natureza->calcula_icms) {
            if (!empty($item['csosn'])) {
                $this->tag($make, 'tagICMSSN', ['item' => $number, 'orig' => $item['origem'] ?? 0, 'CSOSN' => $csosn]);
            } else {
                $this->tag($make, 'tagICMS', ['item' => $number, 'orig' => $item['origem'] ?? 0, 'CST' => $item['cst'] ?? $natureza->cst_padrao, 'modBC' => 3, 'vBC' => 0, 'pICMS' => 0, 'vICMS' => 0]);
            }
        } elseif ($csosn === '0400') {
            // CSOSN 400 = operação não tributada pelo Simples Nacional.
            // O grupo deve existir no XML, mas sem base/alíquota/valor.
            $this->tag($make, 'tagICMSSN', ['item' => $number, 'orig' => $item['origem'] ?? 0, 'CSOSN' => '400']);
        } else {
            $this->tag($make, 'tagICMS', ['item' => $number, 'orig' => $item['origem'] ?? 0, 'CST' => $item['cst'] ?? '40', 'modBC' => 3, 'vBC' => 0, 'pICMS' => 0, 'vICMS' => 0]);
        }
        $this->tag($make, 'tagPIS', ['item' => $number, 'CST' => $natureza->calcula_pis ? ($item['pis_cst'] ?? '01') : '07']);
        $this->tag($make, 'tagCOFINS', ['item' => $number, 'CST' => $natureza->calcula_cofins ? ($item['cofins_cst'] ?? '01') : '07']);
        if ($natureza->calcula_ipi) $this->tag($make, 'tagIPI', ['item' => $number, 'cEnq' => '999', 'CST' => $item['ipi_cst'] ?? '50', 'vBC' => 0, 'pIPI' => 0, 'vIPI' => 0]);
    }

    private function applyCatalogRules(array $payload, NaturezaOperacao $natureza, ?Cliente $cliente, ?Destinatario $destinatario): array
    {
        $source = $cliente ? ['nome' => $cliente->razao_social, 'documento' => $cliente->documento, 'ie' => $cliente->inscricao_estadual, 'cep' => $cliente->cep, 'logradouro' => $cliente->logradouro, 'numero' => $cliente->numero, 'bairro' => $cliente->bairro, 'municipio' => $cliente->cidade, 'cMun' => $cliente->codigo_ibge, 'uf' => $cliente->uf] : ['nome' => $destinatario->nome_razao_social, 'documento' => $destinatario->documento, 'ie' => $destinatario->inscricao_estadual, 'cep' => $destinatario->cep, 'logradouro' => $destinatario->logradouro, 'numero' => $destinatario->numero, 'bairro' => $destinatario->bairro, 'municipio' => $destinatario->municipio, 'cMun' => $destinatario->codigo_municipio_ibge, 'uf' => $destinatario->uf];
        $payload['natureza_operacao'] = $natureza->nome;
        $payload['destinatario'] = [
            'nome' => $source['nome'], 'cnpj' => strlen($source['documento']) === 14 ? $source['documento'] : null, 'cpf' => strlen($source['documento']) === 11 ? $source['documento'] : null, 'ie' => $source['ie'],
            'endereco' => ['xLgr' => $source['logradouro'], 'nro' => $source['numero'], 'xBairro' => $source['bairro'], 'cMun' => $source['cMun'], 'xMun' => $source['municipio'], 'uf' => $source['uf'], 'cep' => $source['cep']],
        ];
        foreach ($payload['produtos'] as &$item) {
            // A natureza fornece apenas o valor inicial. Se o usuário informar
            // outro CFOP no item, ele deve ser preservado até o XML final.
            $item['cfop'] = $item['cfop'] ?: $natureza->cfop_padrao;
            $item['csosn'] = $item['csosn'] ?: ($natureza->csosn_padrao ?: null);
        }
        unset($item);
        return $payload;
    }

    private function tag(Make $make, string $method, array $data): void
    {
        $data = array_filter($data, static fn ($value) => $value !== null && (!is_string($value) || trim($value) !== ''));
        $make->{$method}((object) $data);
    }
    private function emitterAddress(array $p): array { $e=$this->currentEmissor(); return ['xLgr'=>$e?->logradouro ?: 'ENDERECO DO EMITENTE','nro'=>$e?->numero ?: 'S/N','xBairro'=>$e?->bairro ?: 'CENTRO','cMun'=>$e?->codigo_municipio_ibge ?: $p['destinatario']['endereco']['cMun'],'xMun'=>$e?->municipio ?: $p['destinatario']['endereco']['xMun'],'UF'=>$e?->uf ?: config('nfe.uf'),'CEP'=>$e?->cep ?: $p['destinatario']['endereco']['cep'],'cPais'=>'1058','xPais'=>'BRASIL']; }
    private function accessKey(string $xml): ?string { $dom = new \DOMDocument(); $dom->loadXML($xml); $node = $dom->getElementsByTagName('infNFe')->item(0); return $node?->getAttribute('Id') ? substr($node->getAttribute('Id'), 3) : null; }

    private function addDraftIdentity(string $xml): string
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $inf = $dom->getElementsByTagName('infNFe')->item(0);
        $ide = $dom->getElementsByTagName('ide')->item(0);
        $emit = $dom->getElementsByTagName('emit')->item(0);

        if (!$inf || !$ide || !$emit) {
            return $xml;
        }

        $dhEmi = (string) $dom->getElementsByTagName('dhEmi')->item(0)?->nodeValue;
        $cnpj = preg_replace('/\D+/', '', (string) $dom->getElementsByTagName('CNPJ')->item(0)?->nodeValue);
        $cuf = str_pad((string) ($ide->getElementsByTagName('cUF')->item(0)?->nodeValue ?? '35'), 2, '0', STR_PAD_LEFT);
        $aamm = substr(preg_replace('/\D+/', '', $dhEmi), 2, 4) ?: now('America/Sao_Paulo')->format('ym');
        $serie = str_pad((string) ($ide->getElementsByTagName('serie')->item(0)?->nodeValue ?? '1'), 3, '0', STR_PAD_LEFT);
        $numero = str_pad((string) ($ide->getElementsByTagName('nNF')->item(0)?->nodeValue ?? '0'), 9, '0', STR_PAD_LEFT);
        $tpEmis = (string) ($ide->getElementsByTagName('tpEmis')->item(0)?->nodeValue ?? '1');
        $cNf = str_pad((string) ($ide->getElementsByTagName('cNF')->item(0)?->nodeValue ?? random_int(1, 99999999)), 8, '0', STR_PAD_LEFT);
        $base = $cuf . $aamm . str_pad($cnpj, 14, '0', STR_PAD_LEFT) . '55' . $serie . $numero . $tpEmis . $cNf;

        $sum = 0;
        $weight = 2;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $sum += ((int) $base[$i]) * $weight;
            $weight = $weight === 9 ? 2 : $weight + 1;
        }
        $remainder = $sum % 11;
        $digit = $remainder === 0 || $remainder === 1 ? 0 : 11 - $remainder;
        $inf->setAttribute('Id', 'NFe' . $base . $digit);

        return $dom->saveXML();
    }

    private function sefazReason(int $cstat, ?string $reason): string
    {
        $reason = trim((string) $reason);

        return $reason !== ''
            ? sprintf('Rejeição SEFAZ (%d): %s', $cstat, $reason)
            : sprintf('A SEFAZ rejeitou a nota (cStat %d). Consulte os dados fiscais e tente novamente.', $cstat);
    }

    private function friendlyIntegrationMessage(\Throwable $exception): string
    {
        if ($exception instanceof NfeEmissionException) {
            return $exception->getMessage();
        }

        if ($exception instanceof QueryException) {
            return 'Não foi possível registrar a emissão agora. Nenhum documento foi transmitido. Tente novamente em alguns instantes.';
        }

        $message = strtolower($exception->getMessage());
        $networkTerms = ['timeout', 'timed out', 'could not connect', 'connection refused', 'connection reset', 'curl', 'soap', 'failed to open stream'];
        foreach ($networkTerms as $term) {
            if (str_contains($message, $term)) {
                return 'A SEFAZ não respondeu no tempo esperado. A nota foi registrada como erro. Aguarde alguns instantes e consulte novamente.';
            }
        }

        if ($exception instanceof SoapFault) {
            return 'Não foi possível comunicar com a SEFAZ neste momento. Tente novamente em alguns instantes.';
        }

        return 'Não foi possível concluir a emissão da NF-e. A nota foi registrada como erro para auditoria.';
    }
}
