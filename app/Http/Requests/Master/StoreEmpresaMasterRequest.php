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
}
