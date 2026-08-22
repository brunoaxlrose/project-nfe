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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $limite = $request->user()->empresa?->assinaturaVigente?->plano?->limite_usuarios;
        if (($data['ativo'] ?? true) && $limite !== null && Usuario::query()->where('id_empresa', $empresaId)->where('ativo', true)->count() >= $limite) {
            throw ValidationException::withMessages([
                'email' => "O plano atual permite no máximo {$limite} usuário(s). Altere o plano antes de adicionar outro usuário.",
            ]);
        }

        $criador = $request->user()->loadMissing('permissoesDiretas');
        $copiarAcessos = (bool) ($data['copiar_minhas_permissoes'] ?? true);
        $perfilId = $copiarAcessos ? (int) $criador->id_perfil : (int) $data['id_perfil'];
        $permissoesDiretas = $copiarAcessos
            ? $criador->permissoesDiretas->pluck('id_permissao')->all()
            : ($data['permissoes_especificas'] ?? []);

        $usuario = DB::transaction(function () use ($data, $empresaId, $perfilId, $permissoesDiretas): Usuario {
            $perfil = Perfil::query()->findOrFail($perfilId);
            $usuario = Usuario::query()->create([
                'id_empresa' => $empresaId,
                'id_perfil' => $perfilId,
                'nome' => $data['nome'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'perfil' => $perfil->nome,
                'ativo' => (bool) ($data['ativo'] ?? true),
            ]);

            $usuario->permissoesDiretas()->sync($permissoesDiretas);

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

        $vaiAtivar = (bool) ($data['ativo'] ?? true) && !$usuario->ativo;
        $limite = $request->user()->empresa?->assinaturaVigente?->plano?->limite_usuarios;
        if ($vaiAtivar && $limite !== null && Usuario::query()
            ->where('id_empresa', (int) $request->user()->id_empresa)
            ->where('ativo', true)
            ->count() >= $limite) {
            throw ValidationException::withMessages([
                'ativo' => "O plano atual permite no máximo {$limite} usuário(s) ativo(s). Desative outro acesso ou altere o plano antes de reativar este usuário.",
            ]);
        }

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
            ->withCount('usuarios')
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

    public function storePerfil(Request $request): JsonResponse
    {
        $data = $this->validarPerfil($request);

        $perfil = Perfil::query()->create([
            'id_empresa' => (int) $request->user()->id_empresa,
            'nome' => $data['nome'],
            'slug' => $this->slugPerfil($data['nome']),
        ]);

        return response()->json([
            'message' => 'Perfil cadastrado com sucesso.',
            'perfil' => $perfil->fresh(['permissoes'])->loadCount('usuarios'),
        ], 201);
    }

    public function updatePerfil(Request $request, Perfil $perfil): JsonResponse
    {
        $data = $this->validarPerfil($request, $perfil);

        $perfil->fill([
            'nome' => $data['nome'],
            'slug' => $this->slugPerfil($data['nome']),
        ])->save();

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'perfil' => $perfil->fresh(['permissoes'])->loadCount('usuarios'),
        ]);
    }

    public function destroyPerfil(Perfil $perfil): JsonResponse
    {
        if ($perfil->usuarios()->exists()) {
            throw ValidationException::withMessages([
                'perfil' => 'Este perfil possui usuários vinculados e não pode ser excluído.',
            ]);
        }

        $perfil->delete();

        return response()->json([
            'message' => 'Perfil excluído com sucesso.',
        ]);
    }

    public function adicionarUsuarioPerfil(Request $request, Perfil $perfil): JsonResponse
    {
        $empresaId = (int) $request->user()->id_empresa;
        $data = $request->validate([
            'id_usuario' => [
                'required',
                'integer',
                Rule::exists('usuario', 'id_usuario')->where('id_empresa', $empresaId),
            ],
        ], [
            'id_usuario.required' => 'Selecione um usuário para adicionar ao perfil.',
            'id_usuario.exists' => 'O usuário selecionado não pertence à sua empresa.',
        ]);

        $usuario = Usuario::query()->findOrFail($data['id_usuario']);
        $usuario->fill([
            'id_perfil' => $perfil->id_perfil,
            'perfil' => $perfil->nome,
        ])->save();

        return response()->json([
            'message' => 'Usuário adicionado ao perfil com sucesso.',
            'usuario' => $this->usuarioPayload($usuario->fresh(['perfilAcesso', 'permissoesDiretas'])),
        ]);
    }

    public function removerUsuarioPerfil(Perfil $perfil, Usuario $usuario): JsonResponse
    {
        if ((int) $usuario->id_perfil !== (int) $perfil->id_perfil) {
            throw ValidationException::withMessages([
                'usuario' => 'Este usuário não está vinculado ao perfil selecionado.',
            ]);
        }

        $usuario->fill([
            'id_perfil' => null,
            'perfil' => 'Sem perfil',
        ])->save();

        return response()->json([
            'message' => 'Usuário removido do perfil com sucesso.',
            'usuario' => $this->usuarioPayload($usuario->fresh(['perfilAcesso', 'permissoesDiretas'])),
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

    private function validarPerfil(Request $request, ?Perfil $perfil = null): array
    {
        $empresaId = (int) $request->user()->id_empresa;
        $slug = $this->slugPerfil((string) $request->input('nome'));

        $request->merge(['slug' => $slug]);

        return $request->validate([
            'nome' => ['required', 'string', 'min:2', 'max:80'],
            'slug' => [
                'required',
                'string',
                Rule::unique('perfil', 'slug')
                    ->where('id_empresa', $empresaId)
                    ->ignore($perfil?->id_perfil, 'id_perfil'),
            ],
        ], [
            'nome.required' => 'Informe o nome do perfil.',
            'nome.min' => 'O nome do perfil deve ter pelo menos 2 caracteres.',
            'slug.unique' => 'Já existe um perfil com este nome.',
        ]);
    }

    private function slugPerfil(string $nome): string
    {
        return Str::slug($nome);
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
