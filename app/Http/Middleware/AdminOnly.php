// app/Http/Middleware/AdminOnly.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->tipo !== 'admin') {
            abort(403, 'Acesso negado');
        }

        return $next($request);
    }
}