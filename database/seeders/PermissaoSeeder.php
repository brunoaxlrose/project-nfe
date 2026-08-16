<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Services\RbacService;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $rbac = app(RbacService::class);
        $rbac->syncPermissions();

        Empresa::query()->each(function (Empresa $empresa) use ($rbac): void {
            $rbac->provisionEmpresa($empresa);
        });
    }
}
