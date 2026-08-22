<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    public function show(Request $request, string $cep): JsonResponse
    {
        $digits = preg_replace('/\D+/', '', $cep);

        if (strlen($digits) !== 8) {
            return response()->json([
                'message' => 'Informe um CEP válido com 8 números.',
            ], 422);
        }

        $address = $this->fromBrasilApi($digits) ?? $this->fromViaCep($digits);

        if (!$address) {
            return response()->json([
                'message' => 'CEP não encontrado.',
            ], 404);
        }

        return response()->json($address);
    }

    private function fromBrasilApi(string $cep): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->get("https://brasilapi.com.br/api/cep/v2/{$cep}");
        } catch (\Throwable) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $data = $response->json();

        return [
            'cep' => $cep,
            'logradouro' => $data['street'] ?? '',
            'bairro' => $data['neighborhood'] ?? '',
            'municipio' => $data['city'] ?? '',
            'uf' => $data['state'] ?? '',
            'codigo_ibge' => $data['city_ibge_code'] ?? null,
        ];
    }

    private function fromViaCep(string $cep): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->get("https://viacep.com.br/ws/{$cep}/json/");
        } catch (\Throwable) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $data = $response->json();
        if (!empty($data['erro'])) {
            return null;
        }

        return [
            'cep' => $cep,
            'logradouro' => $data['logradouro'] ?? '',
            'bairro' => $data['bairro'] ?? '',
            'municipio' => $data['localidade'] ?? '',
            'uf' => $data['uf'] ?? '',
            'codigo_ibge' => $data['ibge'] ?? null,
        ];
    }
}
