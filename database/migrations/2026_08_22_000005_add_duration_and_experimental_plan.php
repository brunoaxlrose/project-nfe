<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plano', function (Blueprint $table): void {
            $table->unsignedInteger('duracao_dias')->nullable()->after('limite_usuarios');
        });

        DB::table('plano')->updateOrInsert(
            ['slug' => 'experimental'],
            [
                'nome' => 'Experimental',
                'descricao' => 'Acesso de demonstração válido por 1 dia após a liberação.',
                'valor_mensal' => 0,
                'limite_usuarios' => 1,
                'duracao_dias' => 1,
                'modulos' => json_encode(['*']),
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('plano')->where('slug', 'experimental')->delete();

        Schema::table('plano', function (Blueprint $table): void {
            $table->dropColumn('duracao_dias');
        });
    }
};
