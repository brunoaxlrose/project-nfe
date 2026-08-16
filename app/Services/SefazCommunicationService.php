<?php

namespace App\Services;

use App\Exceptions\NfeEmissionException;
use App\Models\ConfiguracaoEmissor;
use Illuminate\Support\Facades\Log;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Tools;

final class SefazCommunicationService
{
    public function testStatusServico(ConfiguracaoEmissor $emissor, ?int $ambiente = null): array
    {
        $startedAt = microtime(true);

        try {
            $tools = $this->createTools($emissor, $ambiente);
            $response = $tools->sefazStatus();
            $std = (new Standardize())->toStd($response);
            $cstat = (int) ($std->cStat ?? 0);
            $motivo = $this->reason($cstat, (string) ($std->xMotivo ?? ''));

            return [
                'success' => $cstat === 107,
                'ambiente' => (int) ($ambiente ?? $emissor->ambiente),
                'uf' => strtoupper((string) $emissor->uf),
                'cstat' => $cstat,
                'motivo' => $motivo,
                'latencia_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
        } catch (NfeEmissionException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Falha no teste de comunicação com a SEFAZ.', [
                'empresa_id' => $emissor->empresa_id,
                'uf' => $emissor->uf,
                'ambiente' => $ambiente ?? $emissor->ambiente,
                'exception' => $exception,
            ]);

            throw new NfeEmissionException(
                $this->friendlyNetworkMessage($exception),
                503,
                'erro',
                null,
                $exception,
            );
        }
    }

    private function createTools(ConfiguracaoEmissor $emissor, ?int $ambiente = null): Tools
    {
        $userEmpresaId = (int) auth()->user()?->empresa_id;

        if ($userEmpresaId <= 0 || $userEmpresaId !== (int) $emissor->empresa_id) {
            throw new NfeEmissionException(
                'A configuração fiscal não pertence à empresa da sessão atual.',
                403,
                'erro',
            );
        }

        if (!$emissor->certificado_base64) {
            throw new NfeEmissionException(
                'Cadastre um certificado digital A1 antes de testar a comunicação.',
                422,
                'erro',
            );
        }

        if (!$emissor->uf) {
            throw new NfeEmissionException(
                'Informe a UF da empresa antes de testar a comunicação.',
                422,
                'erro',
            );
        }

        $certificate = base64_decode($emissor->certificado_base64, true);

        if ($certificate === false || $certificate === '') {
            throw new NfeEmissionException(
                'O certificado digital salvo para esta empresa está corrompido.',
                422,
                'erro',
            );
        }

        $password = (string) $emissor->certificado_senha;

        if ($password === '') {
            throw new NfeEmissionException(
                'A senha do certificado A1 não está configurada.',
                422,
                'erro',
            );
        }

        try {
            $certificateObject = Certificate::readPfx($certificate, $password);
        } catch (\Throwable $exception) {
            throw new NfeEmissionException(
                'Não foi possível abrir o certificado A1. Confira a senha e o arquivo enviado.',
                422,
                'erro',
                null,
                $exception,
            );
        }

        if ($certificateObject->isExpired()) {
            throw new NfeEmissionException(
                'O certificado digital A1 está vencido. Envie um certificado dentro da validade.',
                422,
                'erro',
            );
        }

        $config = [
            'atualizacao' => now()->format('Y-m-d H:i:s'),
            'tpAmb' => (int) ($ambiente ?? $emissor->ambiente),
            'razaosocial' => $emissor->razao_social,
            'cnpj' => $emissor->cnpj,
            'ie' => $emissor->inscricao_estadual,
            'siglaUF' => strtoupper($emissor->uf),
            'schemes' => config('nfe.schemes'),
            'versao' => config('nfe.versao'),
            'aProxyConf' => [
                'proxyIp' => '',
                'proxyPort' => '',
                'proxyUser' => '',
                'proxyPass' => '',
            ],
        ];

        return new Tools(json_encode($config, JSON_THROW_ON_ERROR), $certificateObject);
    }

    private function reason(int $cstat, string $reason): string
    {
        if ($reason !== '') {
            return $reason;
        }

        return match ($cstat) {
            107 => 'Serviço em Operação',
            108 => 'Serviço paralisado momentaneamente',
            109 => 'Serviço paralisado sem previsão de retorno',
            default => 'A SEFAZ retornou o status '.$cstat.'.',
        };
    }

    private function friendlyNetworkMessage(\Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'A SEFAZ demorou para responder. Tente novamente em alguns instantes.';
        }

        if (str_contains($message, 'could not resolve') || str_contains($message, 'name or service not known')) {
            return 'Não foi possível localizar o endereço da SEFAZ. Verifique a conexão do servidor.';
        }

        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
            return 'A conexão segura com a SEFAZ não pôde ser estabelecida. Verifique o certificado A1.';
        }

        return 'Não foi possível testar a comunicação com a SEFAZ agora. Tente novamente em alguns instantes.';
    }
}
