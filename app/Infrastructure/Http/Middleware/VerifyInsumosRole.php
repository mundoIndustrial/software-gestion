<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VerifyInsumosRole
{
    /**
     * Verifica que el usuario tenga rol insumos, admin, supervisor_planta o patronista
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            Log::warning('VerifyInsumosRole: Usuario no autenticado');
            abort(401, 'Usuario no autenticado');
        }

        $user = Auth::user();
        $rolesPermitidos = ['admin', 'supervisor_planta', 'patronista', 'insumos'];
        
        // Intentar usar hasAnyRole si está disponible
        if (method_exists($user, 'hasAnyRole')) {
            if (!$user->hasAnyRole($rolesPermitidos)) {
                Log::warning('VerifyInsumosRole: Acceso denegado', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles()->pluck('name')->toArray(),
                    'roles_permitidos' => $rolesPermitidos
                ]);
                abort(403, 'No autorizado para acceder a este módulo.');
            }
        } else {
            // Fallback manual
            $userRole = $user->role ?? null;
            if (is_object($userRole) && isset($userRole->name)) {
                $userRole = $userRole->name;
            }
            
            if (!$userRole || !in_array($userRole, $rolesPermitidos)) {
                Log::warning('VerifyInsumosRole: Acceso denegado', [
                    'user_id' => $user->id,
                    'user_role' => $userRole,
                    'roles_permitidos' => $rolesPermitidos
                ]);
                abort(403, 'No autorizado para acceder a este módulo.');
            }
        }
        
        return $next($request);
    }
}
