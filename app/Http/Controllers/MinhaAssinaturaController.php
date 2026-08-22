<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MinhaAssinaturaController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $empresa = Empresa::query()
            ->with('assinaturaAtual.plano')
            ->findOrFail($request->user()->id_empresa);
        $assinatura = $empresa->assinaturaAtual;
        $plano = $assinatura?->plano;

        return response()->json([
            'empresa' => [
                'razao_social' => $empresa->razao_social,
                'nome_fantasia' => $empresa->nome_fantasia,
            ],
            'assinatura' => $assinatura ? [
                'status' => $assinatura->status,
                'inicia_em' => $assinatura->inicia_em?->toIso8601String(),
                'termina_em' => $assinatura->termina_em?->toIso8601String(),
                'carencia_ate' => $assinatura->carencia_ate?->toIso8601String(),
                'dias_restantes' => $assinatura->termina_em
                    ? max(0, (int) now()->startOfDay()->diffInDays($assinatura->termina_em->startOfDay(), false))
                    : null,
            ] : null,
            'plano' => $plano ? [
                'nome' => $plano->nome,
                'descricao' => $plano->descricao,
                'valor_mensal' => $plano->valor_mensal,
                'limite_usuarios' => $plano->limite_usuarios,
                'modulos' => $plano->modulos ?? [],
            ] : null,
            'uso' => [
                'usuarios' => User::query()->where('id_empresa', $empresa->id_empresa)->where('ativo', true)->count(),
            ],
        ]);
    }
}
