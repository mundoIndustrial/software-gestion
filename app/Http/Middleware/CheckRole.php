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
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!$request->user()) {
            Log::warning('CheckRole: Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();
        
        // Soportar múltiples roles separados por comas
        $requiredRoles = array_map('trim', explode(',', $roles));
        
        // Obtener roles_ids - puede ser JSON o array
        $rolesIds = $user->roles_ids;
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }
        if (!is_array($rolesIds)) {
            $rolesIds = [];
        }
        
        Log::info('CheckRole: Verificando rol', [
            'user_id' => $user->id,
            'required_roles' => $requiredRoles,
            'roles_ids' => $rolesIds,
        ]);

        // Obtener nombres de los roles del usuario
        $userRoles = [];
        if (!empty($rolesIds)) {
            $userRoles = \App\Models\Role::whereIn('id', $rolesIds)->pluck('name')->toArray();
        }
        
        // También verificar el role_id principal del usuario (campo legacy)
        if ($user->role_id && !in_array($user->role_id, $rolesIds)) {
            $mainRole = \App\Models\Role::find($user->role_id);
            if ($mainRole && !in_array($mainRole->name, $userRoles)) {
                $userRoles[] = $mainRole->name;
            }
        }
        
        Log::info('CheckRole: Roles del usuario encontrados', ['roles' => $userRoles]);

        $hasRequiredRole = false;
        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles)) {
                $hasRequiredRole = true;
                break;
            }
        }
        
        if (!$hasRequiredRole && !in_array('admin', $userRoles)) {
            Log::warning('CheckRole: Acceso denegado', [
                'required_roles' => $requiredRoles, 
                'user_roles' => $userRoles,
                'user_role_id' => $user->role_id,
                'user_roles_ids' => $rolesIds
            ]);
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        Log::info('CheckRole: Acceso permitido');
        return $next($request);
    }
}
