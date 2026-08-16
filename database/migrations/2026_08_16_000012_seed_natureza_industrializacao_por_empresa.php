<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $agora = now();

        DB::table('empresas')->select('id')->orderBy('id')->get()->each(
            function (object $empresa) use ($agora): void {
                DB::table('naturezas_operacao')->updateOrInsert(
                    [
                        'empresa_id' => $empresa->id,
                        'nome' => 'Remessa de Mercadoria para Industrialização',
                    ],
                    [
                        'cfop_padrao' => '5901',
                        'csosn_padrao' => '0400',
                        'cst_padrao' => null,
                        'calcula_icms' => false,
                        'calcula_ipi' => false,
                        'calcula_pis' => false,
                        'calcula_cofins' => false,
                        'informacoes_complementares' => 'Remessa para industrialização por encomenda.',
                        'ativa' => true,
                        'updated_at' => $agora,
                        'created_at' => $agora,
                    ]
                );
            }
        );
    }

    public function down(): void
    {
        DB::table('naturezas_operacao')
            ->where('nome', 'Remessa de Mercadoria para Industrialização')
            ->delete();
    }
};
