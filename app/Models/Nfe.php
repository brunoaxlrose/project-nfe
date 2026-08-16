<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nfe extends Model
{
    use Tenantable, UsesCorporateNaming, SoftDeletes;

    protected $table = 'nota_fiscal';
    protected $primaryKey = 'id_nota_fiscal';

    protected $fillable = [
        'id_empresa',
        'numero',
        'serie',
        'chave_acesso',
        'status',
        'recibo',
        'protocolo',
        'cstat',
        'xmotivo',
        'xml',
        'danfe_path',
        'payload',
        'id_usuario',
        'id_cliente',
        'id_destinatario',
        'id_natureza_operacao',
        'destinatario_documento',
        'data_cancelamento',
        'motivo_cancelamento',
    ];

    protected $casts = [
        'payload' => 'array',
        'data_cancelamento' => 'datetime',
    ];
    protected $appends = ['id', 'empresa_id', 'usuario_id', 'cliente_id', 'destinatario_id', 'natureza_operacao_id'];

    public function getUsuarioIdAttribute(): mixed
    {
        return $this->getAttribute('id_usuario');
    }

    public function getClienteIdAttribute(): mixed
    {
        return $this->getAttribute('id_cliente');
    }

    public function getDestinatarioIdAttribute(): mixed
    {
        return $this->getAttribute('id_destinatario');
    }

    public function getNaturezaOperacaoIdAttribute(): mixed
    {
        return $this->getAttribute('id_natureza_operacao');
    }

    public function getValorTotalAttribute(): float
    {
        $produtos = collect(data_get($this->payload, 'produtos', []))->sum(function (array $produto): float {
            return (float) ($produto['quantidade'] ?? 0) * (float) ($produto['valor_unitario'] ?? 0);
        });
        $desconto = (float) data_get($this->payload, 'desconto', 0);
        $outrasDespesas = (float) data_get($this->payload, 'outras_despesas', 0);

        return round(max(0, $produtos - $desconto + $outrasDespesas), 2);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    public function destinatario(): BelongsTo
    {
        return $this->belongsTo(Destinatario::class, 'id_destinatario', 'id_destinatario');
    }

    public function naturezaOperacao(): BelongsTo
    {
        return $this->belongsTo(NaturezaOperacao::class, 'id_natureza_operacao', 'id_natureza_operacao');
    }
}
