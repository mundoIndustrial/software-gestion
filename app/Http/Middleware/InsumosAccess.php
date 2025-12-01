<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        if (Auth::check()) {
            $user = Auth::user();

            // Cargar la relación de rol si no está cargada
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }

            // Obtener el nombre del rol de forma segura
            $roleName = null;
            if ($user->role && is_object($user->role)) {
                $roleName = $user->role->name ?? null;
            } elseif (is_string($user->role)) {
                $roleName = $user->role;
            }

            // Log para debugging
            \Log::info('InsumosAccess - Usuario: ' . $user->email . ', Role ID: ' . ($user->role_id ?? 'NULL') . ', Rol: ' . ($roleName ?? 'NULL'));

            // Verificar si el usuario tiene rol de insumos o supervisor_planta
            if ($roleName === 'insumos' || $roleName === 'supervisor_planta') {
                return $next($request);
            }
        }

        return redirect('/')->with('error', 'No autorizado para acceder a este módulo.');
    }
}
