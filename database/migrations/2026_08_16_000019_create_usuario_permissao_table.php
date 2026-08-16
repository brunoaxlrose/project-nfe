<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuario_permissao', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_permissao');

            $table->primary(['id_usuario', 'id_permissao'], 'usuario_permissao_pk');

            $table->foreign('id_usuario', 'usuario_permissao_fk_usuario')
                ->references('id_usuario')
                ->on('usuario')
                ->cascadeOnDelete();

            $table->foreign('id_permissao', 'usuario_permissao_fk_permissao')
                ->references('id_permissao')
                ->on('permissao')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_permissao');
    }
};
