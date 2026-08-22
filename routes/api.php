<?php

use App\Http\Controllers\NfeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaturamentoController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EmpresaConfiguracaoController;
// use App\Http\Controllers\RegisterController; // Cadastro público desativado.
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\FornecedorController;
use App\Http\Controllers\Api\NaturezaOperacaoController;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(['json.payload', 'throttle:10,1']);
// Cadastro público de empresas desativado.
// Route::post('/auth/register', [RegisterController::class, 'store'])
//     ->middleware(['json.payload', 'throttle:5,1']);

Route::middleware(['jwt', 'json.payload'])->group(function (): void {
    Route::get('/destinatarios', [CatalogController::class, 'destinatarios'])->middleware('permission:clientes.visualizar');
    Route::get('/destinatarios/buscar', [CatalogController::class, 'destinatariosBuscar'])->middleware('permission:clientes.visualizar');
    
    Route::apiResource('fornecedores', FornecedorController::class)
        ->parameters(['fornecedores' => 'fornecedor'])
        ->middlewareFor(['index', 'show'], 'permission:fornecedores.visualizar')
        ->middlewareFor('store', 'permission:fornecedores.criar')
        ->middlewareFor('update', 'permission:fornecedores.editar')
        ->middlewareFor('destroy', 'permission:fornecedores.excluir');
    Route::apiResource('naturezas-operacao', NaturezaOperacaoController::class)
        ->parameters(['naturezas-operacao' => 'natureza'])
        ->middlewareFor(['index', 'show'], 'permission:naturezas.visualizar')
        ->middlewareFor('store', 'permission:naturezas.criar')
        ->middlewareFor('update', 'permission:naturezas.editar')
        ->middlewareFor('destroy', 'permission:naturezas.excluir');

    Route::get('/clientes/buscar', [CatalogController::class, 'clientesBuscar'])->middleware('permission:clientes.visualizar');
    Route::post('/clientes/importar', [CatalogController::class, 'importarCliente'])->middleware('permission:clientes.criar');
    Route::get('/produtos/buscar', [CatalogController::class, 'produtosBuscar'])->middleware('permission:produtos.visualizar');
    Route::apiResource('clientes', ClienteController::class)
        ->middlewareFor(['index', 'show'], 'permission:clientes.visualizar')
        ->middlewareFor('store', 'permission:clientes.criar')
        ->middlewareFor('update', 'permission:clientes.editar')
        ->middlewareFor('destroy', 'permission:clientes.excluir');
    Route::apiResource('produtos', ProdutoController::class)
        ->middlewareFor(['index', 'show'], 'permission:produtos.visualizar')
        ->middlewareFor('store', 'permission:produtos.criar')
        ->middlewareFor('update', 'permission:produtos.editar')
        ->middlewareFor('destroy', 'permission:produtos.excluir');
    Route::post('/nfe', [NfeController::class, 'store'])
        ->middleware('permission:nfe.criar');
    Route::get('/nfe/destinatarios', [NfeController::class, 'recipients'])
        ->middleware('permission:nfe.criar');
    Route::get('/nfe/proximo-numero', [NfeController::class, 'nextNumber'])
        ->middleware('permission:nfe.criar');
    Route::post('/nfe/{nfe}/consultar', [NfeController::class, 'consult'])
        ->middleware('permission:nfe.consultar');
    Route::post('/nfe/{nfe}/emitir', [NfeController::class, 'emitirPendente'])
        ->middleware('permission:nfe.criar');

    Route::get('/faturamento/notas', [FaturamentoController::class, 'index'])
        ->middleware('permission:nfe.visualizar');
    Route::get('/faturamento/resumo', [FaturamentoController::class, 'resumo'])
        ->middleware('permission:nfe.visualizar');
    Route::get('/faturamento/notas/{nfe}/download', [FaturamentoController::class, 'download'])
        ->middleware('permission:nfe.baixar');
    Route::post('/faturamento/notas/{nfe}/clonar', [FaturamentoController::class, 'clone'])
        ->middleware('permission:nfe.clonar');
    Route::post('/faturamento/notas/{nfe}/cancelar', [FaturamentoController::class, 'cancelar'])
        ->middleware('permission:nfe.cancelar');
    Route::delete('/faturamento/notas/{nfe}', [FaturamentoController::class, 'destroy'])
        ->middleware('permission:nfe.cancelar'); // Using cancel permission to allow deleting drafts or delete permission if available. Let's use permission:nfe.cancelar
    Route::post('/faturamento/notas/{nfe}/cce', [FaturamentoController::class, 'cartaCorrecao'])
        ->middleware('permission:nfe.cce');
});

Route::middleware(['jwt'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/configuracoes-emissor', [EmpresaConfiguracaoController::class, 'show'])->middleware('permission:configuracoes.visualizar');
    Route::post('/configuracoes-emissor', [EmpresaConfiguracaoController::class, 'update'])->middleware('permission:configuracoes.editar');
    Route::post('/configuracoes-emissor/testar-comunicacao', [EmpresaConfiguracaoController::class, 'testarComunicacao'])->middleware('permission:configuracoes.editar');
    Route::delete('/configuracoes-emissor/certificado', [EmpresaConfiguracaoController::class, 'removerCertificado'])->middleware('permission:certificado.gerenciar');
    Route::get('/usuarios', [UsuarioController::class, 'index'])->middleware('permission:usuarios.visualizar');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->middleware('permission:usuarios.criar');
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->middleware('permission:usuarios.editar');
    Route::get('/perfis', [UsuarioController::class, 'perfis'])->middleware('permission:perfis.gerenciar');
    Route::post('/perfis', [UsuarioController::class, 'storePerfil'])->middleware('permission:perfis.gerenciar');
    Route::put('/perfis/{perfil}', [UsuarioController::class, 'updatePerfil'])->middleware('permission:perfis.gerenciar');
    Route::delete('/perfis/{perfil}', [UsuarioController::class, 'destroyPerfil'])->middleware('permission:perfis.gerenciar');
    Route::post('/perfis/{perfil}/usuarios', [UsuarioController::class, 'adicionarUsuarioPerfil'])->middleware('permission:perfis.gerenciar');
    Route::delete('/perfis/{perfil}/usuarios/{usuario}', [UsuarioController::class, 'removerUsuarioPerfil'])->middleware('permission:perfis.gerenciar');
    Route::put('/perfis/{perfil}/permissoes', [UsuarioController::class, 'atualizarPermissoes'])->middleware('permission:perfis.gerenciar');
});
