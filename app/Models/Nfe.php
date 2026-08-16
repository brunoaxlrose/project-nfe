<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Tenantable;

class Nfe extends Model
{
    use Tenantable;
    protected $table = 'nfes';
    protected $fillable = ['empresa_id', 'numero', 'serie', 'chave_acesso', 'status', 'recibo', 'protocolo', 'cstat', 'xmotivo', 'xml', 'danfe_path', 'payload', 'usuario_id', 'cliente_id', 'destinatario_id', 'natureza_operacao_id', 'destinatario_documento'];
    protected $casts = ['payload' => 'array'];

    public function getValorTotalAttribute(): float
    {
        $produtos = collect(data_get($this->payload, 'produtos', []))->sum(function (array $produto): float {
            return (float) ($produto['quantidade'] ?? 0) * (float) ($produto['valor_unitario'] ?? 0);
        });
        $desconto = (float) data_get($this->payload, 'desconto', 0);
        $outrasDespesas = (float) data_get($this->payload, 'outras_despesas', 0);

        return round(max(0, $produtos - $desconto + $outrasDespesas), 2);
    }

    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function destinatario() { return $this->belongsTo(Destinatario::class); }
    public function naturezaOperacao() { return $this->belongsTo(NaturezaOperacao::class, 'natureza_operacao_id'); }
}
