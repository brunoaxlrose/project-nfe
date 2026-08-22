<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\User;

final class EmpresaAccessService
{
    public function bloqueio(User $user): ?string
    {
        if ($user->master) {
            return null;
        }

        $empresa = Empresa::query()->with('assinaturaVigente.plano')->find($user->id_empresa);
        if (!$empresa || !$empresa->ativa) {
            return 'O acesso desta empresa está suspenso. Entre em contato com o suporte.';
        }
        if (!$empresa->assinaturaVigente || !$empresa->assinaturaVigente->plano) {
            return 'O plano desta empresa está vencido ou suspenso. Entre em contato com o responsável pela assinatura.';
        }

        return null;
    }

    public function moduloPermitido(User $user, string $permission): bool
    {
        if ($user->master) {
            return true;
        }

        $modulo = match (true) {
            str_starts_with($permission, 'nfe.'), $permission === 'menu.nfe' => 'nfe',
            str_starts_with($permission, 'clientes.') => 'clientes',
            str_starts_with($permission, 'fornecedores.') => 'fornecedores',
            str_starts_with($permission, 'produtos.') => 'produtos',
            str_starts_with($permission, 'naturezas.') => 'naturezas',
            str_starts_with($permission, 'usuarios.'), str_starts_with($permission, 'perfis.'), $permission === 'menu.usuarios' => 'usuarios',
            str_starts_with($permission, 'configuracoes.'), str_starts_with($permission, 'certificado.'), $permission === 'menu.configuracoes' => 'configuracoes',
            default => null,
        };
        if ($modulo === null) {
            return true;
        }

        $plano = Empresa::query()->with('assinaturaVigente.plano')->find($user->id_empresa)?->assinaturaVigente?->plano;
        return $plano?->permiteModulo($modulo) ?? false;
    }
}
