<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'spa')->name('login');
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
