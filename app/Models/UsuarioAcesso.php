<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class UsuarioAcesso extends Model
{
    use Tenantable;

    protected $table = 'usuario_acesso';
    protected $primaryKey = 'id_usuario_acesso';

    protected $fillable = [
        'id_empresa',
        'id_usuario',
        'acessado_em',
        'endereco_ip',
        'dispositivo',
        'plataforma',
        'navegador',
        'idioma',
        'user_agent',
    ];

    protected $casts = [
        'acessado_em' => 'immutable_datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public static function registrar(User $usuario, Request $request): self
    {
        $userAgent = $request->userAgent();

        return static::query()->withoutGlobalScopes()->create([
            'id_empresa' => $usuario->id_empresa,
            'id_usuario' => $usuario->id_usuario,
            'acessado_em' => now(),
            'endereco_ip' => $request->ip(),
            'dispositivo' => self::identificarDispositivo($request, $userAgent),
            'plataforma' => self::identificarPlataforma($request, $userAgent),
            'navegador' => self::identificarNavegador($request, $userAgent),
            'idioma' => $request->header('Accept-Language'),
            'user_agent' => $userAgent,
        ]);
    }

    private static function identificarDispositivo(Request $request, ?string $userAgent): string
    {
        return match ($request->header('Sec-CH-UA-Mobile')) {
            '?1' => 'Celular/tablet',
            '?0' => 'Computador',
            default => preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent ?? '')
                ? 'Celular/tablet'
                : 'Computador',
        };
    }

    private static function identificarPlataforma(Request $request, ?string $userAgent): ?string
    {
        $clientHint = trim((string) $request->header('Sec-CH-UA-Platform'), '"');
        if ($clientHint !== '') {
            return $clientHint;
        }

        return match (true) {
            preg_match('/Windows/i', $userAgent ?? '') === 1 => 'Windows',
            preg_match('/Android/i', $userAgent ?? '') === 1 => 'Android',
            preg_match('/iPhone|iPad|iPod/i', $userAgent ?? '') === 1 => 'iOS',
            preg_match('/Mac OS|Macintosh/i', $userAgent ?? '') === 1 => 'macOS',
            preg_match('/Linux/i', $userAgent ?? '') === 1 => 'Linux',
            default => null,
        };
    }

    private static function identificarNavegador(Request $request, ?string $userAgent): ?string
    {
        $clientHint = $request->header('Sec-CH-UA');
        if ($clientHint) {
            return mb_substr($clientHint, 0, 100);
        }

        return match (true) {
            preg_match('/Edg\//i', $userAgent ?? '') === 1 => 'Microsoft Edge',
            preg_match('/OPR\//i', $userAgent ?? '') === 1 => 'Opera',
            preg_match('/Chrome\//i', $userAgent ?? '') === 1 => 'Google Chrome',
            preg_match('/Firefox\//i', $userAgent ?? '') === 1 => 'Mozilla Firefox',
            preg_match('/Safari\//i', $userAgent ?? '') === 1 => 'Safari',
            default => null,
        };
    }
}
