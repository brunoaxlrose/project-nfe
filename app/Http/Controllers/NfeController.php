<?php

namespace App\Http\Controllers;

use App\Exceptions\NfeEmissionException;
use App\Models\Nfe;
use App\Models\ConfiguracaoEmissor;
use App\Services\NfeEmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class NfeController extends Controller
{
    public function __construct(private readonly NfeEmissionService $emitter) {}

    public function nextNumber(Request $request): JsonResponse
    {
        $data = $request->validate([
            'serie' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);
        $serie = (int) ($data['serie'] ?? 1);
        $config = ConfiguracaoEmissor::current();
        $numero = ((int) Nfe::query()->where('serie', $serie)->max('numero')) + 1;
        if ((int) $config->serie_padrao === $serie && $config->proximo_numero) {
            $numero = (int) $config->proximo_numero;
        }

        return response()->json(['serie' => $serie, 'numero' => $numero]);
    }

    public function store(Request $request): JsonResponse
    {
        $empresaId = (int) $request->user()->id_empresa;
        $payload = $request->validate([
            'numero' => ['required', 'integer', 'min:1', 'max:999999999'],
            'serie' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'natureza_operacao' => ['nullable', 'string', 'max:60'],
            'id_natureza_operacao' => [
                'required',
                'integer',
                Rule::exists('natureza_operacao', 'id_natureza_operacao')->where('id_empresa', $empresaId),
            ],
            'tipo_saida' => ['nullable', 'in:propria,terceiros'],
            'data_emissao' => ['nullable', 'date'],
            'hora_emissao' => ['nullable', 'date_format:H:i'],
            'data_saida' => ['nullable', 'date'],
            'hora_saida' => ['nullable', 'date_format:H:i'],
            'finalidade' => ['nullable', 'in:1,2,3,4'],
            'ind_pres' => ['nullable', 'integer', 'between:0,9'],
            'consumidor_final' => ['nullable', 'boolean'],
            'id_destinatario' => [
                'nullable',
                'integer',
                Rule::exists('destinatario', 'id_destinatario')->where('id_empresa', $empresaId),
            ],
            'id_cliente' => [
                'nullable',
                'integer',
                Rule::exists('cliente', 'id_cliente')->where('id_empresa', $empresaId),
            ],
            'destinatario' => ['nullable', 'array'],
            'destinatario.nome' => ['nullable', 'string', 'max:60'],
            'destinatario.cnpj' => ['nullable', 'digits:14'],
            'destinatario.cpf' => ['nullable', 'digits:11'],
            'destinatario.ie' => ['nullable', 'string', 'max:14'],
            'destinatario.endereco' => ['nullable', 'array'],
            'destinatario.endereco.xLgr' => ['nullable', 'string'],
            'destinatario.endereco.nro' => ['nullable', 'string'],
            'destinatario.endereco.xBairro' => ['nullable', 'string'],
            'destinatario.endereco.cMun' => ['nullable', 'digits:7'],
            'destinatario.endereco.xMun' => ['nullable', 'string'],
            'destinatario.endereco.uf' => ['nullable', 'size:2'],
            'destinatario.endereco.cep' => ['nullable', 'digits:8'],
            'produtos' => ['required', 'array', 'min:1', 'max:50'],
            'produtos.*.codigo' => ['required', 'string'],
            'produtos.*.descricao' => ['required', 'string'],
            'produtos.*.ncm' => ['required', 'digits:8'],
            'produtos.*.cfop' => ['required', 'digits:4'],
            'produtos.*.unidade' => ['required', 'string', 'max:6'],
            'produtos.*.quantidade' => ['required', 'integer', 'gt:0'],
            'produtos.*.valor_unitario' => ['required', 'numeric', 'gte:0'],
            'produtos.*.cst' => ['nullable', 'required_without:produtos.*.csosn', 'string', 'max:3'],
            'produtos.*.csosn' => ['nullable', 'required_without:produtos.*.cst', 'string', 'max:4'],
            'produtos.*.origem' => ['sometimes', 'integer', 'between:0,8'],
            'produtos.*.pis_cst' => ['nullable', 'string', 'max:2'],
            'produtos.*.cofins_cst' => ['nullable', 'string', 'max:2'],
            'calculo_automatico' => ['nullable', 'boolean'],
            'desconto' => ['nullable', 'numeric', 'gte:0'],
            'outras_despesas' => ['nullable', 'numeric', 'gte:0'],
            'transportadora' => ['nullable', 'array'],
            'transportadora.modalidade_frete' => ['nullable', 'in:0,1,2,9'],
            'transportadora.nome' => ['nullable', 'string', 'max:120'],
            'transportadora.documento' => ['nullable', 'digits_between:11,14'],
            'transportadora.inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'transportadora.endereco' => ['nullable', 'string', 'max:120'],
            'transportadora.placa' => ['nullable', 'string', 'max:8'],
            'transportadora.uf_veiculo' => ['nullable', 'size:2'],
            'transportadora.rntc' => ['nullable', 'string', 'max:20'],
            'transportadora.municipio' => ['nullable', 'string', 'max:60'],
            'volumes' => ['nullable', 'array'],
            'volumes.quantidade' => ['nullable', 'integer', 'min:0'],
            'volumes.especie' => ['nullable', 'string', 'max:60'],
            'volumes.marca' => ['nullable', 'string', 'max:60'],
            'volumes.peso_bruto' => ['nullable', 'numeric', 'gte:0'],
            'volumes.peso_liquido' => ['nullable', 'numeric', 'gte:0'],
            'pagamento.tpag' => ['sometimes', 'string', 'size:2'],
            'informacoes_complementares' => ['nullable', 'string', 'max:2000'],
            'modo' => ['nullable', 'in:rascunho,emitir'],
        ]);

        $totalProdutos = collect($payload['produtos'])
            ->sum(fn (array $produto): float => round((float) $produto['quantidade'] * (float) $produto['valor_unitario'], 2));
        $desconto = round((float) ($payload['desconto'] ?? 0), 2);
        if ($desconto > $totalProdutos) {
            throw ValidationException::withMessages([
                'desconto' => 'O desconto não pode ser maior que o total dos produtos (R$ '.number_format($totalProdutos, 2, ',', '.').').',
            ]);
        }

        $config = ConfiguracaoEmissor::current();
        if ((int) $config->ambiente === 1) {
            $numeroOcupado = Nfe::query()
                ->where('serie', (int) ($payload['serie'] ?? 1))
                ->where('numero', (int) $payload['numero'])
                ->whereIn('status', ['autorizada', 'cancelada'])
                ->exists();
            if ($numeroOcupado) {
                throw ValidationException::withMessages([
                    'numero' => 'Este número já está ocupado por uma NF-e autorizada ou cancelada. Informe outro número.',
                ]);
            }
        }

        if (empty($payload['id_cliente']) && empty($payload['id_destinatario'])) {
            throw ValidationException::withMessages(['id_cliente' => 'Selecione um cliente ou destinatário cadastrado.']);
        }
        try {
            if (($payload['modo'] ?? 'emitir') === 'rascunho') {
                $cliente = !empty($payload['id_cliente']) ? \App\Models\Cliente::query()->findOrFail($payload['id_cliente']) : null;
                $destinatario = $cliente ? null : \App\Models\Destinatario::query()->findOrFail($payload['id_destinatario']);
                $nfe = Nfe::create([
                    'numero' => $payload['numero'],
                    'serie' => $payload['serie'] ?? 1,
                    'status' => 'rascunho',
                    'payload' => $payload,
                    'id_usuario' => $request->user()->id,
                    'id_cliente' => $cliente?->id,
                    'id_destinatario' => $destinatario?->id,
                    'id_natureza_operacao' => $payload['id_natureza_operacao'],
                    'destinatario_documento' => preg_replace('/\D+/', '', (string) ($payload['destinatario']['cnpj'] ?? $payload['destinatario']['cpf'] ?? '')),
                ]);

                return response()->json([...$nfe->fresh()->toArray(), 'message' => 'NF-e salva como pendente.'], 201);
            }

            $nfe = $this->emitter->emit($payload, $request->user()->id);
            if ($nfe->status === 'autorizada') {
                $config = ConfiguracaoEmissor::current();
                if ((int) $config->serie_padrao === (int) $nfe->serie && (int) $config->proximo_numero === (int) $nfe->numero) {
                    $config->update(['proximo_numero' => (int) $nfe->numero + 1]);
                }
            }
            return response()->json($nfe, 201);
        } catch (NfeEmissionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->nfeStatus,
                'cstat' => $exception->cstat,
            ], $exception->httpStatus);
        } catch (\Throwable $exception) {
            Log::error('Falha inesperada no endpoint de emissão de NF-e.', [
                'exception' => $exception,
                'id_usuario' => $request->user()?->id,
                'id_empresa' => $request->user()?->id_empresa,
            ]);

            return response()->json([
                'message' => 'Não foi possível concluir a emissão agora. Nenhum documento foi transmitido. Tente novamente em alguns instantes.',
                'status' => 'erro',
            ], 503);
        }
    }

    public function emitirPendente(Request $request, Nfe $nfe): JsonResponse
    {
        if ($nfe->status !== 'rascunho') {
            return response()->json(['message' => 'Somente uma NF-e pendente pode ser enviada para a SEFAZ.'], 422);
        }

        try {
            $emitida = $this->emitter->emit($nfe->payload ?? [], $request->user()->id, $nfe);
            return response()->json($emitida);
        } catch (NfeEmissionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->nfeStatus,
                'cstat' => $exception->cstat,
                'nota' => $nfe->fresh(),
            ], $exception->httpStatus);
        } catch (\Throwable $exception) {
            Log::error('Falha inesperada ao transmitir NF-e pendente.', [
                'exception' => $exception,
                'nfe_id' => $nfe->id,
                'id_usuario' => $request->user()?->id,
            ]);

            return response()->json(['message' => 'Não foi possível enviar a NF-e pendente para a SEFAZ agora.', 'status' => 'erro'], 503);
        }
    }

    public function consult(Request $request, Nfe $nfe): JsonResponse
    {
        if (!$nfe->recibo) {
            throw ValidationException::withMessages(['recibo' => 'A NF-e ainda não possui recibo de lote.']);
        }

        try {
            return response()->json($this->emitter->consultReceipt($nfe));
        } catch (NfeEmissionException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->nfeStatus,
                'cstat' => $exception->cstat,
            ], $exception->httpStatus);
        } catch (\Throwable $exception) {
            Log::error('Falha inesperada na consulta do recibo da NF-e.', [
                'exception' => $exception,
                'nfe_id' => $nfe->id,
                'id_usuario' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Não foi possível consultar o retorno da SEFAZ agora. Tente novamente em alguns instantes.',
                'status' => 'erro',
            ], 503);
        }
    }
}
