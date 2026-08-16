<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('naturezas_operacao', function (Blueprint $table): void {
            if (!Schema::hasColumn('naturezas_operacao', 'tipo_movimento')) {
                $table->string('tipo_movimento', 10)->default('Saída')->after('nome');
            }

            if (!Schema::hasColumn('naturezas_operacao', 'calcula_impostos')) {
                $table->boolean('calcula_impostos')->default(false)->after('calcula_cofins');
            }
        });
    }

    public function down(): void
    {
        Schema::table('naturezas_operacao', function (Blueprint $table): void {
            if (Schema::hasColumn('naturezas_operacao', 'calcula_impostos')) {
                $table->dropColumn('calcula_impostos');
            }

            if (Schema::hasColumn('naturezas_operacao', 'tipo_movimento')) {
                $table->dropColumn('tipo_movimento');
            }
        });
    }
};
