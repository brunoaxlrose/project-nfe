<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario', function (Blueprint $table): void {
            $table->boolean('master')->default(false)->after('ativo')->index();
        });

        Schema::create('plano', function (Blueprint $table): void {
            $table->bigIncrements('id_plano');
            $table->string('nome', 80);
            $table->string('slug', 80)->unique();
            $table->text('descricao')->nullable();
            $table->decimal('valor_mensal', 12, 2)->default(0);
            $table->unsignedInteger('limite_usuarios')->nullable();
            $table->json('modulos');
            $table->boolean('ativo')->default(true)->index();
            $table->timestampsTz();
        });

        Schema::create('empresa_assinatura', function (Blueprint $table): void {
            $table->bigIncrements('id_empresa_assinatura');
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_plano');
            $table->string('status', 30)->default('ativa')->index();
            $table->timestampTz('inicia_em');
            $table->timestampTz('termina_em')->nullable();
            $table->timestampTz('carencia_ate')->nullable();
            $table->timestampTz('cancelada_em')->nullable();
            $table->text('observacoes')->nullable();
            $table->unsignedBigInteger('criada_por')->nullable();
            $table->timestampsTz();

            $table->foreign('id_empresa', 'empresa_assinatura_fk_empresa')->references('id_empresa')->on('empresa')->cascadeOnDelete();
            $table->foreign('id_plano', 'empresa_assinatura_fk_plano')->references('id_plano')->on('plano')->restrictOnDelete();
            $table->foreign('criada_por', 'empresa_assinatura_fk_criador')->references('id_usuario')->on('usuario')->nullOnDelete();
            $table->index(['id_empresa', 'status', 'inicia_em'], 'empresa_assinatura_empresa_status_idx');
        });

        $planoId = DB::table('plano')->insertGetId([
            'nome' => 'Legado completo',
            'slug' => 'legado-completo',
            'descricao' => 'Plano criado automaticamente para preservar o acesso das empresas existentes.',
            'valor_mensal' => 0,
            'limite_usuarios' => null,
            'modulos' => json_encode(['*']),
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_plano');

        foreach (DB::table('empresa')->pluck('id_empresa') as $empresaId) {
            DB::table('empresa_assinatura')->insert([
                'id_empresa' => $empresaId,
                'id_plano' => $planoId,
                'status' => 'ativa',
                'inicia_em' => now(),
                'termina_em' => null,
                'observacoes' => 'Migração automática sem interrupção de acesso.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_assinatura');
        Schema::dropIfExists('plano');
        Schema::table('usuario', fn (Blueprint $table) => $table->dropColumn('master'));
    }
};
