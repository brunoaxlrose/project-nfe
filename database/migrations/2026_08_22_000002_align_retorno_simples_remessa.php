<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('natureza_operacao')
            ->whereRaw('UPPER(nome) = ?', ['RETORNO SIMPLES REMESSA'])
            ->update(['tipo_movimento' => 'Saída', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('natureza_operacao')
            ->whereRaw('UPPER(nome) = ?', ['RETORNO SIMPLES REMESSA'])
            ->update(['tipo_movimento' => 'Entrada', 'updated_at' => now()]);
    }
};
