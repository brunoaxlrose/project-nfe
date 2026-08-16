<?php

namespace App\Models;

use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Model;

class ConfiguracaoEmissor extends Model
{
    use Tenantable;

    protected $table = 'configuracoes_emissor';

    protected $fillable = [
        'empresa_id',
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
        'cfop_padrao',
        'csosn_padrao',
    ];

    protected $casts = [
        'inscricao_estadual_isento' => 'boolean',
        'certificado_base64' => 'encrypted',
        'certificado_senha' => 'encrypted',
        'certificado_validade' => 'datetime',
        'certificado_valid_from' => 'datetime',
    ];

    public static function current(): self
    {
        $empresaId = auth()->user()?->empresa_id;

        return static::query()->firstOrCreate(['empresa_id' => $empresaId]);
    }
}
