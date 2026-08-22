<?php

namespace App\Http\Controllers;

use App\Http\Requests\Master\SavePlanoRequest;
use App\Http\Requests\Master\StoreEmpresaMasterRequest;
use App\Http\Requests\Master\UpdateEmpresaMasterRequest;
use App\Models\Empresa;
use App\Models\EmpresaAssinatura;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\RbacService;
use Database\Seeders\NaturezaOperacaoSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MasterController extends Controller
{
    public function __construct(private readonly RbacService $rbac, private readonly AssinaturaService $assinaturas) {}

    public function overview(): JsonResponse
    {
        $empresas = Empresa::query()
            ->with(['assinaturaAtual.plano', 'usuarios' => fn ($q) => $q->select('id_usuario', 'id_empresa', 'nome', 'email', 'ativo')])
            ->withCount('usuarios')
            ->orderBy('razao_social')
            ->get();

        return response()->json([
            'metricas' => [
                'empresas' => $empresas->count(),
                'ativas' => $empresas->where('ativa', true)->filter(fn (Empresa $e) => $e->assinaturaVigente()->exists())->count(),
                'suspensas' => $empresas->where('ativa', false)->count(),
                'vencendo_30_dias' => EmpresaAssinatura::query()->whereIn('status', ['teste', 'ativa'])->whereBetween('termina_em', [now(), now()->addDays(30)])->count(),
            ],
            'empresas' => $empresas->map(fn (Empresa $empresa) => $this->empresaPayload($empresa)),
            'planos' => Plano::query()->orderBy('valor_mensal')->orderBy('nome')->get(),
            'modulos' => ['nfe', 'clientes', 'fornecedores', 'produtos', 'naturezas', 'usuarios', 'configuracoes'],
        ]);
    }

    public function storeEmpresa(StoreEmpresaMasterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $empresa = DB::transaction(function () use ($data, $request): Empresa {
            $empresa = Empresa::query()->create([
                'razao_social' => $data['razao_social'], 'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'cnpj' => $data['cnpj'], 'ativa' => true,
            ]);
            $profiles = $this->rbac->provisionEmpresa($empresa);
            User::query()->create([
                'id_empresa' => $empresa->id_empresa, 'id_perfil' => $profiles->get('administrador')->id_perfil,
                'nome' => $data['admin_nome'], 'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']), 'perfil' => 'Administrador', 'ativo' => true, 'master' => false,
            ]);
            app(NaturezaOperacaoSeeder::class)->seedEmpresa($empresa->id_empresa);
            $this->assinaturas->substituir($empresa, Plano::query()->findOrFail($data['id_plano']), $data, $request->user());
            return $empresa;
        });

        return response()->json(['message' => 'Empresa, administrador e assinatura criados com sucesso.', 'empresa' => $this->empresaPayload($empresa->load('assinaturaAtual.plano')->loadCount('usuarios'))], 201);
    }

    public function updateEmpresa(UpdateEmpresaMasterRequest $request, Empresa $empresa): JsonResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $request, $empresa): void {
            $empresa->update(['razao_social' => $data['razao_social'], 'nome_fantasia' => $data['nome_fantasia'] ?? null, 'ativa' => $data['ativa']]);
            $this->assinaturas->substituir($empresa, Plano::query()->findOrFail($data['id_plano']), $data, $request->user());
        });
        return response()->json(['message' => 'Liberação da empresa atualizada com sucesso.', 'empresa' => $this->empresaPayload($empresa->fresh()->load('assinaturaAtual.plano')->loadCount('usuarios'))]);
    }

    public function storePlano(SavePlanoRequest $request): JsonResponse
    {
        $plano = Plano::query()->create($request->validated());
        return response()->json(['message' => 'Plano criado com sucesso.', 'plano' => $plano], 201);
    }

    public function updatePlano(SavePlanoRequest $request, Plano $plano): JsonResponse
    {
        $plano->update($request->validated());
        return response()->json(['message' => 'Plano atualizado com sucesso.', 'plano' => $plano]);
    }

    public function togglePlano(Plano $plano): JsonResponse
    {
        $plano->update(['ativo' => !$plano->ativo]);

        return response()->json([
            'message' => $plano->ativo ? 'Plano ativado com sucesso.' : 'Plano inativado para novas liberações.',
            'plano' => $plano,
        ]);
    }

    public function destroyPlano(Plano $plano): JsonResponse
    {
        if ($plano->assinaturas()->exists()) {
            throw ValidationException::withMessages([
                'plano' => 'Este plano já foi utilizado por uma empresa e não pode ser excluído. Você pode inativá-lo para impedir novas liberações.',
            ]);
        }

        $plano->delete();

        return response()->json(['message' => 'Plano excluído com sucesso.']);
    }

    private function empresaPayload(Empresa $empresa): array
    {
        $assinatura = $empresa->assinaturaAtual;
        return [
            'id' => $empresa->id_empresa, 'razao_social' => $empresa->razao_social, 'nome_fantasia' => $empresa->nome_fantasia,
            'cnpj' => $empresa->cnpj, 'ativa' => $empresa->ativa, 'usuarios_count' => $empresa->usuarios_count ?? $empresa->usuarios->count(),
            'administradores' => $empresa->relationLoaded('usuarios') ? $empresa->usuarios->where('ativo', true)->take(3)->values() : [],
            'assinatura' => $assinatura ? [
                'id' => $assinatura->id_empresa_assinatura, 'status' => $assinatura->status,
                'inicia_em' => $assinatura->inicia_em?->toIso8601String(), 'termina_em' => $assinatura->termina_em?->toIso8601String(),
                'carencia_ate' => $assinatura->carencia_ate?->toIso8601String(), 'observacoes' => $assinatura->observacoes,
                'plano' => $assinatura->plano,
            ] : null,
        ];
    }
}
