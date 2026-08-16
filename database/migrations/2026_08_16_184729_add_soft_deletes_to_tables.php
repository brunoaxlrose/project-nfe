<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cliente', function (Blueprint $table) {
            $table->softDeletes('deleted_at');
        });

        Schema::table('produto', function (Blueprint $table) {
            $table->softDeletes('deleted_at');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->softDeletes('deleted_at');
        });

        Schema::table('natureza_operacao', function (Blueprint $table) {
            $table->softDeletes('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cliente', function (Blueprint $table) {
            $table->dropSoftDeletes('deleted_at');
        });

        Schema::table('produto', function (Blueprint $table) {
            $table->dropSoftDeletes('deleted_at');
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->dropSoftDeletes('deleted_at');
        });

        Schema::table('natureza_operacao', function (Blueprint $table) {
            $table->dropSoftDeletes('deleted_at');
        });
    }
};
