<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nfes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero');
            $table->unsignedSmallInteger('serie')->default(1);
            $table->string('chave_acesso', 44)->nullable()->unique();
            $table->string('status', 30)->default('rascunho')->index();
            $table->string('recibo', 20)->nullable();
            $table->string('protocolo', 20)->nullable();
            $table->unsignedInteger('cstat')->nullable();
            $table->text('xmotivo')->nullable();
            $table->longText('xml')->nullable();
            $table->string('danfe_path')->nullable();
            $table->jsonb('payload');
            $table->timestamps();
            $table->index(['numero', 'serie']);
        });
    }

    public function down(): void { Schema::dropIfExists('nfes'); }
};
