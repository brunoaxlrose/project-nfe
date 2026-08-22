<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\EmpresaAssinatura;
use App\Models\Plano;
use App\Models\User;
use Carbon\CarbonImmutable;

final class AssinaturaService
{
    public function substituir(Empresa $empresa, Plano $plano, array $dados, ?User $master = null): EmpresaAssinatura
    {
        EmpresaAssinatura::query()
            ->where('id_empresa', $empresa->id_empresa)
            ->whereIn('status', ['teste', 'ativa'])
            ->update(['status' => 'substituida', 'cancelada_em' => now(), 'updated_at' => now()]);

        $inicio = CarbonImmutable::parse($dados['inicia_em']);
        $termino = $plano->duracao_dias
            ? $inicio->addDays($plano->duracao_dias)
            : ($dados['termina_em'] ?? null);
        $carencia = $plano->duracao_dias ? null : ($dados['carencia_ate'] ?? null);

        return EmpresaAssinatura::query()->create([
            'id_empresa' => $empresa->id_empresa,
            'id_plano' => $plano->id_plano,
            'status' => $dados['status'],
            'inicia_em' => $inicio,
            'termina_em' => $termino,
            'carencia_ate' => $carencia,
            'observacoes' => $dados['observacoes'] ?? null,
            'criada_por' => $master?->id_usuario,
        ]);
    }
}
