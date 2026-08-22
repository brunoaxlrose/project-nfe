<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpresaMasterRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->master; }

    public function rules(): array
    {
        return [
            'razao_social' => ['required', 'string', 'max:120'],
            'nome_fantasia' => ['nullable', 'string', 'max:120'],
            'ativa' => ['required', 'boolean'],
            'id_plano' => ['required', 'integer', 'exists:plano,id_plano'],
            'status' => ['required', 'in:teste,ativa,suspensa,cancelada'],
            'inicia_em' => ['required', 'date'],
            'termina_em' => ['nullable', 'required_with:carencia_ate', 'date', 'after:inicia_em'],
            'carencia_ate' => ['nullable', 'date', 'after_or_equal:termina_em'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'razao_social.required' => 'Informe a razão social da empresa.',
            'ativa.required' => 'Informe se a empresa está ativa na plataforma.',
            'id_plano.required' => 'Selecione o plano da empresa.',
            'status.required' => 'Selecione o status da assinatura.',
            'inicia_em.required' => 'Informe o início da vigência.',
            'inicia_em.date' => 'Informe uma data válida para o início da vigência.',
            'termina_em.required_with' => 'Informe o fim da vigência quando houver carência.',
            'termina_em.date' => 'Informe uma data válida para o fim da vigência.',
            'termina_em.after' => 'O fim da vigência deve ser posterior ao início.',
            'carencia_ate.date' => 'Informe uma data válida para a carência.',
            'carencia_ate.after_or_equal' => 'A carência deve ser igual ou posterior ao fim da vigência.',
        ];
    }
}
