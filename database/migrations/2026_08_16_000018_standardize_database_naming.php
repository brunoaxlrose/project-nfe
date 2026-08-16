<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<'SQL'
DO $$
DECLARE
    item record;
BEGIN
    FOR item IN
        SELECT conrelid::regclass::text AS table_name, conname
        FROM pg_constraint
        WHERE contype = 'f'
          AND conrelid::regclass::text IN (
              'users',
              'clientes',
              'produtos',
              'nfes',
              'configuracoes_emissor',
              'destinatarios',
              'naturezas_operacao',
              'perfis',
              'perfil_permissao'
          )
    LOOP
        EXECUTE format('ALTER TABLE %I DROP CONSTRAINT %I', item.table_name, item.conname);
    END LOOP;
END $$;
SQL);

        foreach ([
            'empresas' => 'empresa',
            'users' => 'usuario',
            'clientes' => 'cliente',
            'produtos' => 'produto',
            'nfes' => 'nota_fiscal',
            'configuracoes_emissor' => 'configuracao_emissor',
            'destinatarios' => 'destinatario',
            'naturezas_operacao' => 'natureza_operacao',
            'perfis' => 'perfil',
            'permissoes' => 'permissao',
        ] as $from => $to) {
            $this->renameTableIfExists($from, $to);
        }

        foreach ([
            'empresa' => ['id' => 'id_empresa'],
            'usuario' => ['id' => 'id_usuario', 'empresa_id' => 'id_empresa', 'perfil_id' => 'id_perfil', 'name' => 'nome', 'active' => 'ativo'],
            'cliente' => ['id' => 'id_cliente', 'empresa_id' => 'id_empresa'],
            'produto' => ['id' => 'id_produto', 'empresa_id' => 'id_empresa'],
            'nota_fiscal' => ['id' => 'id_nota_fiscal', 'empresa_id' => 'id_empresa', 'usuario_id' => 'id_usuario', 'cliente_id' => 'id_cliente', 'destinatario_id' => 'id_destinatario', 'natureza_operacao_id' => 'id_natureza_operacao'],
            'configuracao_emissor' => ['id' => 'id_configuracao_emissor', 'empresa_id' => 'id_empresa'],
            'destinatario' => ['id' => 'id_destinatario', 'empresa_id' => 'id_empresa'],
            'natureza_operacao' => ['id' => 'id_natureza_operacao', 'empresa_id' => 'id_empresa'],
            'perfil' => ['id' => 'id_perfil', 'empresa_id' => 'id_empresa'],
            'permissao' => ['id' => 'id_permissao'],
            'perfil_permissao' => ['perfil_id' => 'id_perfil', 'permissao_id' => 'id_permissao'],
        ] as $table => $columns) {
            foreach ($columns as $from => $to) {
                $this->renameColumnIfExists($table, $from, $to);
            }
        }

        foreach ([
            'empresa' => ['empresas_pkey', 'empresa_pk'],
            'usuario' => ['users_pkey', 'usuario_pk'],
            'cliente' => ['clientes_pkey', 'cliente_pk'],
            'produto' => ['produtos_pkey', 'produto_pk'],
            'nota_fiscal' => ['nfes_pkey', 'nota_fiscal_pk'],
            'configuracao_emissor' => ['configuracoes_emissor_pkey', 'configuracao_emissor_pk'],
            'destinatario' => ['destinatarios_pkey', 'destinatario_pk'],
            'natureza_operacao' => ['naturezas_operacao_pkey', 'natureza_operacao_pk'],
            'perfil' => ['perfis_pkey', 'perfil_pk'],
            'permissao' => ['permissoes_pkey', 'permissao_pk'],
        ] as $table => [$from, $to]) {
            $this->renameConstraintIfExists($table, $from, $to);
        }

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'usuario_fk_empresa') THEN
        ALTER TABLE usuario ADD CONSTRAINT usuario_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'cliente_fk_empresa') THEN
        ALTER TABLE cliente ADD CONSTRAINT cliente_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'produto_fk_empresa') THEN
        ALTER TABLE produto ADD CONSTRAINT produto_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'destinatario_fk_empresa') THEN
        ALTER TABLE destinatario ADD CONSTRAINT destinatario_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'natureza_operacao_fk_empresa') THEN
        ALTER TABLE natureza_operacao ADD CONSTRAINT natureza_operacao_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'configuracao_emissor_fk_empresa') THEN
        ALTER TABLE configuracao_emissor ADD CONSTRAINT configuracao_emissor_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nota_fiscal_fk_empresa') THEN
        ALTER TABLE nota_fiscal ADD CONSTRAINT nota_fiscal_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'perfil_fk_empresa') THEN
        ALTER TABLE perfil ADD CONSTRAINT perfil_fk_empresa FOREIGN KEY (id_empresa) REFERENCES empresa(id_empresa) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nota_fiscal_fk_usuario') THEN
        ALTER TABLE nota_fiscal ADD CONSTRAINT nota_fiscal_fk_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nota_fiscal_fk_cliente') THEN
        ALTER TABLE nota_fiscal ADD CONSTRAINT nota_fiscal_fk_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nota_fiscal_fk_destinatario') THEN
        ALTER TABLE nota_fiscal ADD CONSTRAINT nota_fiscal_fk_destinatario FOREIGN KEY (id_destinatario) REFERENCES destinatario(id_destinatario) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'nota_fiscal_fk_natureza_operacao') THEN
        ALTER TABLE nota_fiscal ADD CONSTRAINT nota_fiscal_fk_natureza_operacao FOREIGN KEY (id_natureza_operacao) REFERENCES natureza_operacao(id_natureza_operacao) ON DELETE SET NULL;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'perfil_permissao_fk_perfil') THEN
        ALTER TABLE perfil_permissao ADD CONSTRAINT perfil_permissao_fk_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'perfil_permissao_fk_permissao') THEN
        ALTER TABLE perfil_permissao ADD CONSTRAINT perfil_permissao_fk_permissao FOREIGN KEY (id_permissao) REFERENCES permissao(id_permissao) ON DELETE CASCADE;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'perfil_id_perfil_id_empresa_unique') THEN
        ALTER TABLE perfil ADD CONSTRAINT perfil_id_perfil_id_empresa_unique UNIQUE (id_perfil, id_empresa);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'usuario_fk_perfil') THEN
        ALTER TABLE usuario ADD CONSTRAINT usuario_fk_perfil FOREIGN KEY (id_perfil, id_empresa) REFERENCES perfil(id_perfil, id_empresa) ON DELETE RESTRICT;
    END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        foreach ([
            'empresa' => 'empresas',
            'usuario' => 'users',
            'cliente' => 'clientes',
            'produto' => 'produtos',
            'nota_fiscal' => 'nfes',
            'configuracao_emissor' => 'configuracoes_emissor',
            'destinatario' => 'destinatarios',
            'natureza_operacao' => 'naturezas_operacao',
            'perfil' => 'perfis',
            'permissao' => 'permissoes',
        ] as $from => $to) {
            $this->renameTableIfExists($from, $to);
        }
    }

    private function renameTableIfExists(string $from, string $to): void
    {
        DB::statement("DO $$ BEGIN IF to_regclass('public.$from') IS NOT NULL AND to_regclass('public.$to') IS NULL THEN ALTER TABLE $from RENAME TO $to; END IF; END $$;");
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' AND column_name = '$from') AND NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '$table' AND column_name = '$to') THEN ALTER TABLE $table RENAME COLUMN $from TO $to; END IF; END $$;");
    }

    private function renameConstraintIfExists(string $table, string $from, string $to): void
    {
        DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid = 'public.$table'::regclass AND conname = '$from') AND NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conrelid = 'public.$table'::regclass AND conname = '$to') THEN ALTER TABLE $table RENAME CONSTRAINT $from TO $to; END IF; END $$;");
    }
};
