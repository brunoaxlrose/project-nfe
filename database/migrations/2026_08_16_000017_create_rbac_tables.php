<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissoes', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 120);
            $table->string('slug', 120)->unique();
            $table->string('categoria', 60)->index();
            $table->timestamps();
        });

        Schema::create('perfis', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->string('nome', 80);
            $table->string('slug', 80);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
            $table->unique(['id', 'empresa_id'], 'perfis_id_empresa_unique');
        });

        Schema::create('perfil_permissao', function (Blueprint $table): void {
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $table->primary(['perfil_id', 'permissao_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('perfil_id')->nullable()->after('empresa_id');
            $table->foreign(
                ['perfil_id', 'empresa_id'],
                'users_perfil_empresa_foreign',
            )->references(['id', 'empresa_id'])->on('perfis')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign('users_perfil_empresa_foreign');
            $table->dropColumn('perfil_id');
        });

        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('perfis');
        Schema::dropIfExists('permissoes');
    }
};
