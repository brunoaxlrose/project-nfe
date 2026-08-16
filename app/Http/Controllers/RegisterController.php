<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Services\JwtService;
use App\Services\RbacService;
use Database\Seeders\NaturezaOperacaoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly RbacService $rbac,
    ) {
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            [$empresa, $user] = DB::transaction(function () use ($data): array {
                $empresa = Empresa::create([
                    'cnpj' => $data['cnpj'],
                    'razao_social' => $data['razao_social'],
                    'ativa' => true,
                ]);
                $profiles = $this->rbac->provisionEmpresa($empresa);
                $administrator = $profiles->get('administrador');
                $user = User::create([
                    'id_empresa' => $empresa->id,
                    'id_perfil' => $administrator->id,
                    'nome' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'perfil' => 'Administrador',
                    'ativo' => true,
                ]);

                app(NaturezaOperacaoSeeder::class)->seedEmpresa($empresa->id);

                return [$empresa, $user->load('perfilAcesso.permissoes')];
            });
        } catch (QueryException) {
            return response()->json([
                'message' => 'Não foi possível concluir o cadastro. O CNPJ ou e-mail pode já estar em uso.',
            ], 422);
        }

        return response()->json([
            'message' => 'Empresa cadastrada com sucesso.',
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'access_token' => $this->jwt->issue($user),
            'user' => $user->authPayload(),
            'empresa' => [
                'id' => $empresa->id,
                'id_empresa' => $empresa->id_empresa,
                'razao_social' => $empresa->razao_social,
                'cnpj' => $empresa->cnpj,
            ],
        ], 201);
    }
}
