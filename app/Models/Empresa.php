<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends Model
{
    use UsesCorporateNaming;

    protected $table = 'empresa';
    protected $primaryKey = 'id_empresa';
    protected $fillable = ['razao_social', 'nome_fantasia', 'cnpj', 'inscricao_estadual', 'crt', 'cep', 'logradouro', 'numero', 'bairro', 'municipio', 'codigo_municipio_ibge', 'uf', 'ativa'];
    protected $casts = ['ativa' => 'boolean'];
    protected $appends = ['id'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_empresa', 'id_empresa');
    }

    public function perfis(): HasMany
    {
        return $this->hasMany(Perfil::class, 'id_empresa', 'id_empresa');
    }

    public function configuracaoEmissor(): HasOne
    {
        return $this->hasOne(ConfiguracaoEmissor::class, 'id_empresa', 'id_empresa');
    }
}
