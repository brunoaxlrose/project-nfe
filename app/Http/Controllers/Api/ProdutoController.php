<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\ConfiguracaoEmissor;
use App\Models\Produto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProdutoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $query = Produto::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($where) => $where
                ->where('descricao', 'ilike', "%{$search}%")
                ->orWhere('codigo', 'ilike', "%{$search}%")
                ->orWhere('ncm', 'like', '%'.preg_replace('/\D+/', '', $search).'%')))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('descricao');
        return ProdutoResource::collection($query->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(SaveProdutoRequest $request): JsonResponse
    {
        $data = $this->withDefaults($request->validated());
        if (Produto::query()->where('codigo', $data['codigo'])->exists()) {
            return response()->json(['message' => 'Este código já está cadastrado.', 'errors' => ['codigo' => ['Este código já está cadastrado.']]], 422);
        }
        $produto = Produto::create([...$data, 'id_empresa' => $request->user()->id_empresa, 'ativo' => $request->boolean('ativo', true)]);
        return response()->json(['message' => 'Produto cadastrado com sucesso.', 'data' => new ProdutoResource($produto)], 201);
    }

    public function show(Produto $produto): ProdutoResource
    {
        return new ProdutoResource($produto);
    }

    public function update(SaveProdutoRequest $request, Produto $produto): JsonResponse
    {
        $data = $this->withDefaults($request->validated());
        if (Produto::query()->where('codigo', $data['codigo'])->whereKeyNot($produto->getKey())->exists()) {
            return response()->json(['message' => 'Este código já está cadastrado.', 'errors' => ['codigo' => ['Este código já está cadastrado.']]], 422);
        }
        $produto->update($data);
        return response()->json(['message' => 'Produto atualizado com sucesso.', 'data' => new ProdutoResource($produto->refresh())]);
    }

    public function destroy(Produto $produto): Response
    {
        $produto->delete();
        return response()->noContent();
    }

    private function withDefaults(array $data): array
    {
        $config = ConfiguracaoEmissor::current();
        $data['cfop'] = $data['cfop'] ?: $config->cfop_padrao;
        $data['csosn'] = $data['csosn'] ?: $config->csosn_padrao;
        return $data;
    }
}
