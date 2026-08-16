<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Tenantable
{
    protected static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope());
        static::creating(function ($model): void {
            if (!$model->id_empresa && auth()->user()?->id_empresa) {
                $model->id_empresa = auth()->user()->id_empresa;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Empresa::class, 'id_empresa', 'id_empresa');
    }
}
