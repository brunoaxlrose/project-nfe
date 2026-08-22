<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NaturezaOperacaoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_natureza_operacao,
            'nome' => $this->nome,
            'tipo_movimento' => $this->tipo_movimento,
            'cfop_padrao' => $this->cfop_padrao,
            'csosn_padrao' => $this->csosn_padrao,
            'cst_padrao' => $this->cst_padrao,
            'calcula_impostos' => (bool) $this->calcula_impostos,
            'calcula_icms' => (bool) $this->calcula_icms,
            'calcula_ipi' => (bool) $this->calcula_ipi,
            'calcula_pis' => (bool) $this->calcula_pis,
            'calcula_cofins' => (bool) $this->calcula_cofins,
            'informacoes_complementares' => $this->informacoes_complementares,
            'ativa' => (bool) $this->ativa,
        ];
    }
}
