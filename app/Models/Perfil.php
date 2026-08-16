<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    use Tenantable, UsesCorporateNaming;

    protected $table = 'perfil';
    protected $primaryKey = 'id_perfil';

    protected $fillable = [
        'id_empresa',
        'nome',
        'slug',
    ];
    protected $appends = ['id', 'empresa_id'];

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(
            Permissao::class,
            'perfil_permissao',
            'id_perfil',
            'id_permissao',
            'id_perfil',
            'id_permissao',
        );
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_perfil', 'id_perfil');
    }
}
