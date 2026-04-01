<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InsumosAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $url = $request->fullUrl();
        $method = $request->getMethod();
        $path = $request->getPathInfo();
        
        Log::info(' INSUMOS ACCESS MIDDLEWARE EJECUTADO', [
            'url' => $url,
            'method' => $method,
            'path' => $path,
            'is_json' => $request->expectsJson()
        ]);

        // Primero verificar autenticación
        if (!Auth::check()) {
            Log::warning(' InsumosAccess: Usuario NO AUTENTICADO', [
                'url' => $url,
                'redirect_to' => '/login'
            ]);
            return redirect('/login');
        }

        $user = Auth::user();
        Log::info(' InsumosAccess: Usuario AUTENTICADO', [
            'user_id' => $user->id,
            'user_name' => $user->name ?? 'N/A',
            'roles_ids' => json_encode($user->roles_ids ?? []),
        ]);

        // Permitir acceso si el usuario tiene rol en roles_ids
        if (!empty($user->roles_ids) && is_array($user->roles_ids)) {
            try {
                Log::info('InsumosAccess: Buscando roles de usuario', [
                    'roles_ids' => json_encode($user->roles_ids)
                ]);

                $userRoles = \App\Models\Role::whereIn('id', $user->roles_ids)
                    ->pluck('name')
                    ->toArray();
                
                Log::info('InsumosAccess: Roles encontrados', [
                    'roles' => json_encode($userRoles)
                ]);
                
                // Roles que pueden acceder a insumos
                $allowedRoles = ['admin', 'supervisor-admin', 'supervisor_planta', 'patronista', 'visualizador_plooter', 'insumos'];
                
                foreach ($allowedRoles as $role) {
                    if (in_array($role, $userRoles)) {
                        Log::info(' InsumosAccess: ACCESO PERMITIDO', [
                            'user_id' => $user->id,
                            'role' => $role,
                            'url' => $url
                        ]);
                        return $next($request);
                    }
                }
                
                Log::warning('InsumosAccess: Usuario tiene roles_ids pero NINGUNO permitido', [
                    'user_roles' => json_encode($userRoles),
                    'allowed_roles' => json_encode($allowedRoles)
                ]);
                
            } catch (\Exception $e) {
                Log::error('InsumosAccess: ERROR AL VERIFICAR ROLES', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::warning('InsumosAccess: Usuario sin roles_ids o no es array', [
                'roles_ids' => json_encode($user->roles_ids ?? null),
                'is_array' => is_array($user->roles_ids ?? null)
            ]);
        }

        // Si no tiene los roles requeridos, denegar acceso
        Log::error(' InsumosAccess: ACCESO DENEGADO - ROL INSUFICIENTE', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles_ids' => json_encode($user->roles_ids ?? []),
            'url' => $url,
            'ip' => $request->getClientIp()
        ]);
        
        // Devolver 403 Forbidden en lugar de redirect para evitar loops
        abort(403, 'No autorizado para acceder a este módulo');
    }
}
