<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SacolinhaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas de busca (sem autenticação)
Route::get('/users/search', [UserController::class, 'search'])->name('api.users.search');
Route::get('/items/search', [ItemController::class, 'search'])->name('api.items.search');

// Rotas das sacolinhas
Route::get('/sacolinhas/live', [SacolinhaController::class, 'getActiveLive'])->name('api.sacolinhas.active-live');
Route::post('/sacolinhas/live', [SacolinhaController::class, 'createLive'])->name('api.sacolinhas.create-live');
Route::get('/sacolinhas/live/{liveId?}', [SacolinhaController::class, 'getBagsByLive'])->name('api.sacolinhas.live');
Route::delete('/sacolinhas/remove', [SacolinhaController::class, 'removeItem'])->name('api.sacolinhas.remove');
Route::get('/sacolinhas/stats/{liveId?}', [SacolinhaController::class, 'getLiveStats'])->name('api.sacolinhas.stats');
Route::patch('/sacolinhas/status', [SacolinhaController::class, 'updateItemStatus'])->name('api.sacolinhas.status');
Route::delete('/sacolinhas/clear', [SacolinhaController::class, 'clearClientBag'])->name('api.sacolinhas.clear');

// Rota padrão do usuário autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rota de teste
Route::get('/test', function() {
    return response()->json(['message' => 'API funcionando!', 'timestamp' => now()]);
});
