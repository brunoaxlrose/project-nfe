<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveNaturezaOperacaoRequest;
use App\Http\Resources\NaturezaOperacaoResource;
use App\Models\NaturezaOperacao;
use Database\Seeders\NaturezaOperacaoSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NaturezaOperacaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if (!NaturezaOperacao::query()->exists()) {
            app(NaturezaOperacaoSeeder::class)->seedEmpresa((int) $request->user()->id_empresa);
        }
        $search = trim((string) $request->query('search', ''));
        $query = NaturezaOperacao::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($where) => $where->where('nome', 'ilike', "%{$search}%")->orWhere('cfop_padrao', 'like', "%{$search}%")))
            ->when(!$request->boolean('all', true), fn ($q) => $q->where('ativa', true))
            ->orderBy('nome');
        if (!$request->hasAny(['page', 'per_page', 'search'])) {
            return NaturezaOperacaoResource::collection($query->get());
        }

        return NaturezaOperacaoResource::collection($query->paginate($request->integer('per_page', 15))->withQueryString());
    }

    public function store(SaveNaturezaOperacaoRequest $request): JsonResponse
    {
        $natureza = NaturezaOperacao::create([...$request->validated(), 'id_empresa' => $request->user()->id_empresa, 'ativa' => $request->boolean('ativa', true)]);
        return response()->json(['message' => 'Natureza cadastrada com sucesso.', 'data' => new NaturezaOperacaoResource($natureza)], 201);
    }

    public function show(NaturezaOperacao $natureza): NaturezaOperacaoResource
    {
        return new NaturezaOperacaoResource($natureza);
    }

    public function update(SaveNaturezaOperacaoRequest $request, NaturezaOperacao $natureza): JsonResponse
    {
        $natureza->update($request->validated());
        return response()->json(['message' => 'Natureza atualizada com sucesso.', 'data' => new NaturezaOperacaoResource($natureza->refresh())]);
    }

    public function destroy(NaturezaOperacao $natureza): Response
    {
        $natureza->delete();
        return response()->noContent();
    }
}
