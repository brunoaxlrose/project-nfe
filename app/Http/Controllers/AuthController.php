<?php

namespace App\Http\Controllers;

use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string', 'max:200']]);
        $user = \App\Models\User::query()->where('email', $data['email'])->first();
        if (!$user || !$user->active || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }
        return response()->json(['token_type' => 'Bearer', 'expires_in' => config('jwt.ttl') * 60, 'access_token' => $this->jwt->issue($user), 'user' => $user->only(['id', 'name', 'email', 'perfil'])]);
    }
}
