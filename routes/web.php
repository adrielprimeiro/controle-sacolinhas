<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SacolinhaController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\Admin\AdminSacolinhaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ===== ROTAS PÚBLICAS =====

Route::get('/', function () {
    return view('welcome');
});

// ===== ROTAS DE AUTENTICAÇÃO =====

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
    ])->onlyInput('email');
});

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);
    return redirect('/login');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/password/reset', function () {
    return view('auth.forgot-password');
})->name('password.request');

// ===== ROTAS PROTEGIDAS =====

Route::middleware('auth')->group(function () {
    
    // ===== DASHBOARD =====
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/home', function () {
        return redirect('/dashboard');
    })->name('home');

    // ===== ITEMS =====
    Route::resource('items', ItemController::class);
    
    // ===== LIVES =====
    Route::resource('lives', LiveController::class)->except(['create', 'edit']);
    Route::get('/bags', [LiveController::class, 'index'])->name('bags.index');
    
    // ===== SACOLINHAS =====
    // Rotas principais
    Route::get('/sacolinhas', [SacolinhaController::class, 'index'])->name('sacolinhas.index');
    Route::post('/sacolinhas', [SacolinhaController::class, 'store'])->name('sacolinhas.store');
    Route::get('/sacolinhas/{sacolinha}', [SacolinhaController::class, 'show'])->name('sacolinhas.show');
    Route::put('/sacolinhas/{sacolinha}', [SacolinhaController::class, 'update'])->name('sacolinhas.update');
    Route::delete('/sacolinhas/{sacolinha}', [SacolinhaController::class, 'destroy'])->name('sacolinhas.destroy');
    
    // Ações específicas de sacolinhas
    Route::post('/sacolinhas/close-live', [SacolinhaController::class, 'closeLive'])->name('sacolinhas.close-live');
    
    // ===== API ROUTES =====
    Route::prefix('api')->name('api.')->group(function () {
        
        // Users API
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        
        // Items API
        Route::get('/items/search', [ItemController::class, 'search'])->name('items.search');
        
        // Sacolinhas API
        Route::prefix('sacolinhas')->name('sacolinhas.')->group(function () {
            // Lives
            Route::get('/live', [SacolinhaController::class, 'getActiveLive'])->name('active-live');
            Route::post('/live', [SacolinhaController::class, 'createLive'])->name('create-live');
            Route::get('/live/{liveId?}', [SacolinhaController::class, 'getBagsByLive'])->name('live');
            
            // Ações com itens
            Route::delete('/remove', [SacolinhaController::class, 'removeItem'])->name('remove');
            Route::delete('/clear', [SacolinhaController::class, 'clearClientBag'])->name('clear');
            Route::patch('/status', [SacolinhaController::class, 'updateItemStatus'])->name('status');
            
            // Estatísticas
            Route::get('/stats/{liveId?}', [SacolinhaController::class, 'getLiveStats'])->name('stats');
        });
    });
    
    // ===== ADMIN ROUTES =====
    Route::prefix('admin')->name('admin.')->group(function () {
        
        // Admin Items
        Route::resource('items', ItemController::class)->names([
            'index' => 'items.index',
            'create' => 'items.create',
            'store' => 'items.store',
            'show' => 'items.show',
            'edit' => 'items.edit',
            'update' => 'items.update',
            'destroy' => 'items.destroy',
        ]);
        
		// Admin Sacolinhas
		Route::prefix('sacolinhas')->name('sacolinhas.')->group(function () {
			// Rotas principais
			Route::get('/', [AdminSacolinhaController::class, 'index'])->name('index');
			Route::get('/live/{live}', [AdminSacolinhaController::class, 'show'])->name('show');
			Route::get('/search-client', [AdminSacolinhaController::class, 'searchByClient'])->name('search-client');
			Route::get('/export/{live}', [AdminSacolinhaController::class, 'export'])->name('export');
			
			// ✅ ADICIONAR ESTA LINHA
			Route::get('/live/{live}/sacolinhas', [AdminSacolinhaController::class, 'getSacolinhasByLive'])->name('by-live');
			
			// Ações
			Route::post('/bulk-action', [AdminSacolinhaController::class, 'bulkAction'])->name('bulk-action');
			Route::patch('/{sacolinha}/status', [AdminSacolinhaController::class, 'updateStatus'])->name('update-status');
			
			// AJAX
			Route::get('/{sacolinha}/details', [AdminSacolinhaController::class, 'details'])->name('details');
		});
    });
});

// ===== ROTAS DE FALLBACK =====

// Redirecionar rotas antigas se necessário
Route::get('/lives', function () {
    return redirect()->route('bags.index');
});

// Rota para buscar sacolinhas de uma live específica (AJAX)
Route::get('/admin/sacolinhas/live/{live}/sacolinhas', [SacolinhaController::class, 'getSacolinhasByLive'])
    ->name('admin.sacolinhas.by-live');