<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clientes')->where('documento', '11222333000181')->delete();
        DB::table('destinatarios')->where('documento', '11222333000181')->delete();
        DB::table('produtos')->where('codigo', 'CAMISA-001')->delete();
    }

    public function down(): void
    {
        // Dados de demonstração não são recriados ao desfazer a migration.
    }
};
