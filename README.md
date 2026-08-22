# FiscalFlow

O FiscalFlow é uma plataforma de gestão fiscal criada para centralizar cadastros, emissão de notas fiscais eletrônicas e acompanhamento das operações de uma empresa em um único ambiente.

## Principais funcionalidades

- Emissão de NF-e por etapas, com revisão das informações antes da transmissão.
- Consulta do retorno da SEFAZ e apresentação clara dos motivos de rejeição.
- Visualização prévia do DANFE para conferência.
- Download de XML, DANFE e documentos disponíveis.
- Cancelamento, carta de correção e clonagem de notas.
- Histórico de notas com busca, filtros e acompanhamento por situação.
- Cadastro e gerenciamento de clientes e fornecedores.
- Cadastro de produtos com código, NCM, unidade, valor e informações fiscais.
- Cadastro de naturezas de operação para vendas, compras, devoluções, remessas, transferências, bonificações e industrialização.
- Busca rápida de empresas, destinatários e produtos mesmo quando existem muitos registros.
- Aplicação de descontos e outras despesas no total da nota.
- Formatação automática de valores monetários.

## Gestão da empresa

Cada empresa possui um espaço separado para seus usuários, cadastros, configurações, certificados e documentos fiscais.

Nas configurações, é possível manter:

- Dados cadastrais e endereço fiscal.
- Regime tributário e preferências de emissão.
- Série, numeração, CFOP e CSOSN padrão.
- Ambiente de homologação ou produção.
- Logotipo da empresa.
- Certificado digital A1 e sua validade.
- Informações do plano contratado, vigência, módulos e limite de usuários.

## Usuários e permissões

O administrador da empresa pode criar usuários, definir perfis e controlar as ações disponíveis para cada pessoa.

Quando o plano permitir novos acessos, também é possível criar um usuário com o mesmo perfil e as mesmas permissões de quem está realizando o cadastro. Todos os acessos continuam respeitando os módulos contratados pela empresa.

O sistema registra os acessos realizados para auxiliar no acompanhamento e na segurança da conta.

## Planos e liberações

O FiscalFlow possui uma área de administração geral para gerenciar empresas atendidas pela plataforma.

Essa área permite:

- Cadastrar novas empresas e seu administrador inicial.
- Criar e atualizar planos comerciais.
- Oferecer plano experimental com acesso automático por 1 dia.
- Ativar, inativar ou excluir planos que ainda não foram utilizados.
- Definir os módulos disponíveis em cada plano.
- Estabelecer limite de usuários.
- Informar valor mensal, início e fim da vigência.
- Trabalhar com período de teste e carência.
- Suspender, cancelar ou renovar uma liberação.
- Acompanhar empresas ativas e assinaturas próximas do vencimento.

Quando uma assinatura estiver vencida ou suspensa, o sistema impede o uso dos recursos protegidos e apresenta uma mensagem compreensível ao usuário.

## Segurança e organização

- Separação das informações por empresa.
- Controle de acesso por perfil e permissão.
- Proteção exclusiva da área de administração geral.
- Armazenamento protegido das credenciais fiscais.
- Registro de acessos dos usuários.
- Bloqueio por situação da empresa e vigência do plano.
- Mensagens claras para erros de validação, acesso e retorno da SEFAZ.

## Ambientes fiscais

O ambiente de homologação é indicado para testes e não possui valor fiscal.

O ambiente de produção é destinado às emissões reais. Antes de utilizá-lo, a empresa deve conferir seus dados cadastrais, regras fiscais, certificado digital e numeração das notas.

## Público do sistema

O FiscalFlow foi pensado para empresas, indústrias, comércios, escritórios e equipes que desejam organizar a rotina fiscal, reduzir tarefas repetitivas e acompanhar suas emissões com mais clareza.

## Observação importante

As informações tributárias devem ser conferidas com o responsável fiscal ou contábil da empresa. CFOP, NCM, CST, CSOSN, impostos, benefícios e demais dados precisam representar corretamente cada operação e a legislação aplicável.
