<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectVisualizadorPedidos
{
    /**
     * Redireccionar visualizador-pedidos al módulo de visualización de pedidos
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario autenticado es visualizador-pedidos
        if (auth()->check() && auth()->user()->hasRole('visualizador-pedidos')) {
            // Si intenta acceder al dashboard, redirigir al módulo de visualización de pedidos
            if ($request->routeIs('dashboard')) {
                return redirect('/visualizador-pedidos');
            }
            
            // Si intenta acceder a cualquier otra ruta que no sea del módulo visualizador-pedidos, redirigir
            if (!$request->routeIs('visualizador-pedidos.*') && !$request->routeIs('profile.*') && !$request->routeIs('logout')) {
                return redirect('/visualizador-pedidos');
            }
        }

        // Si el usuario autenticado es visualizador_talleres
        if (auth()->check() && auth()->user()->hasRole('visualizador_talleres')) {
            // Si intenta acceder al dashboard, redirigir al módulo de entrada
            if ($request->routeIs('dashboard')) {
                return redirect('/entrada');
            }

            // Permitir acceso a talleres, entrada, profile y logout
            if (
                !$request->routeIs('talleres.*')
                && !$request->routeIs('entrada.*')
                && !$request->routeIs('profile.*')
                && !$request->routeIs('logout')
            ) {
                return redirect('/entrada');
            }
        }

        return $next($request);
    }
}
