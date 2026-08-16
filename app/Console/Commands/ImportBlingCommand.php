<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Destinatario;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportBlingCommand extends Command
{
    protected $signature = 'bling:import
        {contacts : Caminho do CSV de contatos exportado pelo Bling}
        {products : Caminho do CSV de produtos exportado pelo Bling}
        {--empresa=2 : ID da empresa que receberá os dados}
        {--dry-run : Apenas valida e apresenta o resumo, sem gravar no banco}';

    protected $description = 'Importa contatos e produtos do Bling para uma empresa do FiscalFlow';

    public function handle(): int
    {
        $empresaId = (int) $this->option('empresa');
        $contactsPath = (string) $this->argument('contacts');
        $productsPath = (string) $this->argument('products');

        $empresa = Empresa::query()->withoutGlobalScopes()->find($empresaId);
        $user = User::query()->where('empresa_id', $empresaId)->where('active', true)->first();

        if (!$empresa || !$user) {
            $this->error('Empresa ou usuário ativo não encontrado para o ID informado.');

            return self::FAILURE;
        }

        Auth::setUser($user);

        try {
            $contacts = $this->readCsv($contactsPath);
            $products = $this->readCsv($productsPath);
            $summary = $this->summarize($contacts, $products);

            $this->line('Contatos lidos: '.$summary['contacts_total']);
            $this->line('Clientes: '.$summary['clients_total']);
            $this->line('Fornecedores: '.$summary['suppliers_total']);
            $this->line('Produtos: '.$summary['products_total']);

            if ($summary['invalid_contacts'] || $summary['invalid_products'] || $summary['unknown_cities']) {
                $this->warn('Foram encontradas linhas que não podem ser importadas:');
                $this->line('Contatos inválidos: '.$summary['invalid_contacts']);
                $this->line('Produtos inválidos: '.$summary['invalid_products']);
                $this->line('Municípios sem código IBGE: '.$summary['unknown_cities']);

                return self::FAILURE;
            }

            if ($this->option('dry-run')) {
                $this->info('Dry-run concluído. Nenhum registro foi gravado.');

                return self::SUCCESS;
            }

            $result = DB::transaction(function () use ($contacts, $products, $empresaId): array {
                return [
                    'clients' => $this->importClients($contacts, $empresaId),
                    'suppliers' => $this->importSuppliers($contacts, $empresaId),
                    'products' => $this->importProducts($products, $empresaId),
                ];
            });

            $this->info('Importação concluída para a empresa '.$empresaId.'.');
            $this->line('Clientes inseridos/atualizados: '.$result['clients']);
            $this->line('Fornecedores inseridos/atualizados: '.$result['suppliers']);
            $this->line('Produtos inseridos/atualizados: '.$result['products']);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Importação cancelada: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function readCsv(string $path): array
    {
        if (!is_readable($path)) {
            throw new RuntimeException('Arquivo não encontrado ou sem permissão de leitura: '.$path);
        }

        $handle = fopen($path, 'rb');
        $headers = fgetcsv($handle, 0, ';');

        if ($handle === false || $headers === false) {
            throw new RuntimeException('Não foi possível ler o cabeçalho do arquivo: '.$path);
        }

        $headers = array_map(fn ($header): string => $this->clean((string) $header), $headers);
        $rows = [];

        while (($values = fgetcsv($handle, 0, ';')) !== false) {
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            $values = array_pad($values, count($headers), '');
            $row = [];

            foreach ($headers as $index => $header) {
                $row[$header] = $this->clean((string) ($values[$index] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function summarize(array $contacts, array $products): array
    {
        $clients = array_filter($contacts, fn (array $row): bool => trim($row['Tipo contato'] ?? '') === 'Cliente');
        $suppliers = array_filter($contacts, fn (array $row): bool => trim($row['Tipo contato'] ?? '') !== 'Cliente');
        $invalidContacts = 0;
        $unknownCities = 0;

        foreach ($contacts as $row) {
            if (!$this->document($row['CNPJ / CPF'] ?? '') || !$this->cityCode($row['Cidade'] ?? '', $row['UF'] ?? '')) {
                $invalidContacts++;
            }

            if (!$this->cityCode($row['Cidade'] ?? '', $row['UF'] ?? '')) {
                $unknownCities++;
            }
        }

        $invalidProducts = count(array_filter($products, function (array $row): bool {
            $ncm = preg_replace('/\D+/', '', $row['NCM'] ?? '');

            return trim($row['Código'] ?? '') === ''
                || trim($row['Descrição'] ?? '') === ''
                || strlen($ncm) !== 8;
        }));

        return [
            'contacts_total' => count($contacts),
            'clients_total' => count($clients),
            'suppliers_total' => count($suppliers),
            'products_total' => count($products),
            'invalid_contacts' => $invalidContacts,
            'invalid_products' => $invalidProducts,
            'unknown_cities' => $unknownCities,
        ];
    }

    private function importClients(array $contacts, int $empresaId): int
    {
        $count = 0;

        foreach ($contacts as $row) {
            if (trim($row['Tipo contato'] ?? '') !== 'Cliente') {
                continue;
            }

            $documento = $this->document($row['CNPJ / CPF'] ?? '');
            $data = $this->clientData($row, $empresaId, $documento);
            Cliente::query()->updateOrCreate(['documento' => $documento], $data);
            $count++;
        }

        return $count;
    }

    private function importSuppliers(array $contacts, int $empresaId): int
    {
        $count = 0;

        foreach ($contacts as $row) {
            if (trim($row['Tipo contato'] ?? '') === 'Cliente') {
                continue;
            }

            $documento = $this->document($row['CNPJ / CPF'] ?? '');
            $data = $this->supplierData($row, $empresaId, $documento);
            Destinatario::query()->updateOrCreate(['documento' => $documento], $data);
            $count++;
        }

        return $count;
    }

    private function importProducts(array $products, int $empresaId): int
    {
        $count = 0;

        foreach ($products as $row) {
            $codigo = trim($row['Código'] ?? '');
            $ncm = preg_replace('/\D+/', '', $row['NCM'] ?? '');
            $unidade = mb_strtoupper(trim($row['Unidade'] ?? 'UN')) ?: 'UN';

            Produto::query()->updateOrCreate(
                ['codigo' => $codigo],
                [
                    'empresa_id' => $empresaId,
                    'descricao' => mb_substr(trim($row['Descrição'] ?? ''), 0, 120),
                    'ncm' => $ncm,
                    'valor_unitario' => $this->decimal($row['Preço'] ?? '0'),
                    'cfop' => null,
                    'csosn' => null,
                    'cst' => null,
                    'unidade' => mb_substr($unidade, 0, 6),
                    'ativo' => mb_strtolower(trim($row['Situação'] ?? '')) === 'ativo',
                ],
            );
            $count++;
        }

        return $count;
    }

    private function clientData(array $row, int $empresaId, string $documento): array
    {
        return [
            'empresa_id' => $empresaId,
            'razao_social' => mb_substr(trim($row['Nome'] ?? ''), 0, 120),
            'documento' => $documento,
            'inscricao_estadual' => $this->nullable($row['IE / RG'] ?? ''),
            'cep' => $this->digits($row['CEP'] ?? ''),
            'logradouro' => mb_substr(trim($row['Endereço'] ?? ''), 0, 120),
            'numero' => mb_substr(trim($row['Número'] ?? ''), 0, 20),
            'complemento' => $this->nullable($row['Complemento'] ?? ''),
            'bairro' => mb_substr(trim($row['Bairro'] ?? ''), 0, 60),
            'cidade' => mb_substr(trim($row['Cidade'] ?? ''), 0, 60),
            'codigo_ibge' => $this->cityCode($row['Cidade'] ?? '', $row['UF'] ?? ''),
            'uf' => mb_strtoupper(trim($row['UF'] ?? '')),
            'ativo' => mb_strtolower(trim($row['Situação'] ?? '')) === 'ativo',
        ];
    }

    private function supplierData(array $row, int $empresaId, string $documento): array
    {
        return [
            'empresa_id' => $empresaId,
            'nome_razao_social' => mb_substr(trim($row['Nome'] ?? ''), 0, 120),
            'documento' => $documento,
            'inscricao_estadual' => $this->nullable($row['IE / RG'] ?? ''),
            'cep' => $this->digits($row['CEP'] ?? ''),
            'logradouro' => mb_substr(trim($row['Endereço'] ?? ''), 0, 120),
            'numero' => mb_substr(trim($row['Número'] ?? ''), 0, 20),
            'complemento' => $this->nullable($row['Complemento'] ?? ''),
            'bairro' => mb_substr(trim($row['Bairro'] ?? ''), 0, 60),
            'municipio' => mb_substr(trim($row['Cidade'] ?? ''), 0, 60),
            'codigo_municipio_ibge' => $this->cityCode($row['Cidade'] ?? '', $row['UF'] ?? ''),
            'uf' => mb_strtoupper(trim($row['UF'] ?? '')),
            'tipo' => 'fornecedor',
            'ativo' => mb_strtolower(trim($row['Situação'] ?? '')) === 'ativo',
        ];
    }

    private function cityCode(string $city, string $uf): ?string
    {
        $key = Str::upper(Str::ascii(trim($city))).'|'.Str::upper(trim($uf));

        return [
            'SAO PAULO|SP' => '3550308',
            'GUARULHOS|SP' => '3518800',
            'ITAPEVI|SP' => '3522505',
            'CONTAGEM|MG' => '3118601',
            'SALVADOR|BA' => '2927408',
        ][$key] ?? null;
    }

    private function document(string $value): string
    {
        return $this->digits($value);
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value));
    }

    private function decimal(string $value): float
    {
        $value = trim($value);

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $value));
    }
}
