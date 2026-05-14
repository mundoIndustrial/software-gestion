<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictGestionBodega
{
    /**
     * Restringe el rol gestion-bodega a su módulo de recibos-bodega.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Si además es admin, no restringir.
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        if (!$user->hasRole('gestion-bodega')) {
            return $next($request);
        }

        $allowed = $request->is('dashboard')
            || $request->is('recibos-bodega')
            || $request->is('api/recibo-corte-bodega*')
            || $request->is('api/recibos-bodega*')
            || $request->is('api/tabla-original-bodega*')
            || $request->is('api/registros-por-orden-bodega*')
            || $request->is('refresh-csrf')
            || $request->is('logout');

        if (!$allowed) {
            return redirect()->route('registros.recibos-bodega');
        }

        return $next($request);
    }
}

