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
        if (!Auth::check()) {
            Log::warning('InsumosAccess: Usuario no autenticado');
            return redirect('/login');
        }

        $user = Auth::user();
        Log::info('InsumosAccess: Usuario', [
            'id' => $user->id,
            'name' => $user->name,
            'roles_ids' => $user->roles_ids,
        ]);

        // Admin, supervisor-admin, supervisor_planta y patronista pueden ver insumos
        // Verificar directamente en roles_ids array
        if (!empty($user->roles_ids) && is_array($user->roles_ids)) {
            // Obtener los roles del usuario
            $userRoles = \App\Models\Role::whereIn('id', $user->roles_ids)->pluck('name')->toArray();
            Log::info('InsumosAccess: Roles del usuario', ['roles' => $userRoles]);

            if (in_array('admin', $userRoles) || in_array('supervisor-admin', $userRoles) || in_array('supervisor_planta', $userRoles) || in_array('patronista', $userRoles)) {
                Log::info('InsumosAccess: Acceso permitido');
                return $next($request);
            }
        }

        // Verificar si el usuario tiene rol de insumos o patronista
        if ($user->hasRole('insumos') || $user->hasRole('patronista')) {
            Log::info('InsumosAccess: Rol insumos o patronista permitido');
            return $next($request);
        }

        Log::warning('InsumosAccess: Acceso denegado', ['user_id' => $user->id]);
        return redirect('/')->with('error', 'No autorizado para acceder a este módulo.');
    }
}
