public function handle(Request $request, Closure $next, ...$guards)
{
    $guards = empty($guards) ? [null] : $guards;

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            return redirect(RouteServiceProvider::HOME); // Certifique-se que RouteServiceProvider::HOME aponta para o lugar certo.
        }
    }

    return $next($request);
}