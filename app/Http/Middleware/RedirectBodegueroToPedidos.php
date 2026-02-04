<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectBodegueroToPedidos
{
    /**
     * Redireccionar bodeguero a la gestión de pedidos
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario autenticado es bodeguero
        if (auth()->check() && auth()->user()->hasRole('bodeguero')) {
            // Si intenta acceder al dashboard, redirigir a gestión de pedidos
            if ($request->routeIs('dashboard')) {
                return redirect()->route('bodega.pedidos');
            }
        }

        return $next($request);
    }
}
