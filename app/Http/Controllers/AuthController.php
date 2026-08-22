<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\UsuarioAcesso;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt)
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
            'user' => $user->authPayload(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->loadMissing(['perfilAcesso.permissoes', 'permissoesDiretas'])->authPayload(),
        ]);
    }
}
