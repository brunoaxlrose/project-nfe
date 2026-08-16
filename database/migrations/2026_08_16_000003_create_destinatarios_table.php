<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('destinatarios', function (Blueprint $table): void {
            $table->id();
            $table->string('nome_razao_social', 120);
            $table->string('documento', 14)->unique();
            $table->string('inscricao_estadual', 14)->nullable();
            $table->string('cep', 8);
            $table->string('logradouro', 120);
            $table->string('numero', 20);
            $table->string('complemento', 60)->nullable();
            $table->string('bairro', 60);
            $table->string('municipio', 60);
            $table->string('codigo_municipio_ibge', 7);
            $table->char('uf', 2);
            $table->string('tipo', 20)->default('parceiro');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index(['ativo', 'nome_razao_social']);
        });
    }

    public function down(): void { Schema::dropIfExists('destinatarios'); }
};
