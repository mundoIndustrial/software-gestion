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
            'permitedRoles' => ['cortador', 'costurero', 'bodeguero', 'costura-reflectivo', 'lider-reflectivo', 'lider-costura', 'vista-costura', 'administrador-costura', 'confeccion-sobremedida', 'visualizador_plooter', 'visualizador_ordenes_produccion']
        ]);

        // Verificar si tiene rol permitido para modulo operario
        if (!$usuario->hasAnyRole(['cortador', 'costurero', 'bodeguero', 'costura-reflectivo', 'lider-reflectivo', 'lider-costura', 'vista-costura', 'administrador-costura', 'confeccion-sobremedida', 'visualizador_plooter', 'visualizador_ordenes_produccion'])) {
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
