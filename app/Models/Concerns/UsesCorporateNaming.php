<?php

namespace App\Models\Concerns;

trait UsesCorporateNaming
{
    public function getIdAttribute(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getEmpresaIdAttribute(): mixed
    {
        return $this->getAttribute('id_empresa');
    }

    public function setEmpresaIdAttribute(mixed $value): void
    {
        $this->attributes['id_empresa'] = $value;
    }
}
