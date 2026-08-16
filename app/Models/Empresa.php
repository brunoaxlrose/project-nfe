<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['razao_social', 'nome_fantasia', 'cnpj', 'inscricao_estadual', 'crt', 'cep', 'logradouro', 'numero', 'bairro', 'municipio', 'codigo_municipio_ibge', 'uf', 'ativa'];
    protected $casts = ['ativa' => 'boolean'];

    public function usuarios() { return $this->hasMany(User::class); }
    public function configuracaoEmissor() { return $this->hasOne(ConfiguracaoEmissor::class); }
}
