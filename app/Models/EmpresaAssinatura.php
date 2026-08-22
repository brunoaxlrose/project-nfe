<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaAssinatura extends Model
{
    protected $table = 'empresa_assinatura';
    protected $primaryKey = 'id_empresa_assinatura';
    protected $fillable = ['id_empresa', 'id_plano', 'status', 'inicia_em', 'termina_em', 'carencia_ate', 'cancelada_em', 'observacoes', 'criada_por'];
    protected $casts = ['inicia_em' => 'immutable_datetime', 'termina_em' => 'immutable_datetime', 'carencia_ate' => 'immutable_datetime', 'cancelada_em' => 'immutable_datetime'];

    public function empresa(): BelongsTo { return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa'); }
    public function plano(): BelongsTo { return $this->belongsTo(Plano::class, 'id_plano', 'id_plano'); }

    public function scopeVigente(Builder $query): Builder
    {
        $agora = now();
        return $query->whereIn('status', ['teste', 'ativa'])
            ->where('inicia_em', '<=', $agora)
            ->where(fn (Builder $q) => $q->whereNull('termina_em')->orWhere('termina_em', '>=', $agora)->orWhere('carencia_ate', '>=', $agora));
    }
}
