<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;

class NaturezaOperacao extends Model
{
    use Tenantable;
    protected $table = 'naturezas_operacao';
    protected $fillable = ['empresa_id', 'nome', 'tipo_movimento', 'cfop_padrao', 'csosn_padrao', 'cst_padrao', 'calcula_impostos', 'calcula_icms', 'calcula_ipi', 'calcula_pis', 'calcula_cofins', 'informacoes_complementares', 'ativa'];
    protected $casts = ['calcula_impostos' => 'boolean', 'calcula_icms' => 'boolean', 'calcula_ipi' => 'boolean', 'calcula_pis' => 'boolean', 'calcula_cofins' => 'boolean', 'ativa' => 'boolean'];
    protected $appends = ['descricao'];

    public function getDescricaoAttribute(): string
    {
        return (string) ($this->attributes['nome'] ?? '');
    }
}
