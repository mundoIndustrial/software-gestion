<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictLavanderiaRole
{
    /**
     * Handle an incoming request.
     * Redirige a usuarios con rol 'lavanderia'/'gestor-lavanderia' que intenten acceder a rutas no permitidas
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Si el usuario tiene el rol 'lavanderia', solo permitir rutas de lavandería
        if ($user->hasRole(['lavanderia', 'gestor-lavanderia'])) {
            \Log::info('[RestrictLavanderiaRole] Usuario con rol lavanderia/gestor-lavanderia detectado', [
                'user_id' => $user->id,
                'requested_path' => $request->path(),
                'is_gestion_lavanderia' => $request->is('gestion-lavanderia*'),
            ]);
            
            // Permitir rutas de lavandería
            if ($request->is('gestion-lavanderia*')) {
                \Log::info('[RestrictLavanderiaRole] Permitiendo acceso a gestion-lavanderia');
                return $next($request);
            }

            // Permitir logout
            if ($request->is('logout')) {
                \Log::info('[RestrictLavanderiaRole] Permitiendo logout');
                return $next($request);
            }

            // Permitir profile
            if ($request->is('profile*')) {
                \Log::info('[RestrictLavanderiaRole] Permitiendo profile');
                return $next($request);
            }

            // Permitir refresh-csrf
            if ($request->is('refresh-csrf')) {
                \Log::info('[RestrictLavanderiaRole] Permitiendo refresh-csrf');
                return $next($request);
            }

            // Redirigir a lavandería (incluso si intenta ir a /dashboard)
            \Log::info('[RestrictLavanderiaRole] REDIRIGIENDO a gestion-lavanderia', [
                'user_id' => $user->id,
                'requested_path' => $request->path(),
            ]);
            return redirect()->route('gestion-lavanderia.index');
        }

        return $next($request);
    }
}
