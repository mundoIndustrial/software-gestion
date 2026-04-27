<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            Log::warning('[CHECKROLE] Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();

        $requiredRoles = [];
        foreach (($roles ?? []) as $r) {
            if ($r === null) continue;
            $r = (string) $r;
            foreach (explode(',', $r) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') $requiredRoles[] = $piece;
            }
        }

        // Obtener roles_ids del usuario
        $rolesIds = $user->roles_ids ?? [];
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }

        if (empty($rolesIds) && !empty($user->role_id)) {
            $rolesIds = [(int) $user->role_id];
        }


        // Obtener nombres de roles del usuario (usar cache de sesión)
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

        // Verificar si alguno de los roles requeridos coincide
        $hasAccess = false;
        foreach ($requiredRoles as $requiredRole) {
            // HERENCIA DE ROLES: supervisor_pedidos puede acceder a rutas que requieren asesor
            if ($requiredRole === 'asesor' && in_array('supervisor_pedidos', $userRoleNames)) {
                $hasAccess = true;
                break;
            }

            if (in_array($requiredRole, $userRoleNames)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            \Log::warning('[CHECKROLE-DENEGADO] Acceso rechazado', [
                'usuario' => $user->email,
                'ruta' => $request->path(),
                'roles_requeridos' => $requiredRoles,
                'roles_usuario' => $userRoleNames,
            ]);
            abort(403, 'Acceso denegado');
        }
        return $next($request);
    }
}
