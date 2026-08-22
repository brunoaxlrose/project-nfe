<?php

namespace App\Http\Controllers;

use App\Models\Nfe;
use App\Services\NfeEmissionService;
use App\Exceptions\NfeEmissionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FaturamentoController extends Controller
{
    public function resumo(Request $request): JsonResponse
    {
        $porStatus = Nfe::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $emitidas = Nfe::query()
            ->whereNotIn('status', ['rascunho'])
            ->count();

        $timezone = 'America/Sao_Paulo';
        $inicioVolume = Carbon::now($timezone)->subDays(6)->startOfDay()->utc();
        $volumePorDia = Nfe::query()
            ->whereNotIn('status', ['rascunho'])
            ->where('created_at', '>=', $inicioVolume)
            ->get(['created_at', 'payload'])
            ->groupBy(fn (Nfe $nfe): string => $nfe->created_at->setTimezone($timezone)->format('Y-m-d'));

        $volume = collect(range(6, 0))->map(function (int $dias) use ($timezone, $volumePorDia): array {
            $data = Carbon::now($timezone)->subDays($dias);
            $notas = $volumePorDia->get($data->format('Y-m-d'), collect());

            return [
                'data' => $data->format('Y-m-d'),
                'label' => $data->locale('pt_BR')->isoFormat('ddd'),
                'quantidade' => $notas->count(),
                'valor' => round($notas->sum(fn (Nfe $nfe): float => $nfe->valor_total), 2),
            ];
        })->values();

        return response()->json([
            'notas_emitidas' => $emitidas,
            'autorizadas' => (int) ($porStatus['autorizada'] ?? 0),
            'processando' => (int) collect(['gerando', 'assinado', 'aguardando_retorno'])->sum(fn (string $status) => (int) ($porStatus[$status] ?? 0)),
            'rejeitadas' => (int) ($porStatus['rejeitada'] ?? 0) + (int) ($porStatus['erro'] ?? 0),
            'volume_7_dias' => $volume,
            'por_status' => $porStatus,
        ]);
    }

    public function index(Request $request)
    {
        $request->merge([
            'documento' => preg_replace('/\D+/', '', (string) $request->input('documento', '')),
        ]);

        $filters = $request->validate([
            'data_inicio' => ['nullable', 'date'], 'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'status' => ['nullable', 'in:rascunho,gerando,assinado,aguardando_retorno,autorizada,simulada,rejeitada,cancelada,erro'],
            'documento' => ['nullable', 'digits_between:11,14'], 'busca' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Nfe::query()
            ->with([
                'cliente:id_cliente,razao_social',
                'destinatario:id_destinatario,nome_razao_social',
                'naturezaOperacao:id_natureza_operacao,nome,tipo_movimento,cfop_padrao,csosn_padrao',
            ])
                ->select(['id_nota_fiscal','numero','serie','chave_acesso','status','recibo','protocolo','cstat','xmotivo','destinatario_documento','id_usuario','id_cliente','id_destinatario','id_natureza_operacao','created_at','danfe_path','payload']);
        // Os timestamps são armazenados em UTC, mas os filtros da tela são
        // datas locais do usuário. Converta os limites de São Paulo para UTC
        // antes de consultar, evitando ocultar notas emitidas após 00:00 UTC.
        $timezone = 'America/Sao_Paulo';
        if (!empty($filters['data_inicio'])) {
            $inicio = Carbon::parse($filters['data_inicio'], $timezone)->startOfDay()->utc();
            $query->where('created_at', '>=', $inicio);
        }
        if (!empty($filters['data_fim'])) {
            $fim = Carbon::parse($filters['data_fim'], $timezone)->endOfDay()->utc();
            $query->where('created_at', '<=', $fim);
        }
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['documento'])) $query->where('destinatario_documento', preg_replace('/\D+/', '', $filters['documento']));
        if (!empty($filters['busca'])) {
            $term = trim($filters['busca']);
            $digits = preg_replace('/\D+/', '', $term);
            $query->where(function ($search) use ($term, $digits): void {
                $search->whereRaw('CAST(numero AS TEXT) ILIKE ?', ['%'.$term.'%'])
                    ->orWhere('destinatario_documento', 'like', '%'.($digits ?: $term).'%')
                    ->orWhereHas('cliente', fn ($relation) => $relation->where('razao_social', 'ilike', '%'.$term.'%'))
                    ->orWhereHas('destinatario', fn ($relation) => $relation->where('nome_razao_social', 'ilike', '%'.$term.'%'));
            });
        }

        $paginated = $query->latest('id_nota_fiscal')->paginate($filters['per_page'] ?? 10)->withQueryString();
        $paginated->getCollection()->transform(function (Nfe $nota): array {
            return [
                ...$nota->toArray(),
                'destinatario_nome' => $nota->cliente?->razao_social ?: $nota->destinatario?->nome_razao_social,
                'valor_total' => $nota->valor_total,
            ];
        });

        return response()->json($paginated);
    }

    public function clone(Request $request, Nfe $nfe): JsonResponse
    {
        $draft = DB::transaction(function () use ($request, $nfe): Nfe {
            $numero = ((int) Nfe::query()->where('serie', $nfe->serie)->max('numero')) + 1;

            return Nfe::create([
                'numero' => $numero,
                'serie' => $nfe->serie,
                'status' => 'rascunho',
                'payload' => $nfe->payload ?? [],
                'id_usuario' => $request->user()->id,
                'id_cliente' => $nfe->id_cliente,
                'id_destinatario' => $nfe->id_destinatario,
                'id_natureza_operacao' => $nfe->id_natureza_operacao,
                'destinatario_documento' => $nfe->destinatario_documento,
            ]);
        });

        return response()->json([
            'message' => 'Nota pendente criada a partir da nota selecionada.',
            'id' => $draft->id,
            'numero' => $draft->numero,
            'payload' => $draft->payload,
        ], 201);
    }

    public function cancelar(Request $request, Nfe $nfe, NfeEmissionService $emissionService): JsonResponse
    {
        $data = $request->validate(['justificativa' => ['required', 'string', 'min:15', 'max:255']]);

        try {
            $cancelada = $emissionService->cancel($nfe, $data['justificativa']);

            return response()->json([
                'message' => 'Cancelamento autorizado pela SEFAZ.',
                'nota' => $cancelada,
            ]);
        } catch (NfeEmissionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => $e->nfeStatus,
                'cstat' => $e->cstat,
                'nota' => $nfe->fresh(),
            ], $e->httpStatus);
        }
    }

    public function destroy(Request $request, Nfe $nfe): JsonResponse
    {
        if ($nfe->status !== 'rascunho') {
            return response()->json([
                'message' => 'Apenas notas pendentes podem ser excluídas.',
            ], 422);
        }

        $nfe->delete();

        return response()->json([
            'message' => 'Nota pendente excluída com sucesso.',
        ]);
    }

    public function cartaCorrecao(Request $request, Nfe $nfe): JsonResponse
    {
        $request->validate(['justificativa' => ['required', 'string', 'min:15', 'max:1000']]);

        return response()->json([
            'message' => 'A Carta de Correção precisa ser transmitida e autorizada pela SEFAZ. A rotina de transmissão ainda não está habilitada para este ambiente.',
        ], 422);
    }

    public function download(Request $request, Nfe $nfe): BinaryFileResponse|\Illuminate\Http\Response|JsonResponse
    {
        $tipo = $request->query('tipo', 'zip');
        if (!in_array($tipo, ['xml', 'pdf', 'zip'], true)) {
            return response()->json(['message' => 'Selecione um tipo de arquivo válido para download.'], 422);
        }

        try {
            $files = [];

            if ($tipo === 'pdf' && $nfe->status === 'rascunho') {
                $pdf = app(NfeEmissionService::class)->renderDraftDanfe($nfe);

                return response($pdf, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="danfe-previa-'.$nfe->numero.'.pdf"',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                ]);
            }

            if ($tipo !== 'pdf' && $nfe->xml) {
                $xmlName = $nfe->chave_acesso
                    ? $nfe->chave_acesso.'-procNFe.xml'
                    : $nfe->id.'-nfe.xml';
                $xmlPath = 'nfe/'.$xmlName;

                if (!Storage::disk('local')->exists($xmlPath)) {
                    Storage::disk('local')->put($xmlPath, $nfe->xml);
                }

                $files['xml'] = $xmlPath;
            }

            if ($tipo !== 'xml' && $nfe->danfe_path && Storage::disk('local')->exists($nfe->danfe_path)) {
                $files['pdf'] = $nfe->danfe_path;
            }

            if ($tipo === 'xml' && !isset($files['xml'])) {
                return response()->json(['message' => 'O XML desta nota ainda não está disponível para download.'], 404);
            }

            if ($tipo === 'pdf' && !isset($files['pdf'])) {
                return response()->json(['message' => 'O DANFE desta nota ainda não está disponível para download.'], 404);
            }

            if ($tipo === 'zip' && !$files) {
                return response()->json(['message' => 'Os documentos desta nota ainda não estão disponíveis para download.'], 404);
            }

            if ($tipo === 'xml') {
                return response()->download(
                    Storage::disk('local')->path($files['xml']),
                    'nfe-'.$nfe->id.'.xml',
                    ['Content-Type' => 'application/xml'],
                );
            }

            if ($tipo === 'pdf') {
                return response()->download(
                    Storage::disk('local')->path($files['pdf']),
                    ($nfe->chave_acesso ?: 'danfe-'.$nfe->id).'.pdf',
                    ['Content-Type' => 'application/pdf'],
                );
            }

            $zipPath = storage_path('app/nfe/'.$nfe->id.'-documentos.zip');
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return response()->json(['message' => 'Não foi possível preparar os documentos para download.'], 500);
            }

            foreach ($files as $extension => $path) {
                $zip->addFile(Storage::disk('local')->path($path), 'nfe-'.$nfe->id.'.'.$extension);
            }

            $zip->close();

            return response()->download($zipPath, 'nfe-'.$nfe->id.'-documentos.zip')->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            Log::error('Falha técnica ao preparar download da NF-e.', [
                'exception' => $exception,
                'nfe_id' => $nfe->id,
                'id_usuario' => $request->user()?->id,
            ]);

            return response()->json(['message' => 'Não foi possível preparar o arquivo para download. Tente novamente.'], 500);
        }
    }
}
