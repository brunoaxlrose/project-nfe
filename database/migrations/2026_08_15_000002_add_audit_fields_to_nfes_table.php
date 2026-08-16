<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->foreignId('usuario_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('destinatario_documento', 14)->nullable()->index();
        });
    }
    public function down(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->dropForeign(['usuario_id']); $table->dropColumn(['usuario_id', 'destinatario_documento']);
        });
    }
};
