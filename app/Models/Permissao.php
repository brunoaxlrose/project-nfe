<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permissao extends Model
{
    use UsesCorporateNaming;

    protected $table = 'permissao';
    protected $primaryKey = 'id_permissao';

    protected $fillable = [
        'nome',
        'slug',
        'categoria',
    ];
    protected $appends = ['id'];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(
            Perfil::class,
            'perfil_permissao',
            'id_permissao',
            'id_perfil',
            'id_permissao',
            'id_perfil',
        );
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            Usuario::class,
            'usuario_permissao',
            'id_permissao',
            'id_usuario',
            'id_permissao',
            'id_usuario',
        );
    }
}
