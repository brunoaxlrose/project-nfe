<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('configuracao_emissor', function (Blueprint $table): void {
            $table->unsignedInteger('proximo_numero')->nullable()->after('serie_padrao');
        });
    }

    public function down(): void
    {
        Schema::table('configuracao_emissor', function (Blueprint $table): void {
            $table->dropColumn('proximo_numero');
        });
    }
};
