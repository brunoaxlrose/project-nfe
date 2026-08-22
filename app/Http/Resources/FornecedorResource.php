<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FornecedorResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_destinatario,
            'nome_razao_social' => $this->nome_razao_social,
            'documento' => $this->documento,
            'inscricao_estadual' => $this->inscricao_estadual,
            'cep' => $this->cep,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'municipio' => $this->municipio,
            'codigo_municipio_ibge' => $this->codigo_municipio_ibge,
            'uf' => $this->uf,
            'ativo' => (bool) $this->ativo,
        ];
    }
}
