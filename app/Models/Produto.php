<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use Tenantable, UsesCorporateNaming, SoftDeletes;

    protected $table = 'produto';
    protected $primaryKey = 'id_produto';

    protected $fillable = [
        'id_empresa',
        'codigo',
        'descricao',
        'ncm',
        'valor_unitario',
        'cfop',
        'csosn',
        'cst',
        'unidade',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'valor_unitario' => 'decimal:4',
    ];

    protected $appends = ['id', 'empresa_id'];
}
