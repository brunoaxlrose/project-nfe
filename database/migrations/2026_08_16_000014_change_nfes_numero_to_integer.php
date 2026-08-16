<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->unsignedInteger('numero')->change();
        });
    }

    public function down(): void
    {
        Schema::table('nfes', function (Blueprint $table): void {
            $table->unsignedSmallInteger('numero')->change();
        });
    }
};
