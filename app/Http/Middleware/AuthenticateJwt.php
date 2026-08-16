<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization');
        if (!preg_match('/^Bearer\s+([^\s]+)$/i', $header, $matches)) {
            return response()->json([
                'message' => 'Sua sessão não foi encontrada. Entre novamente para continuar.',
            ], 401);
        }
        try {
            $user = $this->jwt->userFromToken($matches[1]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage() === 'Token expirado.'
                    ? 'Sua sessão expirou. Entre novamente para continuar.'
                    : 'Sua sessão não é mais válida. Entre novamente para continuar.',
            ], 401);
        }
        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);
        return $next($request);
    }
}
