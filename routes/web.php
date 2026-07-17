<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/onboarding/store', [StoreController::class, 'create'])->name('onboarding.store');
    Route::post('/onboarding/store', [StoreController::class, 'store'])->name('onboarding.store.save');
    Route::post('/painel/selecionar-loja/{store}', [StoreController::class, 'select'])->name('stores.select');

    Route::middleware('store.member')->group(function (): void {
        Route::get('/painel', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/painel/{page}', [DashboardController::class, 'page'])
            ->whereIn('page', ['produtos', 'pedidos', 'clientes', 'mensagens', 'aparencia', 'configuracoes', 'equipe'])
            ->name('panel.page');
    });
});
