<?php

namespace App\Http\Controllers;

use App\Models\Destinatario;
use App\Models\NaturezaOperacao;
use App\Models\Cliente;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Database\Seeders\NaturezaOperacaoSeeder;

class CatalogController extends Controller
{
    public function destinatarios(): JsonResponse
    {
        return response()->json(Destinatario::query()->where('ativo', true)->orderBy('nome_razao_social')->get());
    }

    public function destinatariosBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        return response()->json(
            Destinatario::query()
                ->where('ativo', true)
                ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                    $search->where('nome_razao_social', 'ilike', '%'.$term.'%')
                        ->orWhere('documento', 'like', '%'.preg_replace('/\D+/', '', $term).'%');
                }))
                ->orderBy('nome_razao_social')
                ->limit(10)
                ->get(),
        );
    }

    public function naturezas(Request $request): JsonResponse
    {
        $empresaId = (int) $request->user()->empresa_id;
        $naturezas = NaturezaOperacao::query()
            ->where('ativa', true)
            ->orderBy('nome')
            ->get();

        if ($naturezas->isEmpty()) {
            app(NaturezaOperacaoSeeder::class)->seedEmpresa($empresaId);

            $naturezas = NaturezaOperacao::query()
                ->where('ativa', true)
                ->orderBy('nome')
                ->get();
        }

        return response()->json($naturezas);
    }

    public function clientesBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        return response()->json(Cliente::query()->where('ativo', true)->when($term !== '', fn ($q) => $q->where(fn ($query) => $query->where('razao_social', 'ilike', "%{$term}%")->orWhere('documento', 'like', "%{$term}%")))->orderBy('razao_social')->limit(10)->get());
    }

    public function importarCliente(Request $request): JsonResponse
    {
        $request->merge([
            'documento' => preg_replace('/\D/', '', (string) $request->input('documento', '')),
            'cep' => preg_replace('/\D/', '', (string) $request->input('cep', '')),
            'codigo_ibge' => preg_replace('/\D/', '', (string) $request->input('codigo_ibge', '')),
            'uf' => strtoupper((string) $request->input('uf', '')),
        ]);

        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'cidade' => ['nullable', 'string', 'max:60'],
            'codigo_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
        ]);

        $data['ativo'] = true;
        $data['empresa_id'] = $request->user()->empresa_id;

        $cliente = DB::transaction(function () use ($data): Cliente {
            return Cliente::query()->updateOrCreate(['documento' => $data['documento']], $data);
        });

        return response()->json($cliente);
    }

    public function produtosBuscar(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        return response()->json(Produto::query()->where('ativo', true)->when($term !== '', fn ($q) => $q->where(fn ($query) => $query->where('descricao', 'ilike', "%{$term}%")->orWhere('codigo', 'like', "%{$term}%")))->orderBy('descricao')->limit(10)->get());
    }
}
