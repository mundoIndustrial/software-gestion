<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para asegurar que la sesión esté disponible en rutas API
 * 
 * Este middleware simplemente permite que peticiones AJAX autenticadas
 * mantengan acceso a la sesión del usuario
 * 
 * Uso: middleware('load-session-api')
 */
class LoadSessionForApi
{
    /**
     * Handle the request.
     */
    public function handle(Request $request, Closure $next)
    {
        // El middleware 'web' ya debería haber cargado la sesión
        // Solo log para debugging
        if (auth('web')->check()) {
            \Log::debug('[LoadSessionForApi] Usuario autenticado:', [
                'user_id' => auth('web')->user()->id,
                'name' => auth('web')->user()->name,
            ]);
        }
        
        return $next($request);
    }
}
