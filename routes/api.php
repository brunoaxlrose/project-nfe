<?php

use App\Http\Controllers\NfeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaturamentoController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EmpresaConfiguracaoController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(['json.payload', 'throttle:10,1']);
Route::post('/auth/register', [RegisterController::class, 'store'])
    ->middleware(['json.payload', 'throttle:5,1']);

Route::middleware(['jwt', 'json.payload'])->group(function (): void {
    Route::get('/destinatarios', [CatalogController::class, 'destinatarios'])->middleware('permission:clientes.visualizar');
    Route::get('/destinatarios/buscar', [CatalogController::class, 'destinatariosBuscar'])->middleware('permission:clientes.visualizar');
    Route::get('/naturezas-operacao', [CatalogController::class, 'naturezas'])->middleware('permission:naturezas.visualizar');
    Route::get('/clientes/buscar', [CatalogController::class, 'clientesBuscar'])->middleware('permission:clientes.visualizar');
    Route::post('/clientes/importar', [CatalogController::class, 'importarCliente'])->middleware('permission:clientes.criar');
    Route::get('/produtos/buscar', [CatalogController::class, 'produtosBuscar'])->middleware('permission:produtos.visualizar');
    Route::post('/nfe', [NfeController::class, 'store'])
        ->middleware('permission:nfe.criar');
    Route::get('/nfe/proximo-numero', [NfeController::class, 'nextNumber'])
        ->middleware('permission:nfe.criar');
    Route::post('/nfe/{nfe}/consultar', [NfeController::class, 'consult'])
        ->middleware('permission:nfe.consultar');

    Route::get('/faturamento/notas', [FaturamentoController::class, 'index'])
        ->middleware('permission:nfe.visualizar');
    Route::get('/faturamento/notas/{nfe}/download', [FaturamentoController::class, 'download'])
        ->middleware('permission:nfe.baixar');
    Route::post('/faturamento/notas/{nfe}/clonar', [FaturamentoController::class, 'clone'])
        ->middleware('permission:nfe.clonar');
    Route::post('/faturamento/notas/{nfe}/cancelar', [FaturamentoController::class, 'cancelar'])
        ->middleware('permission:nfe.cancelar');
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
    Route::put('/perfis/{perfil}/permissoes', [UsuarioController::class, 'atualizarPermissoes'])->middleware('permission:perfis.gerenciar');
});
