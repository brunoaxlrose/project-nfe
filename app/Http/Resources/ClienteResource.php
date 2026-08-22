<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_cliente,
            'razao_social' => $this->razao_social,
            'documento' => $this->documento,
            'inscricao_estadual' => $this->inscricao_estadual,
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'codigo_ibge' => $this->codigo_ibge,
            'uf' => $this->uf,
            'ativo' => (bool) $this->ativo,
        ];
    }
}
