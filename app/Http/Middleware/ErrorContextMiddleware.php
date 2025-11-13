<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            // Agregar contexto adicional para el manejo de errores
            $request->attributes->set('error_context', [
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);
            
            throw $e;
        }
    }
}
