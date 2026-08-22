<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asaas_pagamento', function (Blueprint $table): void {
            $table->bigIncrements('id_asaas_pagamento');
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_plano');
            $table->string('asaas_customer_id', 80)->nullable();
            $table->string('asaas_payment_id', 80)->nullable()->unique();
            $table->uuid('external_reference')->unique();
            $table->decimal('valor', 12, 2);
            $table->string('payer_email', 180);
            $table->string('status', 40)->default('created')->index();
            $table->text('qr_code')->nullable();
            $table->longText('qr_code_base64')->nullable();
            $table->timestampTz('pix_expira_em')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestampTz('aprovado_em')->nullable();
            $table->timestampTz('processado_em')->nullable();
            $table->timestampsTz();

            $table->foreign('id_empresa', 'asaas_pagamento_fk_empresa')->references('id_empresa')->on('empresa')->cascadeOnDelete();
            $table->foreign('id_usuario', 'asaas_pagamento_fk_usuario')->references('id_usuario')->on('usuario')->cascadeOnDelete();
            $table->foreign('id_plano', 'asaas_pagamento_fk_plano')->references('id_plano')->on('plano')->restrictOnDelete();
            $table->index(['id_empresa', 'status'], 'asaas_pagamento_empresa_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asaas_pagamento');
    }
};
