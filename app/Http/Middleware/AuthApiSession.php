<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para autenticación en APIs usando sesión
 * 
 * Similar a auth:web pero NO redirige a login en caso de fallo
 * Devuelve 401 Unauthorized en su lugar
 * 
 * Uso: middleware('auth-api-session')
 */
class AuthApiSession
{
    public function handle(Request $request, Closure $next)
    {
        // Verificar si hay usuario autenticado
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'success' => false
            ], 401);
        }

        return $next($request);
    }
}
