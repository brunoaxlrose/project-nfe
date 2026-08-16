<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('nfes', function (Blueprint $table): void { $table->foreignId('cliente_id')->nullable()->after('usuario_id')->constrained('clientes')->nullOnDelete(); }); } public function down(): void { Schema::table('nfes', function (Blueprint $table): void { $table->dropForeign(['cliente_id']); $table->dropColumn('cliente_id'); }); } };
