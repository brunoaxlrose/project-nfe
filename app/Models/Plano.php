<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plano extends Model
{
    protected $table = 'plano';
    protected $primaryKey = 'id_plano';
    protected $fillable = ['nome', 'slug', 'descricao', 'valor_mensal', 'limite_usuarios', 'duracao_dias', 'modulos', 'ativo'];
    protected $casts = ['valor_mensal' => 'decimal:2', 'limite_usuarios' => 'integer', 'duracao_dias' => 'integer', 'modulos' => 'array', 'ativo' => 'boolean'];

    public function assinaturas(): HasMany
    {
        return $this->hasMany(EmpresaAssinatura::class, 'id_plano', 'id_plano');
    }

    public function permiteModulo(string $modulo): bool
    {
        $modulos = $this->modulos ?? [];
        return in_array('*', $modulos, true) || in_array($modulo, $modulos, true);
    }
}
