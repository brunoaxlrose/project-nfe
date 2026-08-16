<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('naturezas_operacao', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120)->unique();
            $table->string('cfop_padrao', 4);
            $table->string('csosn_padrao', 4)->nullable();
            $table->string('cst_padrao', 3)->nullable();
            $table->boolean('calcula_icms')->default(false);
            $table->boolean('calcula_ipi')->default(false);
            $table->boolean('calcula_pis')->default(false);
            $table->boolean('calcula_cofins')->default(false);
            $table->text('informacoes_complementares')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('naturezas_operacao'); }
};
