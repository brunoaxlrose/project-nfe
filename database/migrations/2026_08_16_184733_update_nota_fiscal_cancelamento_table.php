<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nota_fiscal', function (Blueprint $table) {
            // Se as colunas já não existirem, adiciona. O status já existe em 2026_08_15_000000_create_nfes_table.php como varchar(30).
            // Vamos adicionar data_cancelamento, motivo_cancelamento e a coluna deleted_at para soft delete de rascunhos.
            if (!Schema::hasColumn('nota_fiscal', 'data_cancelamento')) {
                $table->timestamp('data_cancelamento')->nullable();
            }
            if (!Schema::hasColumn('nota_fiscal', 'motivo_cancelamento')) {
                $table->text('motivo_cancelamento')->nullable();
            }
            if (!Schema::hasColumn('nota_fiscal', 'deleted_at')) {
                $table->softDeletes('deleted_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nota_fiscal', function (Blueprint $table) {
            $table->dropColumn(['data_cancelamento', 'motivo_cancelamento']);
            $table->dropSoftDeletes('deleted_at');
        });
    }
};
