<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictGestorEpp
{
    /**
     * Handle an incoming request.
     * 
     * Este middleware bloquea todas las rutas excepto /epp para usuarios con rol gestor_epp
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Si el usuario NO tiene el rol gestor_epp, permitir el acceso
        if (!$user->hasRole('gestor_epp')) {
            return $next($request);
        }

        // El usuario tiene rol gestor_epp - solo puede acceder a rutas de EPP
        $path = $request->path();
        
        // Rutas permitidas para gestor_epp
        $allowedPaths = [
            'epp',
            'epp/test',
            'logout',
        ];

        // Permitir TODAS las rutas que empiecen con api/epp
        if (str_starts_with($path, 'api/epp')) {
            return $next($request);
        }

        // Verificar si la ruta actual está en las permitidas
        foreach ($allowedPaths as $allowed) {
            if ($path === $allowed || str_starts_with($path, $allowed . '/')) {
                return $next($request);
            }
        }

        // Ruta no permitida
        \Log::warning('[RESTRICT-GESTOR-EPP] Acceso denegado', [
            'usuario' => $user->email,
            'ruta_solicitada' => $path,
            'ruta_redirigida' => '/epp',
        ]);

        // Si es petición AJAX/API, devolver JSON error
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'No tienes permiso para acceder a esta sección.',
                'redirect' => route('epp.gestion')
            ], 403);
        }

        // Para peticiones web normales, redirigir
        return redirect()->route('epp.gestion')->with('error', 'No tienes permiso para acceder a esta sección.');
    }
}
