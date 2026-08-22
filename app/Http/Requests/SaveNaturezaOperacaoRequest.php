<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveNaturezaOperacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn (string $field): ?string => preg_replace('/\D+/', '', (string) $this->input($field)) ?: null;
        $this->merge([
            'cfop_padrao' => $digits('cfop_padrao'),
            'csosn_padrao' => $digits('csosn_padrao'),
            'cst_padrao' => $digits('cst_padrao'),
            'tipo_movimento' => $this->input('tipo_movimento') === 'Saida' ? 'Saída' : $this->input('tipo_movimento'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'tipo_movimento' => ['required', 'in:Entrada,Saída'],
            'cfop_padrao' => ['required', 'digits:4'],
            'csosn_padrao' => ['nullable', 'string', 'max:4'],
            'cst_padrao' => ['nullable', 'string', 'max:3'],
            'calcula_impostos' => ['sometimes', 'boolean'],
            'calcula_icms' => ['sometimes', 'boolean'],
            'calcula_ipi' => ['sometimes', 'boolean'],
            'calcula_pis' => ['sometimes', 'boolean'],
            'calcula_cofins' => ['sometimes', 'boolean'],
            'informacoes_complementares' => ['nullable', 'string', 'max:1000'],
            'ativa' => ['sometimes', 'boolean'],
        ];
    }
}
