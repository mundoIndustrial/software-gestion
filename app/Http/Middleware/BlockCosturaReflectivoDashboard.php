<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: BlockCosturaReflectivoDashboard
 * 
 * Bloquea el acceso del rol "costura-reflectivo" a /dashboard
 * Redirige automaticamente a /operario/dashboard
 * 
 * Uso en routes:
 * Route::middleware('block-costura-reflectivo-dashboard')->get('/dashboard', ...)
 */
class BlockCosturaReflectivoDashboard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        // Bloquear costura-reflectivo del acceso a /dashboard
        // Redirigir a /operario/dashboard
        if ($user && $user->hasRole('costura-reflectivo')) {
            return redirect('/operario/dashboard');
        }

        // Permitir acceso a otros usuarios
        return $next($request);
    }
}
