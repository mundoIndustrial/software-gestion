<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatronistaReadOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo bloquear si el usuario es patronista
        if (auth()->check()) {
            $user = auth()->user();
            
            // Obtener el nombre del rol de forma segura
            $roleName = null;
            if ($user->role) {
                $roleName = is_object($user->role) ? $user->role->name : $user->role;
            }
            
            \Log::info('PatronistaReadOnly middleware', [
                'user_id' => $user->id,
                'roles_ids' => $user->roles_ids,
                'role' => $user->role,
                'roleName' => $roleName,
                'method' => $request->method(),
                'path' => $request->path(),
            ]);
            
            // Si el usuario es patronista, bloquear escritura
            if ($roleName === 'patronista') {
                // Bloquear POST, PATCH, PUT, DELETE (escritura)
                if ($request->isMethod(['POST', 'PATCH', 'PUT', 'DELETE'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para realizar esta acción. Tu rol es de solo lectura.',
                    ], 403);
                }
                
                // Permitir GET (lectura) y otras solicitudes
                return $next($request);
            }
        }

        // Para otros roles, permitir todo
        return $next($request);
    }
}
