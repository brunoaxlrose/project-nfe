<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProdutoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_produto,
            'codigo' => $this->codigo,
            'descricao' => $this->descricao,
            'ncm' => $this->ncm,
            'valor_unitario' => (float) $this->valor_unitario,
            'cfop' => $this->cfop,
            'csosn' => $this->csosn,
            'cst' => $this->cst,
            'unidade' => $this->unidade,
            'ativo' => (bool) $this->ativo,
        ];
    }
}
