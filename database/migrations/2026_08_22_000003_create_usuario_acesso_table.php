<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_acesso', function (Blueprint $table): void {
            $table->bigIncrements('id_usuario_acesso');
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_usuario');
            $table->timestampTz('acessado_em')->useCurrent();
            $table->ipAddress('endereco_ip')->nullable();
            $table->string('dispositivo', 30)->nullable();
            $table->string('plataforma', 100)->nullable();
            $table->string('navegador', 100)->nullable();
            $table->string('idioma', 255)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampsTz();

            $table->foreign('id_empresa', 'usuario_acesso_fk_empresa')
                ->references('id_empresa')
                ->on('empresa')
                ->cascadeOnDelete();
            $table->foreign('id_usuario', 'usuario_acesso_fk_usuario')
                ->references('id_usuario')
                ->on('usuario')
                ->restrictOnDelete();

            $table->index(['id_empresa', 'acessado_em'], 'usuario_acesso_empresa_data_idx');
            $table->index(['id_usuario', 'acessado_em'], 'usuario_acesso_usuario_data_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_acesso');
    }
};
