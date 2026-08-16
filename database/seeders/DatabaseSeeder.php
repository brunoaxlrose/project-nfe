<?php

use App\Models\User;
use App\Models\Empresa;
use Database\Seeders\NaturezaOperacaoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->firstOrFail();
        $this->call(NaturezaOperacaoSeeder::class);

        foreach ([
            ['empresa_id' => $empresa->id, 'name' => 'Administrador', 'email' => env('ADMIN_EMAIL'), 'password' => env('ADMIN_PASSWORD'), 'perfil' => 'Administrador'],
            ['empresa_id' => $empresa->id, 'name' => 'Operador', 'email' => env('OPERATOR_EMAIL'), 'password' => env('OPERATOR_PASSWORD'), 'perfil' => 'Operador'],
        ] as $data) {
            if (!$data['email'] || !$data['password']) continue;
            User::updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'], 'password' => Hash::make($data['password']), 'perfil' => $data['perfil'], 'active' => true,
            ]);
        }
    }
}
