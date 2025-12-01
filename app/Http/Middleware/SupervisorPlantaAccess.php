<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorPlantaAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir acceso a supervisor_planta a todas las rutas
        if (auth()->check() && auth()->user()->role?->name === 'supervisor_planta') {
            return $next($request);
        }

        // Si no es supervisor_planta, permitir que continúe (otros middlewares se encargarán)
        return $next($request);
    }
}
