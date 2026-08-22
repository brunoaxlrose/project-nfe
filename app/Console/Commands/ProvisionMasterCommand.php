<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\EmpresaAssinatura;
use App\Models\Plano;
use App\Models\User;
use App\Services\RbacService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProvisionMasterCommand extends Command
{
    protected $signature = 'fiscalflow:provision-master {--rotate-password : Atualiza a senha mesmo se a conta já existir}';
    protected $description = 'Cria ou atualiza, de forma idempotente, a conta MASTER da plataforma';

    public function handle(RbacService $rbac): int
    {
        $email = trim((string) env('MASTER_EMAIL'));
        $password = (string) env('MASTER_PASSWORD');
        if ($email === '' || $password === '') {
            $this->components->warn('MASTER_EMAIL e MASTER_PASSWORD não configurados; nenhuma conta MASTER foi criada.');
            return self::SUCCESS;
        }
        if (strlen($password) < 8) {
            $this->components->error('MASTER_PASSWORD deve possuir pelo menos 8 caracteres.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password, $rbac): void {
            $empresa = Empresa::query()->firstOrCreate(
                ['cnpj' => preg_replace('/\D+/', '', (string) env('MASTER_COMPANY_CNPJ', '99999999999999'))],
                ['razao_social' => 'FiscalFlow Plataforma', 'nome_fantasia' => 'FiscalFlow', 'ativa' => true],
            );
            $profiles = $rbac->provisionEmpresa($empresa);
            $attributes = [
                'id_empresa' => $empresa->id_empresa, 'id_perfil' => $profiles->get('administrador')->id_perfil,
                'nome' => env('MASTER_NAME', 'Administrador MASTER'), 'perfil' => 'Administrador', 'ativo' => true, 'master' => true,
            ];
            $user = User::query()->where('email', $email)->first();
            if (!$user) {
                $attributes['email'] = $email;
                $attributes['password'] = Hash::make($password);
                $user = User::query()->create($attributes);
            } else {
                if ($this->option('rotate-password')) {
                    $attributes['password'] = Hash::make($password);
                }
                $user->update($attributes);
            }

            $plano = Plano::query()->firstOrCreate(
                ['slug' => 'plataforma-master'],
                ['nome' => 'Plataforma MASTER', 'descricao' => 'Acesso interno irrestrito.', 'valor_mensal' => 0, 'modulos' => ['*'], 'ativo' => true],
            );
            if (!EmpresaAssinatura::query()->where('id_empresa', $empresa->id_empresa)->vigente()->exists()) {
                EmpresaAssinatura::query()->create([
                    'id_empresa' => $empresa->id_empresa, 'id_plano' => $plano->id_plano, 'status' => 'ativa',
                    'inicia_em' => now(), 'observacoes' => 'Conta interna da plataforma.', 'criada_por' => $user->id_usuario,
                ]);
            }
        });

        $this->components->info("Conta MASTER provisionada para {$email}.");
        return self::SUCCESS;
    }
}
