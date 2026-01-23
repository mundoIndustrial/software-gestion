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
        
        // Verificar si el usuario tiene el rol Despacho
        // roles_ids puede ser string JSON o array directamente
        $rolesIds = is_array($user->roles_ids) 
            ? $user->roles_ids 
            : json_decode($user->roles_ids ?? '[]', true);
        
        // Obtener ID del rol Despacho
        $despachoRoleId = \App\Models\Role::where('name', 'Despacho')->first()?->id;
        
        if (!$despachoRoleId || !in_array($despachoRoleId, $rolesIds)) {
            return abort(403, 'No tienes permiso para acceder al módulo de despacho');
        }

        return $next($request);
    }
}
