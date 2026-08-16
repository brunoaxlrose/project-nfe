<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdatePerfilPermissoesRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuarios = Usuario::query()
            ->with([
                'perfilAcesso:id_perfil,nome,slug',
                'permissoesDiretas:id_permissao,nome,slug,categoria',
            ])
            ->orderBy('nome')
            ->get()
            ->map(fn (Usuario $usuario): array => $this->usuarioPayload($usuario));

        return response()->json($usuarios);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $empresaId = (int) $request->user()->id_empresa;
        $data = $request->validated();

        $usuario = DB::transaction(function () use ($data, $empresaId): Usuario {
            $perfil = Perfil::query()->findOrFail($data['id_perfil']);
            $usuario = Usuario::query()->create([
                'id_empresa' => $empresaId,
                'id_perfil' => $data['id_perfil'],
                'nome' => $data['nome'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'perfil' => $perfil->nome,
                'ativo' => (bool) ($data['ativo'] ?? true),
            ]);

            $usuario->permissoesDiretas()->sync($data['permissoes_especificas'] ?? []);

            return $usuario->load(['perfilAcesso', 'permissoesDiretas']);
        });

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso.',
            'usuario' => $this->usuarioPayload($usuario),
        ], 201);
    }

    public function update(UpdateUsuarioRequest $request, Usuario $usuario): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($usuario, $data): void {
            $perfil = Perfil::query()->findOrFail($data['id_perfil']);
            $attributes = [
                'id_perfil' => $data['id_perfil'],
                'nome' => $data['nome'],
                'email' => $data['email'],
                'perfil' => $perfil->nome,
                'ativo' => (bool) ($data['ativo'] ?? true),
            ];

            if (!empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $usuario->fill($attributes)->save();
            $usuario->permissoesDiretas()->sync($data['permissoes_especificas'] ?? []);
        });

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'usuario' => $this->usuarioPayload($usuario->fresh(['perfilAcesso', 'permissoesDiretas'])),
        ]);
    }

    public function perfis(): JsonResponse
    {
        $perfis = Perfil::query()
            ->with('permissoes:id_permissao,nome,slug,categoria')
            ->orderBy('nome')
            ->get();
        $permissoes = Permissao::query()
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get()
            ->groupBy('categoria')
            ->map(fn ($items) => $items->values())
            ->values();

        return response()->json([
            'perfis' => $perfis,
            'permissoes' => $permissoes,
        ]);
    }

    public function atualizarPermissoes(UpdatePerfilPermissoesRequest $request, Perfil $perfil): JsonResponse
    {
        $data = $request->validated();

        $perfil->permissoes()->sync($data['permissoes']);

        return response()->json([
            'message' => 'Permissões do perfil atualizadas com sucesso.',
            'perfil' => $perfil->fresh('permissoes'),
        ]);
    }

    private function usuarioPayload(Usuario $usuario): array
    {
        $usuario->loadMissing('perfilAcesso', 'permissoesDiretas');

        return [
            'id' => $usuario->id,
            'id_usuario' => $usuario->id_usuario,
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'ativo' => (bool) $usuario->ativo,
            'perfil' => $usuario->perfilAcesso ? [
                'id' => $usuario->perfilAcesso->id,
                'id_perfil' => $usuario->perfilAcesso->id_perfil,
                'nome' => $usuario->perfilAcesso->nome,
                'slug' => $usuario->perfilAcesso->slug,
            ] : null,
            'permissoes_especificas' => $usuario->permissoesDiretas
                ->map(fn (Permissao $permissao): array => [
                    'id' => $permissao->id,
                    'id_permissao' => $permissao->id_permissao,
                    'nome' => $permissao->nome,
                    'slug' => $permissao->slug,
                    'categoria' => $permissao->categoria,
                ])
                ->values(),
        ];
    }
}
