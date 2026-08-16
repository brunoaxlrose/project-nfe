-- FiscalFlow - restauração/importação de catálogo da empresa 2
-- Gerado a partir dos CSVs do Bling exportados em 16/08/2026.
-- Execute com: docker compose exec -T db psql -U nfe -d nfe < database/sql/restore_empresa_2_catalogo.sql
BEGIN;

DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM empresa WHERE id_empresa = 2) THEN RAISE EXCEPTION 'Empresa 2 não encontrada.'; END IF; END $$;

-- 1) Permissões globais
INSERT INTO permissao (nome, slug, categoria, created_at, updated_at) VALUES
('Visualizar o início', 'menu.dashboard', 'Menus', NOW(), NOW()),
('Visualizar o módulo de NF-e', 'menu.nfe', 'Menus', NOW(), NOW()),
('Visualizar configurações', 'menu.configuracoes', 'Menus', NOW(), NOW()),
('Visualizar usuários e permissões', 'menu.usuarios', 'Menus', NOW(), NOW()),
('Consultar notas fiscais', 'nfe.visualizar', 'NF-e', NOW(), NOW()),
('Criar notas fiscais', 'nfe.criar', 'NF-e', NOW(), NOW()),
('Consultar retorno da SEFAZ', 'nfe.consultar', 'NF-e', NOW(), NOW()),
('Baixar XML e DANFE', 'nfe.baixar', 'NF-e', NOW(), NOW()),
('Clonar notas fiscais', 'nfe.clonar', 'NF-e', NOW(), NOW()),
('Cancelar notas fiscais', 'nfe.cancelar', 'NF-e', NOW(), NOW()),
('Emitir Carta de Correção', 'nfe.cce', 'NF-e', NOW(), NOW()),
('Consultar clientes e destinatários', 'clientes.visualizar', 'Cadastros', NOW(), NOW()),
('Cadastrar clientes e destinatários', 'clientes.criar', 'Cadastros', NOW(), NOW()),
('Consultar produtos', 'produtos.visualizar', 'Cadastros', NOW(), NOW()),
('Consultar naturezas de operação', 'naturezas.visualizar', 'Cadastros', NOW(), NOW()),
('Consultar configurações da empresa', 'configuracoes.visualizar', 'Configurações', NOW(), NOW()),
('Alterar configurações da empresa', 'configuracoes.editar', 'Configurações', NOW(), NOW()),
('Gerenciar certificado digital', 'certificado.gerenciar', 'Configurações', NOW(), NOW()),
('Consultar usuários', 'usuarios.visualizar', 'Usuários', NOW(), NOW()),
('Cadastrar usuários', 'usuarios.criar', 'Usuários', NOW(), NOW()),
('Editar usuários', 'usuarios.editar', 'Usuários', NOW(), NOW()),
('Gerenciar perfis e permissões', 'perfis.gerenciar', 'Usuários', NOW(), NOW())
ON CONFLICT (slug) DO UPDATE SET nome = EXCLUDED.nome, categoria = EXCLUDED.categoria, updated_at = NOW();

-- 2) Perfis da empresa 2
INSERT INTO perfil (id_empresa, nome, slug, created_at, updated_at) VALUES
(2, 'Administrador', 'administrador', NOW(), NOW()),
(2, 'Operador', 'operador', NOW(), NOW()),
(2, 'Faturamento', 'faturamento', NOW(), NOW())
ON CONFLICT (id_empresa, slug) DO UPDATE SET nome = EXCLUDED.nome, updated_at = NOW();

