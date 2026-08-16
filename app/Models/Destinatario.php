<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;

class Destinatario extends Model
{
    use Tenantable;
    protected $fillable = ['empresa_id', 'nome_razao_social', 'documento', 'inscricao_estadual', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'municipio', 'codigo_municipio_ibge', 'uf', 'tipo', 'ativo'];
    protected $casts = ['ativo' => 'boolean'];
}
