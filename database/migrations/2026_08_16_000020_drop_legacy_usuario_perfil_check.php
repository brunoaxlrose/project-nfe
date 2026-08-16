<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = 'public.usuario'::regclass
          AND conname = 'users_perfil_check'
    ) THEN
        ALTER TABLE usuario DROP CONSTRAINT users_perfil_check;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = 'public.usuario'::regclass
          AND conname = 'users_perfil_check'
    ) THEN
        ALTER TABLE usuario
            ADD CONSTRAINT users_perfil_check
            CHECK (perfil IN ('Administrador', 'Operador'));
    END IF;
END $$;
SQL);
    }
};
