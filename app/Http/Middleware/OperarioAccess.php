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
            \Log::warning('[OperarioAccess] Usuario no autenticado');
            return redirect()->route('login');
        }

        $usuario = auth()->user();
        $userRoles = $usuario->roles->pluck('name')->toArray();
        
        \Log::info('[OperarioAccess] Verificando acceso', [
            'user_id' => $usuario->id,
            'user_name' => $usuario->name,
            'user_roles' => $userRoles,
            'permitedRoles' => ['cortador', 'costurero', 'bodeguero', 'costura-reflectivo', 'lider-reflectivo', 'vista-costura', 'administrador-costura', 'confeccion-sobremedida']
        ]);

        // Verificar si tiene rol de cortador, costurero, bodeguero, costura-reflectivo, lider-reflectivo o vista-costura
        if (!$usuario->hasAnyRole(['cortador', 'costurero', 'bodeguero', 'costura-reflectivo', 'lider-reflectivo', 'vista-costura', 'administrador-costura', 'confeccion-sobremedida'])) {
            \Log::error('[OperarioAccess] Acceso denegado - rol no autorizado', [
                'user_id' => $usuario->id,
                'user_roles' => $userRoles
            ]);
            return redirect()->route('login')
                ->with('error', 'No tienes acceso a esta sección');
        }

        \Log::info('[OperarioAccess] Acceso permitido');
        return $next($request);
    }
}
