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

        // Bloquear roles de operarios del acceso a /dashboard admin
        // Redirigir a /operario/dashboard
        $operarioRoles = ['costura-reflectivo', 'lider-reflectivo', 'vista-costura', 'administrador-costura', 'cortador', 'costurero', 'confeccion-sobremedida'];
        
        if ($user && $user->hasAnyRole($operarioRoles)) {
            return redirect('/operario/dashboard');
        }

        // Permitir acceso a otros usuarios
        return $next($request);
    }
}
