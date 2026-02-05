<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictBodegaRoles
{
    /**
     * Handle an incoming request.
     * Restringe usuarios con roles de bodega a solo acceder a rutas de bodega
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        // Obtener roles del usuario
        $rolesIds = $user->roles_ids;
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }
        if (!is_array($rolesIds)) {
            $rolesIds = [];
        }

        // Obtener nombres de los roles
        $userRoles = [];
        if (!empty($rolesIds)) {
            $userRoles = \App\Models\Role::whereIn('id', $rolesIds)->pluck('name')->toArray();
        }

        // Verificar si es Costura-Bodega o EPP-Bodega
        $isBodegaRole = in_array('Costura-Bodega', $userRoles) || in_array('EPP-Bodega', $userRoles);

        // Si es bodeguero especializado, solo puede acceder a /gestion-bodega/*
        if ($isBodegaRole) {
            // Permitir solo rutas de bodega
            if (!$request->is('gestion-bodega*')) {
                return redirect()->route('gestion-bodega.pedidos');
            }
        }

        return $next($request);
    }
}
