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

        // Redirigir gestor de EPP a su vista exclusiva
        if ($user && $user->hasRole('gestor_epp')) {
            return redirect()->route('epp.gestion');
        }

        // Bloquear acceso a despacho en rutas de supervisor/dashboard
        if ($user && ($user->hasRole('Despacho') || $user->hasRole('despacho'))) {
            return redirect()->route('despacho.index');
        }

        // Permitir acceso a todos los usuarios autenticados
        return $next($request);
    }
}
