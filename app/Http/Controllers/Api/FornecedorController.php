<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveFornecedorRequest;
use App\Http\Resources\FornecedorResource;
use App\Models\Destinatario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class FornecedorController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $documento = preg_replace('/\D+/', '', $search);
        $query = Destinatario::query()->where('tipo', 'fornecedor')
            ->when($search !== '', fn ($q) => $q->where(function ($where) use ($search, $documento): void {
                $where->where('nome_razao_social', 'ilike', "%{$search}%");
                if ($documento !== '') $where->orWhere('documento', 'like', "%{$documento}%");
            }))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('nome_razao_social');

        return FornecedorResource::collection($query->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(SaveFornecedorRequest $request): JsonResponse
    {
        $fornecedor = Destinatario::create([...$request->validated(), 'id_empresa' => $request->user()->id_empresa, 'tipo' => 'fornecedor', 'ativo' => $request->boolean('ativo', true)]);
        return response()->json(['message' => 'Fornecedor cadastrado com sucesso.', 'data' => new FornecedorResource($fornecedor)], 201);
    }

    public function show(Destinatario $fornecedor): FornecedorResource
    {
        abort_unless($fornecedor->tipo === 'fornecedor', 404);
        return new FornecedorResource($fornecedor);
    }

    public function update(SaveFornecedorRequest $request, Destinatario $fornecedor): JsonResponse
    {
        abort_unless($fornecedor->tipo === 'fornecedor', 404);
        $fornecedor->update($request->validated());
        return response()->json(['message' => 'Fornecedor atualizado com sucesso.', 'data' => new FornecedorResource($fornecedor->refresh())]);
    }

    public function destroy(Destinatario $fornecedor): Response
    {
        abort_unless($fornecedor->tipo === 'fornecedor', 404);
        $fornecedor->delete();
        return response()->noContent();
    }
}
