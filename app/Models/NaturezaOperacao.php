<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;

class NaturezaOperacao extends Model
{
    use Tenantable, UsesCorporateNaming;

    protected $table = 'natureza_operacao';
    protected $primaryKey = 'id_natureza_operacao';

    protected $fillable = [
        'id_empresa',
        'nome',
        'tipo_movimento',
        'cfop_padrao',
        'csosn_padrao',
        'cst_padrao',
        'calcula_impostos',
        'calcula_icms',
        'calcula_ipi',
        'calcula_pis',
        'calcula_cofins',
        'informacoes_complementares',
        'ativa',
    ];

    protected $casts = [
        'calcula_impostos' => 'boolean',
        'calcula_icms' => 'boolean',
        'calcula_ipi' => 'boolean',
        'calcula_pis' => 'boolean',
        'calcula_cofins' => 'boolean',
        'ativa' => 'boolean',
    ];

    protected $appends = ['id', 'empresa_id', 'descricao'];

    public function getDescricaoAttribute(): string
    {
        return (string) ($this->attributes['nome'] ?? '');
    }
}