-- 3) Vínculo perfil x permissões
INSERT INTO perfil_permissao (id_perfil, id_permissao) SELECT pf.id_perfil, pe.id_permissao FROM perfil pf JOIN permissao pe ON pe.slug IN ('menu.dashboard', 'menu.nfe', 'menu.configuracoes', 'menu.usuarios', 'nfe.visualizar', 'nfe.criar', 'nfe.consultar', 'nfe.baixar', 'nfe.clonar', 'nfe.cancelar', 'nfe.cce', 'clientes.visualizar', 'clientes.criar', 'produtos.visualizar', 'naturezas.visualizar', 'configuracoes.visualizar', 'configuracoes.editar', 'certificado.gerenciar', 'usuarios.visualizar', 'usuarios.criar', 'usuarios.editar', 'perfis.gerenciar') WHERE pf.id_empresa = 2 AND pf.slug = 'administrador' ON CONFLICT DO NOTHING;
INSERT INTO perfil_permissao (id_perfil, id_permissao) SELECT pf.id_perfil, pe.id_permissao FROM perfil pf JOIN permissao pe ON pe.slug IN ('menu.dashboard', 'menu.nfe', 'nfe.visualizar', 'nfe.criar', 'nfe.consultar', 'nfe.baixar', 'nfe.clonar', 'clientes.visualizar', 'clientes.criar', 'produtos.visualizar', 'naturezas.visualizar') WHERE pf.id_empresa = 2 AND pf.slug = 'operador' ON CONFLICT DO NOTHING;
INSERT INTO perfil_permissao (id_perfil, id_permissao) SELECT pf.id_perfil, pe.id_permissao FROM perfil pf JOIN permissao pe ON pe.slug IN ('menu.dashboard', 'menu.nfe', 'nfe.visualizar', 'nfe.criar', 'nfe.consultar', 'nfe.baixar', 'nfe.clonar', 'clientes.visualizar', 'produtos.visualizar', 'naturezas.visualizar') WHERE pf.id_empresa = 2 AND pf.slug = 'faturamento' ON CONFLICT DO NOTHING;

