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

        // Verificar si tiene rol de cortador, costurero, bodeguero, costura-reflectivo, lider-reflectivo o vista-costura
        if (!$usuario->hasAnyRole(['cortador', 'costurero', 'bodeguero', 'costura-reflectivo', 'lider-reflectivo', 'vista-costura', 'administrador-costura', 'confeccion-sobremedida'])) {
            return redirect()->route('login')
                ->with('error', 'No tienes acceso a esta sección');
        }

        return $next($request);
    }
}
