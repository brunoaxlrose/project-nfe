<?php

use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::redirect('/', '/dashboard');
Route::get('/dashboard', [WorkspaceController::class, 'dashboard'])->name('dashboard');
Route::get('/dashboard/nfe/nova', [WorkspaceController::class, 'nfeCreate'])->name('nfe.create');
Route::get('/dashboard/configuracoes', [WorkspaceController::class, 'configuracoes'])->name('configuracoes');
Route::get('/dashboard/notas', [WorkspaceController::class, 'nfeIndex'])->name('nfe.index');
Route::get('/dashboard/usuarios', [WorkspaceController::class, 'usuarios'])->name('usuarios.index');
