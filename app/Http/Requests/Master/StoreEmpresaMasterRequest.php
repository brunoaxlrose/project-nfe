<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmpresaMasterRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->master; }

    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:120'],
            'nome_fantasia' => ['nullable', 'string', 'max:120'],
            'cnpj' => ['required', 'digits:14', 'unique:empresa,cnpj'],
            'id_plano' => ['required', 'integer', 'exists:plano,id_plano'],
            'status' => ['required', 'in:teste,ativa,suspensa'],
            'inicia_em' => ['required', 'date'],
            'termina_em' => ['nullable', 'required_with:carencia_ate', 'date', 'after:inicia_em'],
            'carencia_ate' => ['nullable', 'date', 'after_or_equal:termina_em'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'admin_nome' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:usuario,email'],
            'admin_password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ];
    }

    public function messages(): array
    {
        return [
            'razao_social.required' => 'Informe a razão social da empresa.',
            'cnpj.required' => 'Informe o CNPJ da empresa.',
            'cnpj.digits' => 'Informe um CNPJ válido com 14 números.',
            'cnpj.unique' => 'Este CNPJ já possui uma empresa cadastrada.',
            'id_plano.required' => 'Selecione o plano da empresa.',
            'status.required' => 'Selecione o status da assinatura.',
            'inicia_em.required' => 'Informe o início da vigência.',
            'inicia_em.date' => 'Informe uma data válida para o início da vigência.',
            'termina_em.required_with' => 'Informe o fim da vigência quando houver carência.',
            'termina_em.date' => 'Informe uma data válida para o fim da vigência.',
            'termina_em.after' => 'O fim da vigência deve ser posterior ao início.',
            'carencia_ate.date' => 'Informe uma data válida para a carência.',
            'carencia_ate.after_or_equal' => 'A carência deve ser igual ou posterior ao fim da vigência.',
            'admin_nome.required' => 'Informe o nome do administrador.',
            'admin_email.required' => 'Informe o e-mail do administrador.',
            'admin_email.email' => 'Informe um e-mail válido para o administrador.',
            'admin_email.unique' => 'Este e-mail já está vinculado a outro usuário.',
            'admin_password.required' => 'Informe uma senha temporária.',
            'admin_password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
