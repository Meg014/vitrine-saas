<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerController;
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
        Route::get('/painel/categorias', [CatalogController::class, 'categories'])->name('categories.index');
        Route::get('/painel/atributos', [CatalogController::class, 'attributes'])->name('attributes.index');
        Route::get('/painel/produtos', [CatalogController::class, 'products'])->name('products.index');
        Route::get('/painel/produtos/criar', [CatalogController::class, 'create'])->name('products.create');
        Route::get('/painel/produtos/{product}/editar', [CatalogController::class, 'edit'])->name('products.edit');
        Route::get('/painel/estoque', [CatalogController::class, 'inventory'])->name('inventory.index');
        Route::get('/painel/estoque/movimentacoes', [CatalogController::class, 'movements'])->name('inventory.movements');
        Route::get('/painel/clientes', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/painel/clientes/criar', [CustomerController::class, 'create'])->name('customers.create');
        Route::get('/painel/clientes/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('/painel/clientes/{customer}/editar', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::get('/painel/mensagens', [CustomerController::class, 'messages'])->name('messages.index');
        Route::get('/painel/mensagens/{customerMessage}', [CustomerController::class, 'message'])->name('messages.show');
        Route::get('/painel/{page}', [DashboardController::class, 'page'])
            ->whereIn('page', ['pedidos', 'aparencia', 'configuracoes', 'equipe'])
            ->name('panel.page');
    });
});
