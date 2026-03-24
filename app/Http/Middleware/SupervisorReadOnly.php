<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisorReadOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Permitir todos los métodos a admin
        if ($user && $user->hasRole('admin')) {
            return $next($request);
        }
        
        // Permitir solo métodos GET y HEAD para supervisores (pero no para admin)
        if ($user && $user->role && $user->role->name === 'supervisor') {
            // Solo permitir lectura (GET, HEAD, OPTIONS)
            if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
                return response()->json(['error' => 'Los supervisores solo tienen acceso de lectura'], 403);
            }
        }

        return $next($request);
    }
}
