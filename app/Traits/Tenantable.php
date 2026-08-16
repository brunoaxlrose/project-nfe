<?php

namespace App\Traits;

use App\Scopes\TenantScope;

trait Tenantable
{
    protected static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope());
        static::creating(function ($model): void {
            if (!$model->empresa_id && auth()->user()?->empresa_id) {
                $model->empresa_id = auth()->user()->empresa_id;
            }
        });
    }

    public function empresa()
    {
        return $this->belongsTo(\App\Models\Empresa::class);
    }
}
