<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePerfilPermissoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissoes' => ['required', 'array'],
            'permissoes.*' => ['integer', Rule::exists('permissao', 'id_permissao')],
        ];
    }

    public function messages(): array
    {
        return [
            'permissoes.required' => 'Selecione pelo menos uma permissão para o perfil.',
        ];
    }
}
