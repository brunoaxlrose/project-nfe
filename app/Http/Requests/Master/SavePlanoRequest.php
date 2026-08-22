<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SavePlanoRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->master; }

    protected function prepareForValidation(): void
    {
        $nome = trim((string) $this->input('nome'));
        $slugInformado = trim((string) $this->input('slug'));

        $this->merge([
            'nome' => $nome,
            'slug' => Str::slug($slugInformado !== '' ? $slugInformado : $nome),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('plano')?->id_plano;
        return [
            'nome' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('plano', 'slug')->ignore($id, 'id_plano')],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'valor_mensal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'limite_usuarios' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'duracao_dias' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'modulos' => ['required', 'array', 'min:1'],
            'modulos.*' => ['required', Rule::in(['*', 'nfe', 'clientes', 'fornecedores', 'produtos', 'naturezas', 'usuarios', 'configuracoes'])],
            'ativo' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do plano.',
            'nome.max' => 'O nome do plano deve ter no máximo 80 caracteres.',
            'slug.required' => 'Informe um nome válido para gerar o identificador do plano.',
            'slug.alpha_dash' => 'Não foi possível gerar um identificador válido para o plano.',
            'slug.unique' => 'Já existe um plano com esse nome ou identificador.',
            'valor_mensal.required' => 'Informe o valor mensal do plano.',
            'valor_mensal.numeric' => 'Informe um valor mensal válido.',
            'limite_usuarios.integer' => 'O limite de usuários deve ser um número inteiro.',
            'limite_usuarios.min' => 'O limite deve permitir pelo menos um usuário.',
            'duracao_dias.integer' => 'A duração padrão deve ser informada em dias inteiros.',
            'duracao_dias.min' => 'A duração padrão deve ser de pelo menos um dia.',
            'modulos.required' => 'Selecione pelo menos um módulo para o plano.',
            'modulos.min' => 'Selecione pelo menos um módulo para o plano.',
        ];
    }
}
