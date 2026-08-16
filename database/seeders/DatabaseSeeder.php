<?php

use App\Models\User;
use App\Models\Empresa;
use Database\Seeders\NaturezaOperacaoSeeder;
use Database\Seeders\PermissaoSeeder;
use App\Models\Perfil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->firstOrFail();
        $this->call(PermissaoSeeder::class);
        $this->call(NaturezaOperacaoSeeder::class);

        foreach ([
            ['id_empresa' => $empresa->id, 'nome' => 'Administrador', 'email' => env('ADMIN_EMAIL'), 'password' => env('ADMIN_PASSWORD'), 'perfil' => 'Administrador', 'ativo' => true],
            ['id_empresa' => $empresa->id, 'nome' => 'Operador', 'email' => env('OPERATOR_EMAIL'), 'password' => env('OPERATOR_PASSWORD'), 'perfil' => 'Operador', 'ativo' => true],
        ] as $data) {
            if (!$data['email'] || !$data['password']) continue;
            $profileSlug = $data['perfil'] === 'Administrador' ? 'administrador' : 'operador';
            $profile = Perfil::withoutGlobalScopes()
                ->where('id_empresa', $empresa->id)
                ->where('slug', $profileSlug)
                ->firstOrFail();
            User::updateOrCreate(['email' => $data['email']], [
                'id_empresa' => $empresa->id,
                'id_perfil' => $profile->id,
                'nome' => $data['nome'],
                'password' => Hash::make($data['password']),
                'perfil' => $data['perfil'],
                'ativo' => true,
            ]);
        }
    }
}
