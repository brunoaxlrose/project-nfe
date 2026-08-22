<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'documento' => preg_replace('/\D+/', '', (string) $this->input('documento')),
            'cep' => preg_replace('/\D+/', '', (string) $this->input('cep')) ?: null,
            'codigo_municipio_ibge' => preg_replace('/\D+/', '', (string) $this->input('codigo_municipio_ibge')) ?: null,
            'uf' => strtoupper(trim((string) $this->input('uf'))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome_razao_social' => ['required', 'string', 'max:120'],
            'documento' => ['required', 'digits_between:11,14'],
            'inscricao_estadual' => ['nullable', 'string', 'max:14'],
            'cep' => ['nullable', 'digits:8'],
            'logradouro' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:120'],
            'bairro' => ['nullable', 'string', 'max:60'],
            'municipio' => ['nullable', 'string', 'max:60'],
            'codigo_municipio_ibge' => ['nullable', 'digits:7'],
            'uf' => ['nullable', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
