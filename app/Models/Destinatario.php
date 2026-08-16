<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;

class Destinatario extends Model
{
    use Tenantable, UsesCorporateNaming;

    protected $table = 'destinatario';
    protected $primaryKey = 'id_destinatario';

    protected $fillable = [
        'id_empresa',
        'nome_razao_social',
        'documento',
        'inscricao_estadual',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'municipio',
        'codigo_municipio_ibge',
        'uf',
        'tipo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    protected $appends = ['id', 'empresa_id'];
}
