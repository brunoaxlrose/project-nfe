<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use RuntimeException;

final class JwtService
{
    public function issue(User $user): string
    {
        $now = now()->timestamp;
        return JWT::encode([
            'iss' => config('jwt.issuer'), 'sub' => (string) $user->id,
            'role' => $user->perfil, 'empresa_id' => (string) $user->empresa_id, 'iat' => $now,
            'exp' => $now + (config('jwt.ttl') * 60), 'jti' => (string) Str::uuid(),
        ], $this->secret(), config('jwt.algorithm'));
    }

    public function userFromToken(string $token): User
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret(), config('jwt.algorithm')));
        } catch (ExpiredException $e) {
            throw new RuntimeException('Token expirado.', previous: $e);
        } catch (\Throwable $e) {
            throw new RuntimeException('Token inválido.', previous: $e);
        }

        $user = User::query()->find($claims->sub ?? null);
        if (!$user || !$user->active || ($claims->role ?? null) !== $user->perfil || (string) ($claims->empresa_id ?? '') !== (string) $user->empresa_id) {
            throw new RuntimeException('Usuário inválido ou inativo.');
        }
        return $user;
    }

    private function secret(): string
    {
        $file = config('jwt.secret_file');
        $secret = $file && is_readable($file) ? trim((string) file_get_contents($file)) : config('jwt.secret');
        if (!$secret || strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET deve ter pelo menos 32 caracteres ou ser fornecido via JWT_SECRET_FILE.');
        }
        return $secret;
    }
}
