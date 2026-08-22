<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, Tenantable, UsesCorporateNaming, SoftDeletes;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_empresa',
        'id_perfil',
        'nome',
        'email',
        'password',
        'perfil',
        'ativo',
        'master',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'ativo' => 'boolean',
        'master' => 'boolean',
    ];
    protected $appends = ['id', 'empresa_id', 'perfil_id', 'name', 'active'];

    public function getPerfilIdAttribute(): mixed
    {
        return $this->getAttribute('id_perfil');
    }

    public function setPerfilIdAttribute(mixed $value): void
    {
        $this->attributes['id_perfil'] = $value;
    }

    public function getNameAttribute(): mixed
    {
        return $this->getAttribute('nome');
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['nome'] = $value;
    }

    public function getActiveAttribute(): mixed
    {
        return $this->getAttribute('ativo');
    }

    public function setActiveAttribute(mixed $value): void
    {
        $this->attributes['ativo'] = $value;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function perfilAcesso(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'id_perfil', 'id_perfil');
    }

    public function permissoesDiretas(): BelongsToMany
    {
        return $this->belongsToMany(
            Permissao::class,
            'usuario_permissao',
            'id_usuario',
            'id_permissao',
            'id_usuario',
            'id_permissao',
        );
    }

    public function acessos(): HasMany
    {
        return $this->hasMany(UsuarioAcesso::class, 'id_usuario', 'id_usuario');
    }

    public function temPermissao(string $slug): bool
    {
        if ($this->master) {
            return true;
        }
        if (!$this->active || !$this->id_perfil) {
            return false;
        }

        $profile = $this->perfilAcesso;

        if (!$profile || (int) $profile->id_empresa !== (int) $this->id_empresa) {
            return false;
        }

        if ($profile->slug === 'administrador' || $this->perfil === 'Administrador') {
            return true;
        }

        $this->loadMissing('permissoesDiretas');
        $profile->loadMissing('permissoes');

        return $profile->permissoes->contains('slug', $slug)
            || $this->permissoesDiretas->contains('slug', $slug);
    }

    public function temAlgumaPermissao(array $slugs): bool
    {
        return collect($slugs)->contains(fn (string $slug): bool => $this->temPermissao($slug));
    }

    public function permissoesSlugs(): array
    {
        if ($this->master) {
            return collect(\App\Services\RbacService::PERMISSIONS)->pluck('slug')->push('master.gerenciar')->all();
        }
        $profile = $this->perfilAcesso;

        if (!$profile || (int) $profile->id_empresa !== (int) $this->id_empresa) {
            return [];
        }

        if ($profile->slug === 'administrador' || $this->perfil === 'Administrador') {
            return collect(\App\Services\RbacService::PERMISSIONS)->pluck('slug')->all();
        }

        $this->loadMissing('permissoesDiretas');
        $profile->loadMissing('permissoes');

        return $profile->permissoes
            ->pluck('slug')
            ->merge($this->permissoesDiretas->pluck('slug'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function authPayload(): array
    {
        $profile = $this->perfilAcesso;
        $ambienteSefaz = (int) (ConfiguracaoEmissor::query()
            ->withoutGlobalScopes()
            ->where('id_empresa', $this->id_empresa)
            ->value('ambiente') ?: 2);

        return [
            'id' => $this->id_usuario,
            'empresa_id' => $this->id_empresa,
            'perfil_id' => $this->id_perfil,
            'id_empresa' => $this->id_empresa,
            'id_perfil' => $this->id_perfil,
            'name' => $this->nome,
            'email' => $this->email,
            'perfil' => $profile?->nome ?: $this->perfil,
            'perfil_slug' => $profile?->slug,
            'permissions' => $this->permissoesSlugs(),
            'ambiente_sefaz' => $ambienteSefaz,
            'is_master' => (bool) $this->master,
            'modulos_plano' => $this->master
                ? ['*']
                : ($this->empresa?->assinaturaVigente?->plano?->modulos ?? []),
        ];
    }
}
