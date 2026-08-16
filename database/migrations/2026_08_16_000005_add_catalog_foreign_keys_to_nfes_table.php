<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->foreignId('destinatario_id')->nullable()->after('usuario_id')->constrained('destinatarios')->nullOnDelete();
            $table->foreignId('natureza_operacao_id')->nullable()->after('destinatario_id')->constrained('naturezas_operacao')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->dropForeign(['destinatario_id']);
            $table->dropForeign(['natureza_operacao_id']);
            $table->dropColumn(['destinatario_id', 'natureza_operacao_id']);
        });
    }
};
