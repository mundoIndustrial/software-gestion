<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorAccessControl
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
        if ($user && $user->hasRole('costura-reflectivo')) {
            return redirect('/operario/dashboard');
        }

        // Bloquear administrador-costura del acceso a /dashboard
        if ($user && $user->hasRole('administrador-costura')) {
            return redirect('/operario/dashboard');
        }

        // Bloquear vista-costura del acceso a /dashboard
        if ($user && $user->hasRole('vista-costura')) {
            return redirect('/operario/dashboard');
        }

        // Bloquear cortador del acceso a /dashboard
        if ($user && $user->hasRole('cortador')) {
            return redirect('/operario/dashboard');
        }

        // Bloquear costurero del acceso a /dashboard
        if ($user && $user->hasRole('costurero')) {
            return redirect('/operario/dashboard');
        }

        // Redirigir gestor de EPP a su vista exclusiva
        if ($user && $user->hasRole('gestor_epp')) {
            return redirect()->route('epp.inicio');
        }

        // Bloquear acceso a despacho en rutas de supervisor/dashboard
        if ($user && ($user->hasRole('Despacho') || $user->hasRole('despacho'))) {
            return redirect()->route('despacho.index');
        }

        // Permitir acceso a todos los usuarios autenticados
        return $next($request);
    }
}
