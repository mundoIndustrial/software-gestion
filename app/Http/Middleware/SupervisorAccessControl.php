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
            return $next($request);
        }

        $user = auth()->user();
        
        // Si el usuario es supervisor, permitir acceso (será controlado por supervisor-readonly)
        if ($user->role && $user->role->name === 'supervisor') {
            return $next($request);
        }

        return $next($request);
    }
}
