<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'ativo' => $this->boolean('ativo', true),
        ]);
    }

    public function rules(): array
    {
        $empresaId = (int) $this->user()->id_empresa;
        $usuario = $this->route('usuario');

        return [
            'nome' => ['required', 'string', 'min:2', 'max:120'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('usuario', 'email')->ignore($usuario?->id_usuario, 'id_usuario'),
            ],
            'id_perfil' => [
                'required',
                'integer',
                Rule::exists('perfil', 'id_perfil')->where('id_empresa', $empresaId),
            ],
            'ativo' => ['sometimes', 'boolean'],
            'permissoes_especificas' => ['sometimes', 'array'],
            'permissoes_especificas.*' => ['integer', Rule::exists('permissao', 'id_permissao')],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do usuário.',
            'email.required' => 'Informe o e-mail do usuário.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está vinculado a outro usuário.',
            'id_perfil.required' => 'Selecione um perfil de acesso.',
            'id_perfil.exists' => 'O perfil selecionado não pertence à sua empresa.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
