<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'spa')->name('login');
Route::view('/pagamento', 'spa')->name('pagamento');
Route::get('/403', fn () => response()->view('spa', [], 403))->name('forbidden');
Route::get('/404', fn () => response()->view('spa', [], 404))->name('not-found');
// Cadastro público de empresas desativado.
// Route::view('/register', 'spa')->name('register');
Route::view('/', 'spa');
Route::view('/dashboard', 'spa')->name('dashboard');
Route::view('/dashboard/nfe/nova', 'spa')->name('nfe.create');
Route::view('/dashboard/configuracoes', 'spa')->name('configuracoes');
Route::view('/dashboard/notas', 'spa')->name('nfe.index');
Route::view('/dashboard/usuarios', 'spa')->name('usuarios.index');
Route::view('/dashboard/fornecedores', 'spa')->name('fornecedores.index');
Route::view('/dashboard/clientes', 'spa')->name('clientes.index');
Route::view('/dashboard/naturezas', 'spa')->name('naturezas.index');
Route::view('/dashboard/produtos', 'spa')->name('produtos.index');

// Fallback do SPA: ao atualizar uma rota interna, entrega a aplicação para o
// Vue Router resolver a página. Restrito a /dashboard para não capturar /api.
Route::view('/dashboard/{path?}', 'spa')
    ->where('path', '.*')
    ->name('spa.dashboard');

Route::fallback(fn () => response()->view('spa', [], 404));
