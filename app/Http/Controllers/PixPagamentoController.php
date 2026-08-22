<?php

namespace App\Http\Controllers;

use App\Models\AsaasPagamento;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\AsaasService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class PixPagamentoController extends Controller
{
    public function __construct(
        private readonly AsaasService $asaas,
        private readonly AssinaturaService $assinaturas,
    ) {}

    public function options(Request $request): JsonResponse
    {
        $empresa = $request->user()->empresa()
            ->with(['assinaturaAtual.plano'])
            ->firstOrFail();
        $assinatura = $empresa->assinaturaAtual;
        $planoAtual = $assinatura?->plano;
        $planoRenovacao = $this->renewalPlan();
        $valorPix = number_format((float) $planoRenovacao->valor_mensal, 2, '.', '');

        return response()->json([
            'empresa' => [
                'id' => $empresa->id_empresa,
                'razao_social' => $empresa->razao_social,
                'nome_fantasia' => $empresa->nome_fantasia,
            ],
            'assinatura' => $assinatura ? [
                'status' => $assinatura->status,
                'inicia_em' => $assinatura->inicia_em?->toIso8601String(),
                'termina_em' => $assinatura->termina_em?->toIso8601String(),
                'carencia_ate' => $assinatura->carencia_ate?->toIso8601String(),
            ] : null,
            'plano_atual' => $planoAtual ? [
                'id_plano' => $planoAtual->id_plano,
                'nome' => $planoAtual->nome,
                'descricao' => $planoAtual->descricao,
                'valor_mensal' => $planoAtual->valor_mensal,
                'duracao_dias' => $planoAtual->duracao_dias,
                'modulos' => $planoAtual->modulos ?? [],
            ] : null,
            'planos' => [[
                'id_plano' => $planoRenovacao->id_plano,
                'nome' => $planoRenovacao->nome,
                'descricao' => $planoRenovacao->descricao,
                'valor_mensal' => $valorPix,
                'duracao_dias' => $planoRenovacao->duracao_dias,
                'modulos' => $planoRenovacao->modulos ?? [],
            ]],
            'valor_pix' => $valorPix,
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_plano' => ['required', 'integer', Rule::exists('plano', 'id_plano')->where('ativo', true)->where('slug', (string) config('asaas.renewal_plan_slug', 'legado-completo'))],
            'id_usuario' => ['required', 'integer', 'exists:usuario,id_usuario'],
            'email' => ['required', 'email:rfc', 'max:180'],
        ]);

        $usuario = User::query()->with('empresa')->findOrFail($data['id_usuario']);
        $authUser = $request->user();

        if (!$authUser->master && (int) $usuario->id_usuario !== (int) $authUser->id_usuario) {
            return response()->json(['message' => 'Voce so pode gerar Pix para o proprio usuario.'], 403);
        }

        $plano = $this->renewalPlan();
        if ((int) $plano->id_plano !== (int) $data['id_plano']) {
            return response()->json(['message' => 'Plano indisponivel para renovacao por Pix.'], 422);
        }
        $valor = round((float) $plano->valor_mensal, 2);

        $pagamento = AsaasPagamento::query()->create([
            'id_empresa' => $usuario->id_empresa,
            'id_usuario' => $usuario->id_usuario,
            'id_plano' => $plano->id_plano,
            'external_reference' => (string) Str::uuid(),
            'valor' => $valor,
            'payer_email' => $data['email'],
            'status' => 'created',
        ]);

        try {
            $customer = $this->asaas->createCustomer($usuario, $data['email']);
            $pagamento->update(['asaas_customer_id' => $customer['id'] ?? null]);

            $response = $this->asaas->createPixPayment($pagamento->load('plano'));
            $paymentId = (string) ($response['id'] ?? '');
            $qrCode = $paymentId ? $this->asaas->getPixQrCode($paymentId) : [];
        } catch (RequestException $exception) {
            $pagamento->update([
                'status' => 'failed',
                'raw_response' => $exception->response?->json(),
            ]);

            Log::warning('Asaas recusou a criacao do Pix.', [
                'pagamento_id' => $pagamento->id_asaas_pagamento,
                'status' => $exception->response?->status(),
                'body' => $exception->response?->json(),
            ]);

            return response()->json([
                'message' => $this->asaasErrorMessage($exception->response?->json()),
            ], 422);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }

        $pagamento->update([
            'asaas_payment_id' => $paymentId ?: null,
            'status' => $response['status'] ?? 'PENDING',
            'qr_code' => $qrCode['payload'] ?? null,
            'qr_code_base64' => $qrCode['encodedImage'] ?? null,
            'pix_expira_em' => isset($qrCode['expirationDate']) ? CarbonImmutable::parse($qrCode['expirationDate']) : null,
            'raw_response' => ['payment' => $response, 'pix_qr_code' => $qrCode],
        ]);

        return response()->json([
            'transaction_id' => $pagamento->asaas_payment_id,
            'external_reference' => $pagamento->external_reference,
            'valor' => $pagamento->valor,
            'qr_code' => $pagamento->qr_code,
            'qr_code_base64' => $pagamento->qr_code_base64,
        ], 201);
    }

    public function webhook(Request $request): JsonResponse
    {
        $paymentId = (string) ($request->input('payment.id') ?: $request->input('id'));

        if (!$paymentId) {
            return response()->json(['received' => true]);
        }

        try {
            $payment = $this->asaas->getPayment($paymentId);
            $this->syncPayment($payment);
        } catch (Throwable $exception) {
            Log::warning('Falha ao processar webhook do Asaas.', [
                'payment_id' => $paymentId,
                'message' => $exception->getMessage(),
            ]);
        }

        return response()->json(['received' => true]);
    }

    private function syncPayment(array $payment): void
    {
        $asaasPaymentId = isset($payment['id']) ? (string) $payment['id'] : null;
        $externalReference = $payment['externalReference'] ?? null;

        if (!$asaasPaymentId || !$externalReference) {
            return;
        }

        DB::transaction(function () use ($payment, $asaasPaymentId, $externalReference): void {
            $pagamento = AsaasPagamento::query()
                ->where('external_reference', $externalReference)
                ->lockForUpdate()
                ->first();

            if (!$pagamento) {
                return;
            }

            $status = (string) ($payment['status'] ?? $pagamento->status);
            $alreadyProcessed = $pagamento->processado_em !== null;
            $approved = in_array($status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);

            $pagamento->update([
                'asaas_payment_id' => $asaasPaymentId,
                'status' => $status,
                'raw_response' => $payment,
                'aprovado_em' => $approved ? ($pagamento->aprovado_em ?? now()) : $pagamento->aprovado_em,
            ]);

            if (!$approved || $alreadyProcessed) {
                return;
            }

            $this->assinaturas->substituir($pagamento->empresa, $pagamento->plano, [
                'status' => 'ativa',
                'inicia_em' => now(),
                'observacoes' => 'Liberacao automatica via Pix Asaas. Payment ID: '.$asaasPaymentId,
            ], $pagamento->usuario);

            $pagamento->update(['processado_em' => now()]);
        });
    }

    private function renewalPlan(): Plano
    {
        return Plano::query()
            ->where('ativo', true)
            ->where('slug', (string) config('asaas.renewal_plan_slug', 'legado-completo'))
            ->firstOrFail();
    }

    private function asaasErrorMessage(?array $payload): string
    {
        $description = $payload['errors'][0]['description'] ?? null;

        return is_string($description) && $description !== ''
            ? $description
            : 'Nao foi possivel gerar o Pix no Asaas.';
    }
}
