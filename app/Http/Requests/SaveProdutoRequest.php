<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $onlyDigits = fn (string $field): ?string => preg_replace('/\D+/', '', (string) $this->input($field)) ?: null;
        $this->merge([
            'ncm' => $onlyDigits('ncm'),
            'cfop' => $onlyDigits('cfop'),
            'csosn' => $onlyDigits('csosn'),
            'cst' => $onlyDigits('cst'),
            'unidade' => strtoupper(trim((string) $this->input('unidade'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:60'],
            'descricao' => ['required', 'string', 'max:120'],
            'ncm' => ['required', 'digits:8'],
            'valor_unitario' => ['required', 'numeric', 'min:0.01'],
            'cfop' => ['nullable', 'digits:4'],
            'csosn' => ['nullable', 'string', 'max:4'],
            'cst' => ['nullable', 'string', 'max:3'],
            'unidade' => ['required', 'string', 'max:6'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor_unitario.required' => 'Informe o valor unitário do produto.',
            'valor_unitario.numeric' => 'Informe um valor unitário válido.',
            'valor_unitario.min' => 'Informe um valor unitário maior que zero.',
        ];
    }
}
