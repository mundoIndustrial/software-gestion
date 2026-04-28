<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictVisualizadorRecibosLogo
{
    /**
     * Permite al rol visualizador_recibos_logo acceder solo a la vista
     * de recibos bordado/estampado y a sus endpoints de lectura necesarios.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->hasRole('visualizador_recibos_logo')) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');

        $allowedPatterns = [
            'recibos-bordado-estampado',
            'logout',
            'recibos-costura',
            'recibos-costura/*',
            'api/recibos-costura',
            'api/recibos-costura/*',
            'pedidos-public/*/recibos-datos',
            'recibos-novedades/*/*',
            'recibos-novedades/*/*/consolidado',
            'api/pedidos/*/prendas',
            'registros/*/recibos-datos',
            'registros/*/seguimiento-prenda',
            'api/ordenes/*/procesos',
            'api/tabla-original/*/procesos',
            'api/tabla-original-bodega/*/procesos',
            'seguimiento-proceso/*',
            'storage/*',
            'storage-serve/*',
        ];

        $isAllowed = false;
        foreach ($allowedPatterns as $pattern) {
            if (Str::is($pattern, $path)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            \Log::warning('[RESTRICT-VISUALIZADOR-RECIBOS-LOGO] Acceso denegado', [
                'usuario' => auth()->user()->email,
                'ruta_solicitada' => $path,
                'metodo' => $request->method(),
                'ruta_redirigida' => 'recibos-bordado-estampado',
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'No tienes permiso para acceder a esta sección.',
                    'redirect' => route('registros.recibos-bordado-estampado'),
                ], 403);
            }

            return redirect()
                ->route('registros.recibos-bordado-estampado')
                ->with('error', 'No tienes permiso para acceder a esta sección.');
        }

        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS']) && !$request->is('logout')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Tu rol solo tiene permisos de visualización.',
                    'redirect' => route('registros.recibos-bordado-estampado'),
                ], 403);
            }

            return redirect()
                ->route('registros.recibos-bordado-estampado')
                ->with('error', 'Tu rol solo tiene permisos de visualización.');
        }

        return $next($request);
    }
}
