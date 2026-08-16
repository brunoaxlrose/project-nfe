<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table): void {
            $table->id();
            $table->string('razao_social', 120);
            $table->string('nome_fantasia', 120)->nullable();
            $table->string('cnpj', 14)->unique();
            $table->string('inscricao_estadual', 14)->nullable();
            $table->unsignedTinyInteger('crt')->default(1);
            $table->string('cep', 8)->nullable();
            $table->string('logradouro', 120)->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('bairro', 60)->nullable();
            $table->string('municipio', 60)->nullable();
            $table->string('codigo_municipio_ibge', 7)->nullable();
            $table->char('uf', 2)->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });

        $tables = ['users', 'clientes', 'produtos', 'nfes', 'configuracoes_emissor', 'destinatarios', 'naturezas_operacao'];
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('empresa_id')->nullable()->after('id');
            });
        }

        $empresaId = DB::table('empresas')->insertGetId([
            'razao_social' => env('NFE_RAZAO_SOCIAL', 'Empresa FiscalFlow'),
            'cnpj' => preg_replace('/\D+/', '', env('NFE_CNPJ', '00000000000000')),
            'inscricao_estadual' => env('NFE_IE'),
            'crt' => (int) env('NFE_CRT', 1),
            'uf' => env('NFE_UF', 'SP'),
            'ativa' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($tables as $tableName) {
            DB::table($tableName)->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('empresa_id')->references('id')->on('empresas')->restrictOnDelete();
                $table->index('empresa_id');
            });
        }

        Schema::table('users', function (Blueprint $table): void { $table->foreignId('empresa_id')->nullable(false)->change(); });
        foreach (['clientes', 'produtos', 'nfes', 'configuracoes_emissor', 'destinatarios', 'naturezas_operacao'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void { $table->foreignId('empresa_id')->nullable(false)->change(); });
        }

        Schema::table('clientes', function (Blueprint $table): void { $table->dropUnique('clientes_documento_unique'); $table->unique(['empresa_id', 'documento']); });
        Schema::table('produtos', function (Blueprint $table): void { $table->dropUnique('produtos_codigo_unique'); $table->unique(['empresa_id', 'codigo']); });
        Schema::table('destinatarios', function (Blueprint $table): void { $table->dropUnique('destinatarios_documento_unique'); $table->unique(['empresa_id', 'documento']); });
        Schema::table('naturezas_operacao', function (Blueprint $table): void { $table->dropUnique('naturezas_operacao_nome_unique'); $table->unique(['empresa_id', 'nome']); });
        Schema::table('configuracoes_emissor', function (Blueprint $table): void { $table->unique('empresa_id'); });
    }

    public function down(): void
    {
        foreach (['clientes', 'produtos', 'destinatarios', 'naturezas_operacao'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropUnique($tableName === 'clientes' ? 'clientes_empresa_id_documento_unique' : ($tableName === 'produtos' ? 'produtos_empresa_id_codigo_unique' : ($tableName === 'destinatarios' ? 'destinatarios_empresa_id_documento_unique' : 'naturezas_operacao_empresa_id_nome_unique')));
                $table->dropForeign(['empresa_id']); $table->dropIndex([$tableName . '_empresa_id_index']); $table->dropColumn('empresa_id');
            });
        }
        foreach (['users', 'nfes', 'configuracoes_emissor'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void { $table->dropForeign(['empresa_id']); $table->dropIndex([$tableName . '_empresa_id_index']); $table->dropColumn('empresa_id'); });
        }
        Schema::dropIfExists('empresas');
    }
};
