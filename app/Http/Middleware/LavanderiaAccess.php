<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class LavanderiaAccess
{
    /**
     * Handle an incoming request.
     * Verifica que el usuario tenga el rol 'gestor-lavanderia' o 'admin'
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            Log::warning('[LAVANDERIA-ACCESS] Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();

        // Obtener roles_ids del usuario
        $rolesIds = $user->roles_ids ?? [];
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }

        if (empty($rolesIds) && !empty($user->role_id)) {
            $rolesIds = [(int) $user->role_id];
        }

        // Obtener nombres de roles del usuario
        $cacheKey = "user_roles_{$user->id}";
        $userRoleNames = session($cacheKey);

        if (!$userRoleNames && !empty($rolesIds)) {
            $userRoleNames = \App\Models\Role::whereIn('id', $rolesIds)
                ->pluck('name')
                ->toArray();
            session([$cacheKey => $userRoleNames]);
        } elseif (empty($userRoleNames)) {
            $userRoleNames = [];
        }

        // Verificar si tiene el rol 'gestor-lavanderia' o 'admin'
        $allowedRoles = ['gestor-lavanderia', 'admin'];
        $hasAccess = false;

        foreach ($allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $userRoleNames)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            Log::warning('[LAVANDERIA-ACCESS-DENEGADO] Acceso rechazado', [
                'usuario' => $user->email,
                'ruta' => $request->path(),
                'roles_usuario' => $userRoleNames,
            ]);
            abort(403, 'Acceso denegado. Se requiere el rol gestor-lavanderia o admin.');
        }

        return $next($request);
    }
}
