<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_emissor', function (Blueprint $table): void {
            $table->string('nome_fantasia', 120)->nullable()->after('razao_social');
            $table->boolean('inscricao_estadual_isento')->default(false)->after('inscricao_estadual');
            $table->string('inscricao_municipal', 20)->nullable()->after('inscricao_estadual_isento');
            $table->string('cnae', 10)->nullable()->after('inscricao_municipal');
            $table->string('tamanho_empresa', 10)->nullable()->after('crt');
            $table->string('complemento', 60)->nullable()->after('numero');
            $table->string('telefone', 20)->nullable()->after('uf');
            $table->string('celular', 20)->nullable()->after('telefone');
            $table->string('email', 120)->nullable()->after('celular');
            $table->string('logo_path', 255)->nullable()->after('email');
            $table->string('certificado_storage_path', 255)->nullable()->after('certificado_base64');
            $table->text('certificado_subject')->nullable()->after('certificado_validade');
            $table->text('certificado_issuer')->nullable()->after('certificado_subject');
            $table->string('certificado_serial', 128)->nullable()->after('certificado_issuer');
            $table->timestamp('certificado_valid_from')->nullable()->after('certificado_serial');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes_emissor', function (Blueprint $table): void {
            $table->dropColumn([
                'nome_fantasia',
                'inscricao_estadual_isento',
                'inscricao_municipal',
                'cnae',
                'tamanho_empresa',
                'complemento',
                'telefone',
                'celular',
                'email',
                'logo_path',
                'certificado_storage_path',
                'certificado_subject',
                'certificado_issuer',
                'certificado_serial',
                'certificado_valid_from',
            ]);
        });
    }
};
