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

        \Log::info('[CHECKROLE-DEBUG] Datos del usuario', [
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'ruta_actual' => $request->path(),
            'roles_ids' => $rolesIds,
            'roles_string_recibido' => $roles,
            'roles_requeridos' => $requiredRoles,
        ]);

        // Obtener nombres de roles del usuario desde la BD
        $userRoleNames = [];
        if (!empty($rolesIds)) {
            $userRoleNames = \App\Models\Role::whereIn('id', $rolesIds)
                ->pluck('name')
                ->toArray();
        }

        \Log::info('[CHECKROLE-NOMBRES] Roles encontrados', [
            'roles_ids' => $rolesIds,
            'roles_nombres' => $userRoleNames,
        ]);

        // Verificar si alguno de los roles requeridos coincide
        $hasAccess = false;
        foreach ($requiredRoles as $requiredRole) {
            // HERENCIA DE ROLES: supervisor_pedidos puede acceder a rutas que requieren asesor
            if ($requiredRole === 'asesor' && in_array('supervisor_pedidos', $userRoleNames)) {
                $hasAccess = true;
                \Log::info('[CHECKROLE-ACCESO] Rol herencia: supervisor_pedidos actúa como asesor');
                break;
            }
            
            if (in_array($requiredRole, $userRoleNames)) {
                $hasAccess = true;
                \Log::info('[CHECKROLE-ACCESO] Rol encontrado: ' . $requiredRole);
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

        \Log::info('[CHECKROLE-OK] Acceso permitido para ' . $user->email);
        return $next($request);
    }
}
