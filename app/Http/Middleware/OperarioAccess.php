<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: OperarioAccess
 *
 * Verifica que el usuario tenga rol de cortador o costurero
 * Redirige al login si no tiene acceso
 */
class OperarioAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $usuario = auth()->user();

        // Verificar si tiene rol de cortador o costurero
        if (!$usuario->hasAnyRole(['cortador', 'costurero'])) {
            return redirect()->route('login')
                ->with('error', 'No tienes acceso a esta sección');
        }

        return $next($request);
    }
}
