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

// ===== ROTAS PÚBLICAS =====

Route::get('/', function () {
    return view('welcome');
});

// ===== ROTAS PROTEGIDAS =====

Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::get('/home', function () {
        return redirect('/dashboard');
    })->name('home');

    // Items
    Route::resource('items', ItemController::class);
    
    // Admin Items
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

    // Lives e Bags
    Route::resource('bags', LiveController::class);
    Route::resource('sacolinhas', SacolinhaController::class);
    Route::resource('lives', LiveController::class)->except(['create', 'edit']);
    Route::get('/bags', [LiveController::class, 'index'])->name('bags.index');
    
    // AJAX Lives
    Route::get('/lives', [LiveController::class, 'index']);
    Route::post('/lives', [LiveController::class, 'store']);
    Route::delete('/lives/{id}', [LiveController::class, 'destroy']);

    // API Routes
    Route::prefix('api')->group(function () {
        Route::get('/users/search', [UserController::class, 'search']);
        Route::get('/items/search', [ItemController::class, 'search']);
        Route::get('/sacolinhas/live/{liveId?}', [SacolinhaController::class, 'getBagsByLive']);
        Route::delete('/sacolinhas/remove', [SacolinhaController::class, 'removeItem']);
    });
});

// Rotas de administração de sacolinhas
Route::prefix('admin')->middleware('auth')->group(function () {
    // Rotas principais
    Route::get('/sacolinhas', [AdminSacolinhaController::class, 'index'])->name('admin.sacolinhas.index');
    Route::get('/sacolinhas/live/{live}', [AdminSacolinhaController::class, 'show'])->name('admin.sacolinhas.show');
    Route::get('/sacolinhas/search-client', [AdminSacolinhaController::class, 'searchByClient'])->name('admin.sacolinhas.search-client');
    
    // Ações
    Route::post('/sacolinhas/bulk-action', [AdminSacolinhaController::class, 'bulkAction'])->name('admin.sacolinhas.bulk-action');
    Route::patch('/sacolinhas/{sacolinha}/status', [AdminSacolinhaController::class, 'updateStatus'])->name('admin.sacolinhas.update-status');
    
    // AJAX
    Route::get('/sacolinhas/{sacolinha}/details', [AdminSacolinhaController::class, 'details'])->name('admin.sacolinhas.details');
});


// routes/web.php
Route::get('/admin/sacolinhas/export/{live}', [AdminSacolinhaController::class, 'export'])
    ->name('admin.sacolinhas.export');