-- 4) Produtos do Bling
CREATE TEMP TABLE _import_produto (codigo text, descricao text, ncm text, valor_unitario numeric, cfop text, csosn text, cst text, unidade text, ativo boolean) ON COMMIT DROP;
INSERT INTO _import_produto (codigo, descricao, ncm, valor_unitario, cfop, csosn, cst, unidade, ativo) VALUES
('1-40282', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG OFF LISTRADO', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('4926', 'CALÇA FEM WIDE LEG PLUS COM LISTRAS 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-4926', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG PLUS COM LISTRAS', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40314', 'CALÇA FEM RETA CROPPED SH 23/04/2026 2 LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40314', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM RETA CROPPED SH', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('40317', 'CALÇA FEM WIDE LEG 20/04/2026 2 LAVANDERIA', '62034200', 17.50, '5901', '0400', '', 'PÇ', true),
('1-40317', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('40364', 'CALÇA FEM RTA (SH) 20/04/2026 2 - LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40364', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM RETA  (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('40171-5', 'CALÇA FEM RETA (SH) 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('0118012', 'SHORT FEM RETO INF C A P RETA (JEANS AZUL) REGULADOR NO COS - 224124', '62046900', 40.00, '5901', '0400', '', 'UN', true),
('1-0118012', 'SHORT FEM RETO INF C A P RETA (JEANS AZUL) REGULADOR NO COS - 224124', '62046900', 40.00, '5901', '0400', '', 'UN', true),
('40185-14', 'CALÇA FEM WIDE LEG DARK BLUE (SH) 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40185-14', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM RETA', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40302', 'CALÇA FEM WIDE LEG CROPPED SH 20/04/2026', '62034200', 7.90, '5901', '0400', '', 'PÇ', true),
('1-40302', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG CROPPED SH 20/04/2026', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('201-55-3', 'JAQUETA MASC JEANS 07/05/2026 2- LAVANDERIA', '62023000', 17.90, '5901', '0400', '', 'PÇ', true),
('1-201-55-3', 'MÃO DE OBRA DE LAVAGENS E INSUMOS JAQUETA MASC JEANS', '62023000', 0.30, '5901', '0400', '', 'PÇ', true),
('201-56', 'JAQUETA FEM LYCRA PRETA 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-201-56', 'MÃO DE OBRA DE LAVAGENS E INSUMOS JAQUETA FEM LYCRA PRETA', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('201-17-1', 'JAQUETA MASC BLUE JEANS 30/04/2026 2- LAVANDERIA', '62023000', 17.90, '5901', '0400', '', 'PÇ', true),
('1-201-17-1', 'MÃO DE OBRA DE LAVAGENS E INSUMOS JAQUETA MASC BLUE JEANS', '62063000', 0.30, '5901', '0400', '', 'PÇ', true),
('4925', 'CALÇA FEM CIG PLUS 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-4925', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM CIG PLUS', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40301', 'CALÇA FEM WIDE LEG OFF (SH) 28/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40301', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG OFF (SH)', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40316', 'CALÇA FEM RETA OFF (SH) 29/04/2026 2- LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40316', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM RETA OFF (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('4922', 'CALÇA FEM CIG PLUS SIZE 02/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-4922', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM CIG PLUS SIZE', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40249-6', 'CALCA FEM WIDE LEG (SH) 28 04 2026', '62034200', 17.90, '5901', '0400', '', 'PC', true),
('1-40249-6', 'MAO DE OBRA DE LAVAGENS E INSUMOS CALCA FEM WIDE (SH)', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('40249-5', 'CALCA FEM WIDE LEG (SH)  28 04 2026 2 LAVANDEIA', '62046200', 17.90, '5901', '0400', '', 'PC', true),
('1-40249-5', 'MAO DE OBRA DE LAVAGENS E INSUMOS CALCA FEM WID LEG (SH)', '62046200', 0.30, '5901', '0400', '', 'PC', true),
('40305', 'CALCA FEM BARREL (SH) 29 04 2026 2 LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PC', true),
('1-40305', 'MAO DE OBRA DE LAVAGENS E INSUMOS CALCA FEM BARREL (SH)', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('40195-8', 'CALÇA FEM RETA (SH)05/05/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40195-8', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM RETA (SH)', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('6862', 'SHORTS FEM JEANS (SH) 05/05/2026 2- LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-6862', 'MÃO DE OBRA DE LAVAGENS E INSUMOS SHORTS FEM JEANS (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('6862-2', 'SHORTS FEM JEANS (SH) 05/05/2026 2- LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-6862-2', 'MÃO DE OBRA DE LAVAGENS E INSUMOS SHORTS FEM JEANS (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('12610', 'CALÇA FEM BARREL 21/05/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-12610', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM BARREL', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('4900', 'CALÇA FEM BOOT CUT PLUS 28/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-4900', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM BOOT CUT PLUS', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('1873', 'CALÇA MAS RETA 30/04/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-1873', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA MAS RETA', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('6694-7', 'SHORTS FEM LYCRA BRANCA', '62034200', 50.00, '5901', '0400', '', 'PÇ', true),
('1-6694-7', 'MÃO DE OBRA DE LAVAGENS E INSUMOS SHORTS FEM LYCRA BRANCA', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('5346-2', 'BERMUDA FEM BOYFRIEND', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-5346-2', 'MÃO DE OBRA DE LAVAGENS E INSUMOS BERMUDA FEM BOYFRIEND', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40301-0', 'CALÇA FEM WIDE LEG OFF (SH)', '62046200', 49.90, '5901', '0400', '', 'PÇ', true),
('40278-4', 'CALÇA FEM PALAZZO (SH) 08/05/2026 2- LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40278-4', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM PALAZZO (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('40195-9', 'CALÇA FEM RETA (SH)08/05/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('6866', 'SHORTS FEM (SH) 07/05/2026', '62034200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-6866', 'MÃO DE OBRA DE LAVAGENS E INSUMOS SHORTS FEM (SH)', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('40250-4', 'CALCA FEM WIDE LEG (SH) 18/05/2026 2- LAVANDERIA', '62034200', 17.90, '5901', '0400', '', 'PC', true),
('1-40250-4', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM WIDE LEG', '62034200', 0.30, '5901', '0400', '', 'PÇ', true),
('18206', 'CALÇA MASC SLIM 05/06/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-18206', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA MASC SLIM', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('201-55-4', 'JAQUETA MASC JEANS 05/06/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('40265-1', 'CALÇA FEM CIG BRANCA 05/06/2026', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('40364-2', 'CALCA FEM RETA (SH)', '62034200', 17.90, '5901', '0400', '', 'PC', true),
('1-40364-2', 'MAO DE OBRA DELAVAGENS E INSUMOS CALCA FEM RETA(SH)', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('40364-3', 'CALCA FEM RETA(SH) 28/05/2026', '62034200', 17.90, '5901', '0400', '', 'PC', true),
('1-40364-3', 'MAO DE OBRA DE LAVAGENS E INSUMOS CALCAFEM RETA', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('40361', 'CALÇA FEM BOOT CUT PLUS 28/04/2026', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40361', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM BOOT CUT PLUS', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('40323', 'CALÇA FEM SKINNY 03/06/2026 2- LAVANDERIA', '62046200', 17.90, '5901', '0400', '', 'PÇ', true),
('1-40323', 'MÃO DE OBRA DE LAVAGENS E INSUMOS CALÇA FEM SKINNY', '62046200', 0.30, '5901', '0400', '', 'PÇ', true),
('6365', '6365- BERMUDA MASCULINA BOLSO CHAPADO', '62034200', 22.00, '5901', '0400', '', 'PC', true),
('1-6365', '1-6365 MAO DE OBRA DE LAVAGENS E INSUMOS BERMUDA MASCULINA BOLSO CHAPADO', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('6365-1', 'BERMUDA MASCULINA BOLSO CHAPADO', '62034200', 22.00, '5901', '0400', '', 'PC', true),
('1-6365-1', 'MAO DE OBRA DE LAVAGENS E INSUMOS BERMUDA MASCULINA BOLSO CHAPADO', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('6514', '6514 - CALÇA MASCULINA BOLSO CHAPADO JEANS', '62034200', 30.00, '5901', '0400', '', 'PC', true),
('1-6514', '1 -6514 - MAO DE OBRA CALÇA MASCULINA BOLSO CHAPADO JEANS', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('00910440 0002', '91044 - SHORTS FEM HOTPANT ADULTO 36', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('009104400003', '91044 - SHORTS FEM HOTPANT ADULTO 38', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('00910440 0004', '91044 - SHORTS FEM HOTPANT ADULTO 40', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('00910440 0005', '91044 - SHORTS FEM HOTPANT ADULTO 42', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('00910440 0006', '91044 - SHORTS FEM HOTPANT ADULTO 44', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('00910440 0007', '91044 - SHORTS FEM HOTPANT ADULTO 46', '62034200', 34.00, '5901', '0400', '', 'PC', true),
('1- 00910440 0002', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 36', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1- 00910440 0003', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 38', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1- 00910440 0004', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 40', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1- 00910440 0005', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 42', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1- 00910440 0006', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 44', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1- 00910440 0007', '1 - 91044 MAO DE OBRA SHORTS FEM HOTPANT ADULTO 46', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('1-1033', 'MAO DE OBRA CALCA FEM JEANS SKINNY', '61046300', 0.30, '5901', '0400', '', 'PC', true),
('1033', 'CALCA FEM JEANS SKINNY', '61046300', 39.99, '5901', '0400', '', 'PC', true),
('25163', 'CALCA FEM SKINNY', '61046300', 69.99, '5901', '0400', '', 'PC', true),
('1-25163', 'MAO DE OBRA CALCA FEM SKINNY', '61046300', 0.30, '5901', '0400', '', 'PC', true),
('1002', 'CALCA WIDE LEG JEANS FEMININA', '61034900', 39.99, '5901', '0400', '', 'PC', true),
('1-1002', 'MAO DE OBRA CALCA WIDE LEG JEANS FEMININA', '61034900', 0.30, '5901', '0400', '', 'PC', true),
('1-2010', 'MAO DE OBRA CALCA JEANS MASCULINA', '62034200', 0.30, '5901', '0400', '', 'PC', true),
('2010', 'CALCA JEANS MASCULINA', '62034200', 39.99, '5901', '0400', '', 'PC', true),
('1-1004', 'MAO DE OBRA CALCA FLARE JEANS FEMININA', '61034900', 0.30, '5901', '0400', '', 'PC', true),
('1004', 'CALCA FLARE JEANS FEMININA', '61034900', 49.99, '5901', '0400', '', 'PC', true);
UPDATE produto p SET descricao = i.descricao, ncm = i.ncm, valor_unitario = i.valor_unitario, cfop = i.cfop, csosn = i.csosn, cst = i.cst, unidade = i.unidade, ativo = i.ativo, updated_at = NOW() FROM _import_produto i WHERE p.id_empresa = 2 AND p.codigo = i.codigo;
INSERT INTO produto (id_empresa, codigo, descricao, ncm, valor_unitario, cfop, csosn, cst, unidade, ativo, created_at, updated_at) SELECT 2, i.codigo, i.descricao, i.ncm, i.valor_unitario, i.cfop, i.csosn, i.cst, i.unidade, i.ativo, NOW(), NOW() FROM _import_produto i WHERE NOT EXISTS (SELECT 1 FROM produto p WHERE p.id_empresa = 2 AND p.codigo = i.codigo);

-- 5) Clientes/contatos do Bling
CREATE TEMP TABLE _import_contato (razao_social text, documento text, inscricao_estadual text, cep text, logradouro text, numero text, complemento text, bairro text, cidade text, uf text, fone text, email text, tipo text, ativo boolean) ON COMMIT DROP;
INSERT INTO _import_contato (razao_social, documento, inscricao_estadual, cep, logradouro, numero, complemento, bairro, cidade, uf, fone, email, tipo, ativo) VALUES
('PF2 CONFECÇÕES LTDA', '34546753000119', '126574005111', '03027000', 'Rua Xavantes', '708', '', 'Brás', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('3A GOLDEN VESTUARIO E CALÇADOS LTDA', '45281412000185', '125652660115', '03013000', 'Rua Casimiro de Abreu', '141', '', 'Brás', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('ANTHONY CONFECÇÕES EIRELI', '32796382000106', '123635003112', '03012000', 'Rua Conselheiro Belisário', '173', '', 'Brás', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('CAVIMK COMERCIO E REPRESENTAÇÕES LTDA', '23169531000108', '144960951114', '03021000', 'Rua Catumbi', '473', '', 'Catumbi', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('ROUSSEL CONFECCOES LTDA', '53026151000185', '132476535112', '03027000', 'RUA XAVANTES', '708', 'ANDAR 1                   SALA  01', 'BRAS', 'SAO PAULO', 'SP', '', '', 'Parceiro', true),
('SGK CONFECÇÕES LTDA', '47606508000100', '197842454', '40375016', 'ESTRADA DA LIBERDADE', '340', 'LOJA UNICA', 'LIBERDADE', 'Salvador', 'BA', '', '', 'Parceiro', true),
('ALPHA MAX CONFECCOES LTDA', '54492941000119', '135737613110', '03027000', 'RUA XAVANTES', '708', 'ANDAR 02', 'BRAS', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('RSQ MODAS LTDA', '59142774000108', '0051009150030', '32315000', 'AV JOAO CESAR DE OLIVEIRA', '2745', '', 'ELDORADO', 'CONTAGEM', 'MG', '', '', 'Cliente', true),
('JIMMY LICHAA COMERCIO E CONFECÇÕES LTDA', '39473209000108', '129915276110', '03012030', 'Rua Saião Lobato', '126', '', 'Brás', 'São Paulo', 'SP', '', '', 'Cliente', true),
('KROCO COMERCIO LTDA', '53313445000198', '132683196116', '03027000', 'RUA XAVANTES', '576A', '', 'BRAS', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('MATRIX CONFECÇÕES LTDA', '56975119000134', '150591796110', '03027000', 'Rua Xavantes', '675', 'ANDAR 1', 'Brás', 'São Paulo', 'SP', '', '', 'Cliente', true),
('INDUSTRIA E COMERCIO DE ROUPAS MEITRIX LTDA', '09553057000192', '148125597116', '03027000', 'RUA XAVANTES', '673', ': E 675;', 'BRAS', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('SIDEWAY CONFECCOES LTDA', '03624297000154', '116864482114', '03052060', 'RUA DOUTOR JOAO ALVES DE LIMA', '88', '', 'BRAS', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('KATHAI CONFECCOES LTDA', '27053415000180', '141718184116', '03412030', 'RUA CACAQUERA', '39', 'CONJ  A                   SALA  03', 'VILA ANTONINA', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('GUNTEX TEXTIL LTDA', '31348383000117', '119893242118', '05466030', 'RUA OROBO', '1036', '', 'ALTO DE PINHEIROS', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('A.S.C INDUSTRIA E COMERCIO DE ROUPAS IMPORTACAO E EXPORTACAO', '08062888000107', '149307323112', '03021040', 'RUA JEQUITINHONHA', '100', '', 'BELENZINHO', 'SAO PAULO', 'SP', '', '', 'Cliente', true),
('ZZUK INDUSTRIA E COMERCIO E REPRESENTACOES EIRELI', '34170606000197', '126373418116', '03017010', 'R DOUTOR CARLOS BOTELHO', '274', '', 'BRAS', 'São Paulo', 'SP', '(11) 95832-6699', '', 'Cliente', true),
('M.K GALONE CONF. E COM. DE ROUPAS LTDA', '05784234000190', '116637500117', '03441040', 'R. SEUL', '80', '', 'VILA NOVA MANCHESTER', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('VINOVERO VESTUARIO LTDA', '26390243000177', '141371885114', '05072020', 'RUA DOUTOR CINCINATO PAMPONET', '126', '128 LOJA 05', 'LAPA', 'São Paulo', 'SP', '(11) 91340-2223', 'adm02@natoon.com.br', 'Parceiro', true),
('E. ALMEIDA DA SILVA VESTUARIO LTDA', '63415975000109', '156559919112', '01042001', 'RUA BARAO DE ITAPETININGA', '45', '', 'REPUBLICA', 'São Paulo', 'SP', '', '', 'Parceiro', true),
('BETUNA CONFECCOES DE ROUPAS LTDA', '51070987000232', '373461924119', '06653020', 'AVENIDA CESARIO DE ABREU', '76', 'LOJA 07', 'CENTRO', 'Itapevi', 'SP', '(11) 3195-6388', '', 'Parceiro', true),
('HEIJU VESTUARIO LTDA', '66853283000122', '159855360116', '07011003', 'RUA DOM PEDRO II', '108', '', 'CENTRO', 'Guarulhos', 'SP', '(11) 3195-6388', '', 'Parceiro', true);
UPDATE cliente c SET razao_social = i.razao_social, inscricao_estadual = i.inscricao_estadual, cep = i.cep, logradouro = i.logradouro, numero = i.numero, complemento = i.complemento, bairro = i.bairro, cidade = i.cidade, uf = i.uf, ativo = i.ativo, updated_at = NOW() FROM _import_contato i WHERE c.id_empresa = 2 AND regexp_replace(c.documento, '\D', '', 'g') = i.documento;
INSERT INTO cliente (id_empresa, razao_social, documento, inscricao_estadual, cep, logradouro, numero, complemento, bairro, cidade, uf, ativo, created_at, updated_at) SELECT 2, i.razao_social, i.documento, i.inscricao_estadual, i.cep, i.logradouro, i.numero, i.complemento, i.bairro, i.cidade, i.uf, i.ativo, NOW(), NOW() FROM _import_contato i WHERE NOT EXISTS (SELECT 1 FROM cliente c WHERE c.id_empresa = 2 AND regexp_replace(c.documento, '\D', '', 'g') = i.documento);

-- 6) Também popula destinatario para autocomplete/emissão de NF-e
UPDATE destinatario d SET nome_razao_social = i.razao_social, inscricao_estadual = i.inscricao_estadual, cep = i.cep, logradouro = i.logradouro, numero = i.numero, complemento = i.complemento, bairro = i.bairro, municipio = i.cidade, uf = i.uf, tipo = i.tipo, ativo = i.ativo, updated_at = NOW() FROM _import_contato i WHERE d.id_empresa = 2 AND regexp_replace(d.documento, '\D', '', 'g') = i.documento;
INSERT INTO destinatario (id_empresa, nome_razao_social, documento, inscricao_estadual, cep, logradouro, numero, complemento, bairro, municipio, uf, tipo, ativo, created_at, updated_at) SELECT 2, i.razao_social, i.documento, i.inscricao_estadual, i.cep, i.logradouro, i.numero, i.complemento, i.bairro, i.cidade, i.uf, i.tipo, i.ativo, NOW(), NOW() FROM _import_contato i WHERE NOT EXISTS (SELECT 1 FROM destinatario d WHERE d.id_empresa = 2 AND regexp_replace(d.documento, '\D', '', 'g') = i.documento);

-- 7) Resumo
SELECT 'permissoes' AS tabela, COUNT(*) FROM permissao UNION ALL SELECT 'perfis_empresa_2', COUNT(*) FROM perfil WHERE id_empresa = 2 UNION ALL SELECT 'produtos_empresa_2', COUNT(*) FROM produto WHERE id_empresa = 2 UNION ALL SELECT 'clientes_empresa_2', COUNT(*) FROM cliente WHERE id_empresa = 2 UNION ALL SELECT 'destinatarios_empresa_2', COUNT(*) FROM destinatario WHERE id_empresa = 2;
COMMIT;
