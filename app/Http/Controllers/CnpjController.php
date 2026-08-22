<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class CnpjController extends Controller
{
    public function show(string $cnpj): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cnpj);

        if (!$this->isValidCnpj($digits)) {
            return response()->json([
                'message' => 'Informe um CNPJ válido.',
            ], 422);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->get("https://brasilapi.com.br/api/cnpj/v1/{$digits}");
        } catch (\Throwable) {
            return response()->json([
                'message' => 'Não foi possível consultar o CNPJ agora.',
            ], 503);
        }

        if ($response->status() === 404) {
            return response()->json([
                'message' => 'CNPJ não encontrado na BrasilAPI.',
            ], 404);
        }

        if (!$response->ok()) {
            return response()->json([
                'message' => 'A BrasilAPI não retornou os dados do CNPJ.',
            ], 502);
        }

        $data = $response->json();

        return response()->json([
            'cnpj' => $digits,
            'razao_social' => $data['razao_social'] ?? '',
            'nome_fantasia' => $data['nome_fantasia'] ?? '',
            'cep' => preg_replace('/\D+/', '', (string) ($data['cep'] ?? '')),
            'logradouro' => trim(implode(' ', array_filter([
                $data['descricao_tipo_de_logradouro'] ?? null,
                $data['logradouro'] ?? null,
            ]))),
            'numero' => $data['numero'] ?? '',
            'complemento' => $data['complemento'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'municipio' => $data['municipio'] ?? '',
            'uf' => $data['uf'] ?? '',
            'codigo_ibge' => $data['codigo_municipio_ibge'] ?? null,
            'cnae' => $data['cnae_fiscal'] ?? null,
            'telefone' => $data['ddd_telefone_1'] ?? null,
            'email' => $data['email'] ?? null,
        ]);
    }

    private function isValidCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $firstDigit = $this->calculateDigit(substr($cnpj, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->calculateDigit(substr($cnpj, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $firstDigit === (int) $cnpj[12] && $secondDigit === (int) $cnpj[13];
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function calculateDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $base[$index] * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
