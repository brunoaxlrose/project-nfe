<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Services\JwtService;
use Database\Seeders\NaturezaOperacaoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct(private readonly JwtService $jwt) {}
    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        try {
            [$empresa, $user] = DB::transaction(function () use ($data): array {
                $empresa = Empresa::create(['cnpj' => $data['cnpj'], 'razao_social' => $data['razao_social'], 'ativa' => true]);
                $user = User::create(['empresa_id' => $empresa->id, 'name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'perfil' => 'Administrador', 'active' => true]);
                app(NaturezaOperacaoSeeder::class)->seedEmpresa($empresa->id);
                return [$empresa, $user];
            });
        } catch (QueryException) {
            return response()->json(['message' => 'Não foi possível concluir o cadastro. O CNPJ ou e-mail pode já estar em uso.'], 422);
        }
        return response()->json(['message' => 'Empresa cadastrada com sucesso.', 'token_type' => 'Bearer', 'expires_in' => config('jwt.ttl') * 60, 'access_token' => $this->jwt->issue($user), 'user' => $user->only(['id', 'name', 'email', 'perfil', 'empresa_id']), 'empresa' => $empresa->only(['id', 'razao_social', 'cnpj'])], 201);
    }
}
