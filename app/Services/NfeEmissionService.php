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
    public function emit(array $payload, ?int $usuarioId = null): Nfe
    {
        $nfe = null;

        try {
            $natureza = NaturezaOperacao::query()->findOrFail($payload['id_natureza_operacao']);
            $cliente = !empty($payload['id_cliente']) ? Cliente::query()->findOrFail($payload['id_cliente']) : null;
            $destinatario = $cliente ? null : Destinatario::query()->findOrFail($payload['id_destinatario']);
            $payload = $this->applyCatalogRules($payload, $natureza, $cliente, $destinatario);
            $nfe = Nfe::create([
                'numero' => $payload['numero'], 'serie' => $payload['serie'] ?? 1,
                'status' => 'gerando', 'payload' => $payload, 'id_usuario' => $usuarioId,
                'id_cliente' => $cliente?->id, 'id_destinatario' => $destinatario?->id, 'id_natureza_operacao' => $natureza->id,
                'destinatario_documento' => preg_replace('/\D+/', '', (string) ($payload['destinatario']['cnpj'] ?? $payload['destinatario']['cpf'] ?? '')),
            ]);

            $xml = $this->buildXml($payload, $natureza);

            if ($this->simulationEnabled()) {
                $nfe->update([
                    'xml' => $xml,
                    'status' => 'simulada',
                    'xmotivo' => 'Simulação de homologação concluída. Nenhum documento foi transmitido à SEFAZ.',
                ]);

                return $nfe->fresh();
            }

            $signedXml = $this->tools()->signNFe($xml);
            $nfe->update(['xml' => $signedXml, 'status' => 'assinado', 'chave_acesso' => $this->accessKey($signedXml)]);

            // Envio assíncrono: o retorno 103 contém apenas o recibo do lote.
            $response = $this->tools()->sefazEnviaLote([$signedXml], str_pad((string) $nfe->id, 15, '0', STR_PAD_LEFT));
            $std = (new Standardize())->toStd($response);
            $cstat = (int) ($std->cStat ?? 0);
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

    private function buildXml(array $p, NaturezaOperacao $natureza): string
    {
        $make = new Make();
        $version = config('nfe.versao');
        $emissor = $this->currentEmissor();
        $ambiente = $emissor?->ambiente ?? config('nfe.ambiente');
        $codigoMunicipio = $emissor?->codigo_municipio_ibge ?: $p['destinatario']['endereco']['cMun'];
        $emissao = trim(($p['data_emissao'] ?? now()->format('Y-m-d')).' '.($p['hora_emissao'] ?? now()->format('H:i')));
        $now = \Carbon\Carbon::parse($emissao)->toIso8601String();
        $saida = !empty($p['data_saida'])
            ? \Carbon\Carbon::parse(trim($p['data_saida'].' '.($p['hora_saida'] ?? '00:00')))->toIso8601String()
            : null;
        $tipoNota = ($natureza->tipo_movimento ?? 'Saída') === 'Entrada' ? 0 : 1;
        $finalidade = (int) ($p['finalidade'] ?? 1);
        $indFinal = !empty($p['consumidor_final']) ? 1 : 0;
        $indPres = (int) ($p['ind_pres'] ?? 9);
        $this->tag($make, 'taginfNFe', ['versao' => $version, 'Id' => null, 'pk_nItem' => '']);
        $this->tag($make, 'tagide', ['cUF' => config('nfe.cuf'), 'cNF' => random_int(10000000, 99999999), 'natOp' => $p['natureza_operacao'], 'mod' => 55, 'serie' => $p['serie'] ?? ($emissor?->serie_padrao ?? 1), 'nNF' => $p['numero'], 'dhEmi' => $now, 'dhSaiEnt' => $saida, 'tpNF' => $tipoNota, 'idDest' => 1, 'cMunFG' => $codigoMunicipio, 'tpImp' => 1, 'tpEmis' => 1, 'tpAmb' => $ambiente, 'finNFe' => $finalidade, 'indFinal' => $indFinal, 'indPres' => $indPres, 'procEmi' => 0, 'verProc' => '1.0']);
        $this->tag($make, 'tagemit', ['xNome' => $emissor?->razao_social ?? config('nfe.razao_social'), 'CNPJ' => $emissor?->cnpj ?? config('nfe.cnpj'), 'IE' => $emissor?->inscricao_estadual ?? config('nfe.ie'), 'CRT' => $emissor?->crt ?? config('nfe.crt')]);
        $this->tag($make, 'tagenderEmit', $this->emitterAddress($p));
        $d = $p['destinatario']; $this->tag($make, 'tagdest', ['xNome' => $d['nome'], 'CNPJ' => $d['cnpj'] ?? null, 'CPF' => $d['cpf'] ?? null, 'IE' => $d['ie'] ?? null, 'indIEDest' => $d['ie'] ? 1 : 9]);
        $this->tag($make, 'tagenderDest', $d['endereco'] + ['CEP' => $d['endereco']['cep'], 'UF' => $d['endereco']['uf']]);
        $total = 0.0;
        foreach ($p['produtos'] as $i => $item) {
            $n = $i + 1; $value = round((float)$item['quantidade'] * (float)$item['valor_unitario'], 2); $total += $value;
            $this->tag($make, 'tagprod', ['item' => $n, 'cProd' => $item['codigo'], 'cEAN' => 'SEM GTIN', 'cEANTrib' => 'SEM GTIN', 'xProd' => $item['descricao'], 'NCM' => $item['ncm'], 'CFOP' => $item['cfop'], 'uCom' => $item['unidade'], 'qCom' => $item['quantidade'], 'vUnCom' => $item['valor_unitario'], 'vProd' => $value, 'uTrib' => $item['unidade'], 'qTrib' => $item['quantidade'], 'vUnTrib' => $item['valor_unitario'], 'indTot' => 1]);
            $this->tag($make, 'tagimposto', ['item' => $n, 'vTotTrib' => 0]);
            $this->appendTaxes($make, $item, $natureza, $n);
        }
        $desconto = round((float) ($p['desconto'] ?? 0), 2);
        $outrasDespesas = round((float) ($p['outras_despesas'] ?? 0), 2);
        $valorNota = max(0, round($total - $desconto + $outrasDespesas, 2));
        $this->tag($make, 'tagICMSTot', ['vBC'=>0,'vICMS'=>0,'vICMSDeson'=>0,'vBCST'=>0,'vST'=>0,'vProd'=>$total,'vFrete'=>0,'vSeg'=>0,'vDesc'=>$desconto,'vII'=>0,'vIPI'=>0,'vPIS'=>0,'vCOFINS'=>0,'vOutro'=>$outrasDespesas,'vNF'=>$valorNota,'vTotTrib'=>0]);
        $this->appendTransport($make, $p['transportadora'] ?? [], $p['volumes'] ?? []);
        $this->tag($make, 'tagpag', ['vTroco' => 0]); $this->tag($make, 'tagdetPag', ['indPag'=>0, 'tPag'=>$p['pagamento']['tpag'] ?? '90', 'vPag'=>$valorNota]);
        $this->tag($make, 'taginfAdic', ['infCpl' => $p['informacoes_complementares'] ?? $natureza->informacoes_complementares]);
        return $make->getXML();
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
            $item['cfop'] = $natureza->cfop_padrao;
            $item['csosn'] = $natureza->csosn_padrao ?: ($item['csosn'] ?? null);
        }
        unset($item);
        return $payload;
    }

    private function tag(Make $make, string $method, array $data): void { $make->{$method}((object) array_filter($data, static fn ($v) => $v !== null)); }
    private function emitterAddress(array $p): array { $e=$this->currentEmissor(); return ['xLgr'=>$e?->logradouro ?: 'ENDERECO DO EMITENTE','nro'=>$e?->numero ?: 'S/N','xBairro'=>$e?->bairro ?: 'CENTRO','cMun'=>$e?->codigo_municipio_ibge ?: $p['destinatario']['endereco']['cMun'],'xMun'=>$e?->municipio ?: $p['destinatario']['endereco']['xMun'],'UF'=>$e?->uf ?: config('nfe.uf'),'CEP'=>$e?->cep ?: $p['destinatario']['endereco']['cep'],'cPais'=>'1058','xPais'=>'BRASIL']; }
    private function accessKey(string $xml): ?string { $dom = new \DOMDocument(); $dom->loadXML($xml); $node = $dom->getElementsByTagName('infNFe')->item(0); return $node?->getAttribute('Id') ? substr($node->getAttribute('Id'), 3) : null; }

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
