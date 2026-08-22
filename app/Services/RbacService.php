<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class RbacService
{
    public const PERMISSIONS = [
        ['nome' => 'Visualizar o início', 'slug' => 'menu.dashboard', 'categoria' => 'Menus'],
        ['nome' => 'Visualizar o módulo de NF-e', 'slug' => 'menu.nfe', 'categoria' => 'Menus'],
        ['nome' => 'Visualizar configurações', 'slug' => 'menu.configuracoes', 'categoria' => 'Menus'],
        ['nome' => 'Visualizar usuários e permissões', 'slug' => 'menu.usuarios', 'categoria' => 'Menus'],
        ['nome' => 'Consultar notas fiscais', 'slug' => 'nfe.visualizar', 'categoria' => 'NF-e'],
        ['nome' => 'Criar notas fiscais', 'slug' => 'nfe.criar', 'categoria' => 'NF-e'],
        ['nome' => 'Consultar retorno da SEFAZ', 'slug' => 'nfe.consultar', 'categoria' => 'NF-e'],
        ['nome' => 'Baixar XML e DANFE', 'slug' => 'nfe.baixar', 'categoria' => 'NF-e'],
        ['nome' => 'Clonar notas fiscais', 'slug' => 'nfe.clonar', 'categoria' => 'NF-e'],
        ['nome' => 'Cancelar notas fiscais', 'slug' => 'nfe.cancelar', 'categoria' => 'NF-e'],
        ['nome' => 'Emitir Carta de Correção', 'slug' => 'nfe.cce', 'categoria' => 'NF-e'],
        ['nome' => 'Consultar clientes e destinatários', 'slug' => 'clientes.visualizar', 'categoria' => 'Cadastros'],
        ['nome' => 'Cadastrar clientes e destinatários', 'slug' => 'clientes.criar', 'categoria' => 'Cadastros'],
        ['nome' => 'Editar clientes e destinatários', 'slug' => 'clientes.editar', 'categoria' => 'Cadastros'],
        ['nome' => 'Excluir clientes e destinatários', 'slug' => 'clientes.excluir', 'categoria' => 'Cadastros'],
        ['nome' => 'Consultar fornecedores', 'slug' => 'fornecedores.visualizar', 'categoria' => 'Cadastros'],
        ['nome' => 'Cadastrar fornecedores', 'slug' => 'fornecedores.criar', 'categoria' => 'Cadastros'],
        ['nome' => 'Editar fornecedores', 'slug' => 'fornecedores.editar', 'categoria' => 'Cadastros'],
        ['nome' => 'Excluir fornecedores', 'slug' => 'fornecedores.excluir', 'categoria' => 'Cadastros'],
        ['nome' => 'Consultar produtos', 'slug' => 'produtos.visualizar', 'categoria' => 'Cadastros'],
        ['nome' => 'Cadastrar produtos', 'slug' => 'produtos.criar', 'categoria' => 'Cadastros'],
        ['nome' => 'Editar produtos', 'slug' => 'produtos.editar', 'categoria' => 'Cadastros'],
        ['nome' => 'Excluir produtos', 'slug' => 'produtos.excluir', 'categoria' => 'Cadastros'],
        ['nome' => 'Consultar naturezas de operação', 'slug' => 'naturezas.visualizar', 'categoria' => 'Cadastros'],
        ['nome' => 'Cadastrar naturezas de operação', 'slug' => 'naturezas.criar', 'categoria' => 'Cadastros'],
        ['nome' => 'Editar naturezas de operação', 'slug' => 'naturezas.editar', 'categoria' => 'Cadastros'],
        ['nome' => 'Excluir naturezas de operação', 'slug' => 'naturezas.excluir', 'categoria' => 'Cadastros'],
        ['nome' => 'Consultar configurações da empresa', 'slug' => 'configuracoes.visualizar', 'categoria' => 'Configurações'],
        ['nome' => 'Alterar configurações da empresa', 'slug' => 'configuracoes.editar', 'categoria' => 'Configurações'],
        ['nome' => 'Gerenciar certificado digital', 'slug' => 'certificado.gerenciar', 'categoria' => 'Configurações'],
        ['nome' => 'Consultar usuários', 'slug' => 'usuarios.visualizar', 'categoria' => 'Usuários'],
        ['nome' => 'Cadastrar usuários', 'slug' => 'usuarios.criar', 'categoria' => 'Usuários'],
        ['nome' => 'Editar usuários', 'slug' => 'usuarios.editar', 'categoria' => 'Usuários'],
        ['nome' => 'Gerenciar perfis e permissões', 'slug' => 'perfis.gerenciar', 'categoria' => 'Usuários'],
    ];

    private const PROFILE_RULES = [
        'administrador' => '*',
        'operador' => [
            'menu.dashboard',
            'menu.nfe',
            'nfe.visualizar',
            'nfe.criar',
            'nfe.consultar',
            'nfe.baixar',
            'nfe.clonar',
            'clientes.visualizar',
            'clientes.criar',
            'clientes.editar',
            'clientes.excluir',
            'fornecedores.visualizar',
            'fornecedores.criar',
            'fornecedores.editar',
            'fornecedores.excluir',
            'produtos.visualizar',
            'produtos.criar',
            'produtos.editar',
            'produtos.excluir',
            'naturezas.visualizar',
            'naturezas.criar',
            'naturezas.editar',
            'naturezas.excluir',
        ],
        'faturamento' => [
            'menu.dashboard',
            'menu.nfe',
            'nfe.visualizar',
            'nfe.criar',
            'nfe.consultar',
            'nfe.baixar',
            'nfe.clonar',
            'clientes.visualizar',
            'clientes.criar',
            'clientes.editar',
            'clientes.excluir',
            'fornecedores.visualizar',
            'produtos.visualizar',
            'produtos.criar',
            'naturezas.visualizar',
            'naturezas.criar',
            'naturezas.editar',
        ],
    ];

    public function syncPermissions(): Collection
    {
        return collect(self::PERMISSIONS)->mapWithKeys(function (array $data): array {
            $permission = Permissao::query()->updateOrCreate(
                ['slug' => $data['slug']],
                ['nome' => $data['nome'], 'categoria' => $data['categoria']],
            );

            return [$permission->slug => $permission];
        });
    }

    public function provisionEmpresa(Empresa|int $empresa): Collection
    {
        $empresaId = $empresa instanceof Empresa ? $empresa->id : $empresa;
        $permissions = $this->syncPermissions();
        $profiles = collect();

        foreach (self::PROFILE_RULES as $slug => $rules) {
            $profile = Perfil::withoutGlobalScopes()->updateOrCreate(
                ['id_empresa' => $empresaId, 'slug' => $slug],
                ['nome' => Str::headline($slug)],
            );
            $allowed = $rules === '*' ? $permissions : $permissions->only($rules);
            $profile->permissoes()->sync($allowed->pluck('id')->all());
            $profiles->put($slug, $profile);
        }

        $this->linkLegacyUsers($empresaId, $profiles);

        return $profiles;
    }

    private function linkLegacyUsers(int $empresaId, Collection $profiles): void
    {
        User::query()
            ->where('id_empresa', $empresaId)
            ->whereNull('id_perfil')
            ->get()
            ->each(function (User $user) use ($profiles): void {
                $legacy = Str::slug((string) $user->perfil);
                $slug = $legacy === 'administrador' ? 'administrador' : 'operador';
                $user->forceFill(['id_perfil' => $profiles->get($slug)?->id])->save();
            });
    }
}
