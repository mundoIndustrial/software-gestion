<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToLoginIfUnauthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario NO está autenticado, redirigir a login
        if (!auth()->check()) {
            // Si es una petición AJAX, devolver JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No estás autenticado. Por favor, inicia sesión.',
                    'redirect' => route('login')
                ], 401);
            }

            // Para peticiones normales, redirigir a login con mensaje
            return redirect()->route('login')
                ->with('error', 'No tienes acceso a esta página. Debes estar autenticado.')
                ->with('intended', $request->url());
        }

        return $next($request);
    }
}
