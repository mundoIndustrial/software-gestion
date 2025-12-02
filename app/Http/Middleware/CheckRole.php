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
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            Log::warning('CheckRole: Usuario no autenticado');
            return redirect()->route('login');
        }

        $user = $request->user();
        Log::info('CheckRole: Verificando rol', [
            'user_id' => $user->id,
            'required_role' => $role,
            'roles_ids' => $user->roles_ids,
        ]);

        // Permitir si el usuario tiene el rol requerido O es admin
        // Verificar directamente en roles_ids para admin
        $userRoles = \App\Models\Role::whereIn('id', $user->roles_ids ?? [])->pluck('name')->toArray();
        Log::info('CheckRole: Roles del usuario', ['roles' => $userRoles]);

        if (!in_array($role, $userRoles) && !in_array('admin', $userRoles)) {
            Log::warning('CheckRole: Acceso denegado', ['required_role' => $role, 'user_roles' => $userRoles]);
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        Log::info('CheckRole: Acceso permitido');
        return $next($request);
    }
}
