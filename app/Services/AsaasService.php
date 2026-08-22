<?php

namespace App\Services;

use App\Models\AsaasPagamento;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AsaasService
{
    public function createCustomer(User $usuario, string $email): array
    {
        $empresa = $usuario->empresa;
        $payload = [
            'name' => $empresa?->nome_fantasia ?: $empresa?->razao_social ?: $usuario->nome,
            'email' => $email,
        ];

        $document = $this->document($empresa);
        if ($document) {
            $payload['cpfCnpj'] = $document;
        }

        return $this->request()
            ->post('/customers', $payload)
            ->throw()
            ->json();
    }

    public function createPixPayment(AsaasPagamento $pagamento): array
    {
        return $this->request()
            ->post('/payments', [
                'customer' => $pagamento->asaas_customer_id,
                'billingType' => 'PIX',
                'value' => (float) $pagamento->valor,
                'dueDate' => now()->toDateString(),
                'description' => 'Renovacao do plano '.$pagamento->plano->nome,
                'externalReference' => $pagamento->external_reference,
            ])
            ->throw()
            ->json();
    }

    public function getPixQrCode(string $paymentId): array
    {
        return $this->request()
            ->get('/payments/'.$paymentId.'/pixQrCode')
            ->throw()
            ->json();
    }

    public function getPayment(string $paymentId): array
    {
        return $this->request()
            ->get('/payments/'.$paymentId)
            ->throw()
            ->json();
    }

    private function request(): PendingRequest
    {
        $apiKey = config('asaas.api_key');

        if (!$apiKey) {
            throw new RuntimeException('ASAAS_API_KEY nao configurada.');
        }

        return Http::baseUrl(rtrim((string) config('asaas.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->withHeader('access_token', $apiKey)
            ->timeout(20);
    }

    private function document(?Empresa $empresa): ?string
    {
        $document = preg_replace('/\D+/', '', (string) $empresa?->cnpj);

        return in_array(strlen($document), [11, 14], true) ? $document : null;
    }
}
