<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\NaturezaOperacao;
use Illuminate\Database\Seeder;

class NaturezaOperacaoSeeder extends Seeder
{
    /**
     * Naturezas padrão da operação. O campo descricao é persistido no campo
     * legado nome para manter compatibilidade com as migrations existentes.
     */
    public const PADROES = [
        ['descricao' => 'Compra de mercadoria', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1102', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Compra de mercadoria com ST', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1403', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'DEVOLUÇÃO', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1202', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Devolução de compra', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5202', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Devolução de compra com ST', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5411', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Devolução de venda', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1202', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Devolução de venda com ST', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1411', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Entrada de bonificação', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1910', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Importação de mercadoria', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '3102', 'csosn_padrao' => '0900', 'calcula_impostos' => true],
        ['descricao' => 'Remessa de mercadoria para conserto', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5915', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'Remessa de mercadoria para demonstração', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5912', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'Retorno de mercadoria enviada para conserto', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1916', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'Retorno de mercadoria enviada para demonstração', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1913', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'RETORNO SIMPLES REMESSA', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '1901', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'Saída em bonificação', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5910', 'csosn_padrao' => '0400', 'calcula_impostos' => false],
        ['descricao' => 'Transferência de comercialização', 'tipo_movimento' => 'Entrada', 'cfop_padrao' => '1152', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Transferência para comercialização', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5152', 'csosn_padrao' => '0900', 'calcula_impostos' => false],
        ['descricao' => 'Venda de mercadoria', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5102', 'csosn_padrao' => '0102', 'calcula_impostos' => false],
        ['descricao' => 'Venda de mercadoria a não contribuinte', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5102', 'csosn_padrao' => '0102', 'calcula_impostos' => false],
        ['descricao' => 'Venda de mercadoria com ST', 'tipo_movimento' => 'Saída', 'cfop_padrao' => '5405', 'csosn_padrao' => '0500', 'calcula_impostos' => false],
    ];

    public function run(): void
    {
        Empresa::query()->select('id_empresa')->orderBy('id_empresa')->each(function (Empresa $empresa): void {
            $this->seedEmpresa($empresa->id);
        });
    }

    public function seedEmpresa(int $empresaId): void
    {
        foreach (self::PADROES as $padrao) {
            NaturezaOperacao::withoutGlobalScopes()->updateOrCreate(
                ['id_empresa' => $empresaId, 'nome' => $padrao['descricao']],
                [
                    'tipo_movimento' => $padrao['tipo_movimento'],
                    'cfop_padrao' => $padrao['cfop_padrao'],
                    'csosn_padrao' => $padrao['csosn_padrao'],
                    'cst_padrao' => null,
                    'calcula_impostos' => $padrao['calcula_impostos'],
                    'calcula_icms' => $padrao['calcula_impostos'],
                    'calcula_ipi' => false,
                    'calcula_pis' => false,
                    'calcula_cofins' => false,
                    'informacoes_complementares' => null,
                    'ativa' => true,
                ],
            );
        }

        // Mantém disponível a operação de industrialização já utilizada pelo FiscalFlow.
        NaturezaOperacao::withoutGlobalScopes()->updateOrCreate(
            ['id_empresa' => $empresaId, 'nome' => 'Remessa de Mercadoria para Industrialização'],
            [
                'tipo_movimento' => 'Saída',
                'cfop_padrao' => '5901',
                'csosn_padrao' => '0400',
                'cst_padrao' => null,
                'calcula_impostos' => false,
                'calcula_icms' => false,
                'calcula_ipi' => false,
                'calcula_pis' => false,
                'calcula_cofins' => false,
                'informacoes_complementares' => 'Remessa para industrialização por encomenda.',
                'ativa' => true,
            ],
        );
    }
}
