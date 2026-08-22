<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Models\UsuarioAcesso;
use App\Services\JwtService;
use App\Services\EmpresaAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt, private readonly EmpresaAccessService $empresaAccess)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::query()
            ->with(['perfilAcesso.permissoes', 'permissoesDiretas'])
            ->where('email', $data['email'])
            ->first();

        if (!$user || !$user->active || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        if (!$user->id_perfil || !$user->perfilAcesso) {
            return response()->json([
                'message' => 'Seu perfil de acesso ainda não foi configurado. Procure o administrador do sistema.',
            ], 403);
        }

        $empresa = $user->master ? null : Empresa::query()->with('assinaturaVigente.plano')->find($user->id_empresa);
        $assinaturaBloqueada = false;

        if (!$user->master) {
            if (!$empresa || !$empresa->ativa) {
                return response()->json([
                    'message' => 'O acesso desta empresa está suspenso. Entre em contato com o suporte.',
                    'code' => 'empresa_suspensa',
                ], 403);
            }

            $assinaturaBloqueada = !$empresa->assinaturaVigente || !$empresa->assinaturaVigente->plano;
        }

        try {
            UsuarioAcesso::registrar($user, $request);
        } catch (Throwable $exception) {
            // Uma indisponibilidade momentânea da auditoria não deve impedir o login.
            Log::warning('Não foi possível registrar o acesso do usuário.', [
                'id_usuario' => $user->id_usuario,
                'exception' => $exception,
            ]);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'access_token' => $this->jwt->issue($user),
            'user' => array_merge($user->authPayload(), ['assinatura_bloqueada' => $assinaturaBloqueada]),
            'code' => $assinaturaBloqueada ? 'assinatura_bloqueada' : null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->loadMissing(['perfilAcesso.permissoes', 'permissoesDiretas'])->authPayload(),
        ]);
    }
}
