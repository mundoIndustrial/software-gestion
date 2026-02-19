<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDespachoRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Debug: Mostrar información del usuario
        \Log::info('[CheckDespachoRole] Verificando acceso', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'roles_ids_raw' => $user->roles_ids,
            'roles_ids_array' => is_array($user->roles_ids) ? $user->roles_ids : json_decode($user->roles_ids ?? '[]', true),
        ]);
        
        // Verificar si el usuario tiene el rol Despacho
        // roles_ids puede ser string JSON o array directamente
        $rolesIds = is_array($user->roles_ids) 
            ? $user->roles_ids 
            : json_decode($user->roles_ids ?? '[]', true);
        
        // Obtener ID del rol Despacho
        $despachoRoleId = \App\Models\Role::where('name', 'Despacho')->first()?->id;
        
        \Log::info('[CheckDespachoRole] Verificación de rol', [
            'despacho_role_id' => $despachoRoleId,
            'user_has_despacho_role' => $despachoRoleId && in_array($despachoRoleId, $rolesIds),
        ]);
        
        if (!$despachoRoleId || !in_array($despachoRoleId, $rolesIds)) {
            \Log::warning('[CheckDespachoRole] Acceso denegado', [
                'user_id' => $user->id,
                'despacho_role_id' => $despachoRoleId,
                'user_roles' => $rolesIds,
            ]);
            return abort(403, 'No tienes permiso para acceder al módulo de despacho');
        }

        \Log::info('[CheckDespachoRole] Acceso permitido', [
            'user_id' => $user->id,
        ]);

        return $next($request);
    }
}
