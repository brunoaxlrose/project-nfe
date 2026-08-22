# FiscalFlow

O FiscalFlow é uma plataforma de gestão fiscal para empresas que precisam organizar seus cadastros e emitir notas fiscais eletrônicas com mais segurança, agilidade e controle.

## O que o FiscalFlow oferece

- Cadastro de empresas com contas separadas e dados protegidos.
- Acesso para administradores e operadores, com permissões diferentes para cada função.
- Cadastro e consulta de clientes, fornecedores, parceiros e produtos.
- Importação de cadastros existentes para acelerar a implantação.
- Busca automática de dados de empresas e endereços para reduzir erros de digitação.
- Cadastro de naturezas de operação com regras fiscais padronizadas.
- Emissão de NF-e com preenchimento orientado e cálculo automático dos valores.
- Suporte a operações de venda, remessa, devolução, transferência, bonificação e outras rotinas fiscais.
- Emissão voltada para operações de remessa para industrialização, incluindo CFOP 5901 e CSOSN 0400.
- Consulta do andamento das notas e acompanhamento do retorno da SEFAZ.
- Histórico com filtros por período, situação, documento do destinatário e número da nota.
- Consulta dos motivos de rejeição para facilitar a correção e uma nova emissão.
- Download do XML e do DANFE das notas disponíveis.
- Recursos de cancelamento, carta de correção e clonagem de notas para novos rascunhos.
- Configuração dos dados fiscais e comerciais da empresa em um único lugar.
- Cadastro do logotipo para utilização nos documentos impressos.
- Seleção entre ambiente de homologação e ambiente de produção.
- Teste de comunicação com a SEFAZ antes de iniciar as emissões.
- Cadastro seguro do certificado digital A1, com indicação de validade e titularidade.
- Mensagens claras, alertas visuais e indicadores de processamento para orientar o usuário.

## Como funciona o fluxo principal

1. A empresa cria sua conta e cadastra o responsável inicial.
2. O administrador confirma os dados fiscais, o endereço e o ambiente de trabalho.
3. Clientes, fornecedores e produtos podem ser cadastrados manualmente ou importados.
4. O operador escolhe a natureza da operação, seleciona o destinatário e adiciona os produtos.
5. O FiscalFlow apresenta um resumo antes da emissão para conferência.
6. A nota é enviada para a SEFAZ e seu retorno fica registrado no histórico.
7. Quando autorizados, o XML e o DANFE ficam disponíveis para consulta e download.

## Segurança e organização

Cada empresa possui seus próprios usuários, clientes, produtos, configurações, certificados e notas. Um usuário não deve visualizar ou utilizar informações de outra empresa.

O certificado digital é tratado como uma informação confidencial. O acesso às configurações fica restrito aos administradores, e o sistema informa quando o certificado está vencido ou quando a senha não pode ser validada.

## Ambientes de uso

O ambiente de homologação é indicado para testes e não possui valor fiscal. Depois da conferência dos dados e das regras tributárias, a empresa pode utilizar o ambiente de produção para emitir documentos com valor fiscal.

## Preparação do ambiente local

Antes do primeiro uso:

1. Copie `docker-compose.yml.dist` para `docker-compose.yml`.
2. Copie `.env.example` para `.env`.
3. Preencha no `.env` apenas as informações do seu ambiente local.
4. Inicie o FiscalFlow com `docker compose up -d --build`.

O `docker-compose.yml` local, o `.env`, os certificados e os documentos fiscais permanecem fora do histórico do Git.

## Arquitetura headless

O Laravel 13 concentra regras de negócio, RBAC multiempresa, validação e endpoints JSON em `routes/api.php`. A interface é uma SPA em Vue 3, Vite, Pinia, Vue Router e Tailwind CSS, servida pelo Laravel apenas como shell estático.

- `resources/spa/services/api.ts`: cliente HTTP central com Bearer token e tratamento de sessão expirada.
- `resources/spa/stores`: sessão autenticada e notificações globais.
- `resources/spa/views`: autenticação, dashboard, cadastros, usuários, configurações, NF-e e histórico.
- `app/Http/Requests`: normalização e validação dos payloads.
- `app/Http/Resources`: contratos públicos e sanitizados da API.
- `app/Http/Controllers/Api`: controllers REST dos cadastros centrais.

Para desenvolvimento do frontend:

1. Execute `npm install`.
2. Execute `npm run dev` em paralelo ao Laravel.
3. Use `npm run build` para validar tipos e gerar os assets de produção.

O Dockerfile usa build multi-stage: o Node compila a SPA e não permanece na imagem PHP final.

## Público do sistema

O FiscalFlow foi pensado para empresas, escritórios e equipes que precisam centralizar a operação fiscal, reduzir tarefas repetitivas e acompanhar as emissões em uma única tela.

## Observação importante

As regras tributárias devem ser conferidas com o responsável fiscal da empresa. CFOP, NCM, CST, CSOSN, impostos, benefícios e demais informações precisam refletir a operação real e a legislação vigente.
