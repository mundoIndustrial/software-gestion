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
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!$request->user()) {
            Log::warning('[CHECKROLE] Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();
        $requiredRoles = array_map('trim', explode(',', $roles));

        // Obtener roles_ids del usuario
        $rolesIds = $user->roles_ids ?? [];
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }

        \Log::info('[CHECKROLE-DEBUG] Datos del usuario', [
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'roles_ids' => $rolesIds,
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
