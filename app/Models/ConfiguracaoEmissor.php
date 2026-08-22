<?php

namespace App\Models;

use App\Models\Concerns\UsesCorporateNaming;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoEmissor extends Model
{
    use Tenantable, UsesCorporateNaming;

    protected $table = 'configuracao_emissor';
    protected $primaryKey = 'id_configuracao_emissor';

    protected $fillable = [
        'id_empresa',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'inscricao_estadual_isento',
        'inscricao_municipal',
        'cnae',
        'crt',
        'tamanho_empresa',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'municipio',
        'codigo_municipio_ibge',
        'uf',
        'telefone',
        'celular',
        'email',
        'logo_path',
        'certificado_base64',
        'certificado_storage_path',
        'certificado_senha',
        'certificado_validade',
        'certificado_subject',
        'certificado_issuer',
        'certificado_serial',
        'certificado_valid_from',
        'ambiente',
        'serie_padrao',
        'proximo_numero',
        'cfop_padrao',
        'csosn_padrao',
    ];
    protected $appends = ['id', 'empresa_id'];

    protected $casts = [
        'inscricao_estadual_isento' => 'boolean',
        'certificado_base64' => 'encrypted',
        'certificado_senha' => 'encrypted',
        'certificado_validade' => 'datetime',
        'certificado_valid_from' => 'datetime',
    ];

    public static function current(): self
    {
        $empresaId = auth()->user()?->id_empresa;

        return static::query()->firstOrCreate(['id_empresa' => $empresaId]);
    }
}
