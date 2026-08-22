<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/*
 * Validação do cadastro público desativada junto com o RegisterController.
 *
class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['cnpj' => preg_replace('/\D+/', '', (string) $this->input('cnpj')), 'email' => mb_strtolower(trim((string) $this->input('email')))]); }
    public function rules(): array { return ['cnpj' => ['required', 'digits:14', 'unique:empresa,cnpj'], 'razao_social' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'min:2', 'max:120'], 'email' => ['required', 'email:rfc', 'max:255', 'unique:usuario,email'], 'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()]]; }
    public function messages(): array { return ['cnpj.required' => 'Informe o CNPJ da empresa.', 'cnpj.digits' => 'Informe um CNPJ válido com 14 números.', 'cnpj.unique' => 'Este CNPJ já possui uma empresa cadastrada.', 'razao_social.required' => 'Informe a razão social da empresa.', 'name.required' => 'Informe o nome do administrador.', 'email.required' => 'Informe o e-mail do administrador.', 'email.email' => 'Informe um e-mail válido.', 'email.unique' => 'Este e-mail já está em uso.', 'password.confirmed' => 'A confirmação da senha não confere.']; }
}
*/
