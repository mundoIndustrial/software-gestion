<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBodegaAccess
{
    /**
     * Middleware específico para bodega
     * Requiere uno de: bodeguero, Costura-Bodega, EPP-Bodega
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();
        $allowedRoles = [
            'bodeguero', 
            'Costura-Bodega', 
            'EPP-Bodega',
            'admin',
            'supervisor-admin',
            'supervisor_planta',
            'despacho'
        ];
        
        // Obtener roles del usuario
        $userRoles = $user->getRoleNames()->toArray();

        \Log::info('[BODEGA-MIDDLEWARE] Verificando acceso', [
            'usuario' => $user->email,
            'user_roles' => $userRoles,
            'allowed_roles' => $allowedRoles,
        ]);

        // Verificar si tiene al menos uno de los roles permitidos
        foreach ($allowedRoles as $role) {
            if (in_array($role, $userRoles)) {
                \Log::info('[BODEGA-MIDDLEWARE] Acceso permitido - Rol encontrado: ' . $role);
                return $next($request);
            }
        }

        // Si no tiene ningún rol permitido, registrar y denegar
        \Log::warning('[BODEGA-MIDDLEWARE] Acceso denegado', [
            'usuario' => $user->email,
            'roles_usuario' => $userRoles,
            'roles_permitidos' => $allowedRoles,
        ]);

        abort(403, 'No tienes permisos para acceder a bodega');
    }
}
