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
            Log::warning('[MIDDLEWARE-CHECKROLE] Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();
        
        // Soportar múltiples roles separados por comas
        $requiredRoles = array_map('trim', explode(',', $roles));
        
        // LOG CRÍTICO: ¿Qué roles recibió el middleware?
        Log::info('[MIDDLEWARE-CHECKROLE]  PARÁMETRO ROLES RECIBIDO', [
            'parametro_roles_string' => $roles,
            'roles_parseados' => $requiredRoles,
            'ruta' => $request->path(),
            'método_http' => $request->method(),
            'ruta_match' => $request->route() ? $request->route()->getName() : 'SIN NOMBRE',
            'ruta_uri_pattern' => $request->route() ? $request->route()->uri : 'SIN URI',
        ]);
        
        // Obtener roles_ids - puede ser JSON o array
        $rolesIds = $user->roles_ids;
        if (is_string($rolesIds)) {
            $rolesIds = json_decode($rolesIds, true) ?? [];
        }
        if (!is_array($rolesIds)) {
            $rolesIds = [];
        }
        
        // 📋 LOG: INFORMACIÓN DE LA SOLICITUD
        Log::info('[MIDDLEWARE-CHECKROLE] ===== VERIFICACIÓN DE AUTORIZACIÓN INICIADA =====', [
            'usuario_id' => $user->id,
            'usuario_nombre' => $user->name,
            'usuario_email' => $user->email,
            'ruta' => $request->path(),
            'método' => $request->method(),
            'roles_requeridos' => $requiredRoles,
        ]);
        
        Log::info('[MIDDLEWARE-CHECKROLE] Roles configurados del usuario en BD', [
            'usuario_id' => $user->id,
            'roles_ids_array' => $rolesIds,
            'role_id_principal' => $user->role_id,
        ]);

        // Obtener nombres de los roles del usuario
        $userRoles = [];
        if (!empty($rolesIds)) {
            $userRoles = \App\Models\Role::whereIn('id', $rolesIds)->pluck('name')->toArray();
        }
        
        Log::debug('[MIDDLEWARE-CHECKROLE] Roles obtenidos desde tabla roles (roles_ids)', [
            'roles_nombres' => $userRoles,
            'cantidad' => count($userRoles)
        ]);
        
        // También verificar el role_id principal del usuario (campo legacy)
        if ($user->role_id && !in_array($user->role_id, $rolesIds)) {
            $mainRole = \App\Models\Role::find($user->role_id);
            if ($mainRole && !in_array($mainRole->name, $userRoles)) {
                $userRoles[] = $mainRole->name;
                Log::info('[MIDDLEWARE-CHECKROLE] Rol principal agregado (legacy)', [
                    'role_id' => $user->role_id,
                    'role_nombre' => $mainRole->name
                ]);
            }
        }
        
        Log::info('[MIDDLEWARE-CHECKROLE] Roles finales del usuario', [
            'usuario_id' => $user->id,
            'roles_finales' => $userRoles
        ]);

        // � EXTENSIÓN: RESOLVER JERARQUÍA DE ROLES (herencia de permisos)
        $rolesConHerencia = \App\Services\RoleHierarchyService::getEffectiveRoles($userRoles);
        
        if (count($rolesConHerencia) > count($userRoles)) {
            $rolesHeredados = array_diff($rolesConHerencia, $userRoles);
            Log::info('[MIDDLEWARE-CHECKROLE]  JERARQUÍA DE ROLES APLICADA', [
                'usuario_id' => $user->id,
                'roles_originales' => $userRoles,
                'roles_heredados' => array_values($rolesHeredados),
                'roles_efectivos_totales' => $rolesConHerencia,
                'jerarquía_detectada' => array_map(function($role) {
                    return \App\Services\RoleHierarchyService::getHierarchyChain($role);
                }, $userRoles)
            ]);
            $userRoles = $rolesConHerencia; // Usar los roles con herencia para la verificación
        } else {
            Log::debug('[MIDDLEWARE-CHECKROLE]  Sin jerarquía aplicable para estos roles', [
                'usuario_id' => $user->id,
                'roles_del_usuario' => $userRoles
            ]);
        }

        // �🔐 VERIFICAR SI USUARIO TIENE UN ROL REQUERIDO
        $hasRequiredRole = false;
        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles)) {
                $hasRequiredRole = true;
                Log::debug('[MIDDLEWARE-CHECKROLE] Rol encontrado', [
                    'rol_buscado' => $role,
                    'usuario_tiene_rol' => true
                ]);
                break;
            }
        }
        
        // Los admins siempre tienen acceso
        $esAdmin = in_array('admin', $userRoles);
        if ($esAdmin) {
            Log::info('[MIDDLEWARE-CHECKROLE] Usuario es ADMIN - acceso automático');
            $hasRequiredRole = true;
        }
        
        if (!$hasRequiredRole) {
            Log::warning('❌ [MIDDLEWARE-CHECKROLE] ACCESO DENEGADO - No tiene permisos', [
                'usuario_id' => $user->id,
                'usuario_nombre' => $user->name,
                'usuario_email' => $user->email,
                'ruta_accedida' => $request->path(),
                'roles_requeridos' => $requiredRoles, 
                'roles_usuario' => $userRoles,
                'role_id_legacy' => $user->role_id,
                'roles_ids' => $rolesIds,
                'es_admin' => $esAdmin,
                'razón' => 'El usuario no tiene ninguno de los roles requeridos'
            ]);
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        Log::info(' [MIDDLEWARE-CHECKROLE] ACCESO PERMITIDO - Autorización exitosa');
        return $next($request);
    }
}
