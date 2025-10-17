<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rotas para o sistema de items/sacolinhas
Route::resource('items', App\Http\Controllers\ItemController::class);

// Rota adicional para admin (se necessário)
Route::prefix('admin')->group(function () {
    Route::resource('items', App\Http\Controllers\ItemController::class)->names([
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
Route::resource('bags', App\Http\Controllers\LiveController::class);


// Rota para dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Ou se tiver DashboardController:
// Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');


// Rotas para sacolinhas
Route::resource('sacolinhas', App\Http\Controllers\SacolinhaController::class);

// Rotas API para sacolinhas (usadas pelo JavaScript)
Route::prefix('api')->group(function () {
    Route::get('/sacolinhas/live/{liveId}', [App\Http\Controllers\SacolinhaController::class, 'getByLive']);
    Route::delete('/sacolinhas/remove', [App\Http\Controllers\SacolinhaController::class, 'removeItem']);
});

// Rotas para lives (AJAX)
Route::get('/lives', [App\Http\Controllers\LiveController::class, 'index']);
Route::post('/lives', [App\Http\Controllers\LiveController::class, 'store']);
Route::delete('/lives/{id}', [App\Http\Controllers\LiveController::class, 'destroy']);

// Rotas API para busca (usadas pelos components)
Route::prefix('api')->group(function () {
    Route::get('/users/search', [App\Http\Controllers\UserController::class, 'search']);
    Route::get('/items/search', [App\Http\Controllers\ItemController::class, 'search']);
    Route::get('/sacolinhas/live/{liveId}', [App\Http\Controllers\SacolinhaController::class, 'getByLive']);
    Route::delete('/sacolinhas/remove', [App\Http\Controllers\SacolinhaController::class, 'removeItem']);
});

// Rotas API para busca (usadas pelos components)
Route::prefix('api')->group(function () {
    Route::get('/items/search', [App\Http\Controllers\ItemController::class, 'search']);
    Route::get('/users/search', [App\Http\Controllers\UserController::class, 'search']);
    Route::get('/sacolinhas/live/{liveId}', [App\Http\Controllers\SacolinhaController::class, 'getByLive']);
    Route::delete('/sacolinhas/remove', [App\Http\Controllers\SacolinhaController::class, 'removeItem']);
});