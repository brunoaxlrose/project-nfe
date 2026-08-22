<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsaasPagamento extends Model
{
    protected $table = 'asaas_pagamento';
    protected $primaryKey = 'id_asaas_pagamento';

    protected $fillable = [
        'id_empresa',
        'id_usuario',
        'id_plano',
        'asaas_customer_id',
        'asaas_payment_id',
        'external_reference',
        'valor',
        'payer_email',
        'status',
        'qr_code',
        'qr_code_base64',
        'pix_expira_em',
        'raw_response',
        'aprovado_em',
        'processado_em',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'pix_expira_em' => 'immutable_datetime',
        'raw_response' => 'array',
        'aprovado_em' => 'immutable_datetime',
        'processado_em' => 'immutable_datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class, 'id_plano', 'id_plano');
    }
}
