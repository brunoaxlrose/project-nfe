<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveClienteRequest;
use App\Http\Resources\ClienteResource;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClienteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $documento = preg_replace('/\D+/', '', $search);
        $query = Cliente::query()
            ->when($search !== '', fn ($q) => $q->where(function ($where) use ($search, $documento): void {
                $where->where('razao_social', 'ilike', "%{$search}%");
                if ($documento !== '') $where->orWhere('documento', 'like', "%{$documento}%");
            }))
            ->when($request->has('ativo'), fn ($q) => $q->where('ativo', $request->boolean('ativo')))
            ->orderBy('razao_social');

        return ClienteResource::collection($query->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(SaveClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::create([...$request->validated(), 'id_empresa' => $request->user()->id_empresa, 'ativo' => $request->boolean('ativo', true)]);

        return response()->json(['message' => 'Cliente cadastrado com sucesso.', 'data' => new ClienteResource($cliente)], 201);
    }

    public function show(Cliente $cliente): ClienteResource
    {
        return new ClienteResource($cliente);
    }

    public function update(SaveClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return response()->json(['message' => 'Cliente atualizado com sucesso.', 'data' => new ClienteResource($cliente->refresh())]);
    }

    public function destroy(Cliente $cliente): Response
    {
        $cliente->delete();
        return response()->noContent();
    }
}
