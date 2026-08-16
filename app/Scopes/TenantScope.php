<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();
        if ($user?->id_empresa) {
            $builder->where($builder->getQuery()->from . '.id_empresa', $user->id_empresa);
        }
    }
}
