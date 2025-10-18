<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SacolinhaController;
use App\Http\Controllers\LiveController;

Route::get('/', function () {
    return view('welcome');
});

// ===== ROTAS WEB (HTML) =====

// Rotas para o sistema de items/sacolinhas
Route::resource('items', ItemController::class);

// Rota adicional para admin
Route::prefix('admin')->group(function () {
    Route::resource('items', ItemController::class)->names([
        'index' => 'admin.items.index',
        'create' => 'admin.items.create',
        'store' => 'admin.items.store',
        'show' => 'admin.items.show',
        'edit' => 'admin.items.edit',
        'update' => 'admin.items.update',
        'destroy' => 'admin.items.destroy',
    ]);
});

// Rota para bags/sacolinhas (LiveController)
Route::resource('bags', LiveController::class);

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Rotas para sacolinhas (WEB)
Route::resource('sacolinhas', SacolinhaController::class);

// ===== ROTAS AJAX/API (JSON) =====

// Rotas para lives (AJAX)
Route::get('/lives', [LiveController::class, 'index']);
Route::post('/lives', [LiveController::class, 'store']);
Route::delete('/lives/{id}', [LiveController::class, 'destroy']);

// Rotas API (todas em um grupo só)
Route::prefix('api')->group(function () {
    // Busca de usuários e itens
    Route::get('/users/search', [UserController::class, 'search']);
    Route::get('/items/search', [ItemController::class, 'search']);
    
    // Sacolas
    Route::get('/sacolinhas/live/{liveId?}', [SacolinhaController::class, 'getBagsByLive']); // ← CORRIGIDO
    Route::delete('/sacolinhas/remove', [SacolinhaController::class, 'removeItem']);
});