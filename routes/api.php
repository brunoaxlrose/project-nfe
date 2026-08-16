<?php

use App\Http\Controllers\NfeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaturamentoController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\EmpresaConfiguracaoController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(['json.payload', 'throttle:10,1']);
Route::post('/auth/register', [RegisterController::class, 'store'])
    ->middleware(['json.payload', 'throttle:5,1']);

Route::middleware(['jwt', 'json.payload'])->group(function (): void {
    Route::get('/destinatarios', [CatalogController::class, 'destinatarios'])->middleware('role:Administrador,Operador');
    Route::get('/destinatarios/buscar', [CatalogController::class, 'destinatariosBuscar'])->middleware('role:Administrador,Operador');
    Route::get('/naturezas-operacao', [CatalogController::class, 'naturezas'])->middleware('role:Administrador,Operador');
    Route::get('/clientes/buscar', [CatalogController::class, 'clientesBuscar'])->middleware('role:Administrador,Operador');
    Route::post('/clientes/importar', [CatalogController::class, 'importarCliente'])->middleware('role:Administrador,Operador');
    Route::get('/produtos/buscar', [CatalogController::class, 'produtosBuscar'])->middleware('role:Administrador,Operador');
    Route::post('/nfe', [NfeController::class, 'store'])
        ->middleware('role:Administrador,Operador');
    Route::get('/nfe/proximo-numero', [NfeController::class, 'nextNumber'])
        ->middleware('role:Administrador,Operador');
    Route::post('/nfe/{nfe}/consultar', [NfeController::class, 'consult'])
        ->middleware('role:Administrador,Operador');

    Route::get('/faturamento/notas', [FaturamentoController::class, 'index'])
        ->middleware('role:Administrador,Operador');
    Route::get('/faturamento/notas/{nfe}/download', [FaturamentoController::class, 'download'])
        ->middleware('role:Administrador,Operador');
    Route::post('/faturamento/notas/{nfe}/clonar', [FaturamentoController::class, 'clone'])
        ->middleware('role:Administrador,Operador');
    Route::post('/faturamento/notas/{nfe}/cancelar', [FaturamentoController::class, 'cancelar'])
        ->middleware('role:Administrador');
    Route::post('/faturamento/notas/{nfe}/cce', [FaturamentoController::class, 'cartaCorrecao'])
        ->middleware('role:Administrador');
});

Route::middleware(['jwt'])->group(function (): void {
    Route::get('/configuracoes-emissor', [EmpresaConfiguracaoController::class, 'show'])->middleware('role:Administrador');
    Route::post('/configuracoes-emissor', [EmpresaConfiguracaoController::class, 'update'])->middleware('role:Administrador');
    Route::post('/configuracoes-emissor/testar-comunicacao', [EmpresaConfiguracaoController::class, 'testarComunicacao'])->middleware('role:Administrador');
    Route::delete('/configuracoes-emissor/certificado', [EmpresaConfiguracaoController::class, 'removerCertificado'])->middleware('role:Administrador');
});
