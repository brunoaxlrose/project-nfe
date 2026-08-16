<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use Tenantable, UsesCorporateNaming, SoftDeletes;

    protected $table = 'cliente';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_empresa',
        'razao_social',
        'documento',
        'inscricao_estadual',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'codigo_ibge',
        'uf',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected $appends = ['id', 'empresa_id'];
}
