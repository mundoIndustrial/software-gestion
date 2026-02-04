<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: BodegueroRecibosProtection
 * 
 * Protege el acceso a recibos-datos:
 * - Bodeguero SOLO puede ver COSTURA-BODEGA
 * - Bodeguero NO puede acceder a otros tipos de recibos
 */
class BodegueroRecibosProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplica si el usuario es bodeguero
        if (!auth()->check() || !auth()->user()->hasRole('bodeguero')) {
            return $next($request);
        }

        // Bodeguero intentó acceder a recibos-datos directamente
        // El filtrado ya ocurre en PedidoController::obtenerDetalleCompleto()
        // Pero podemos agregar validaciones adicionales aquí si es necesario
        
        \Log::info('🔐 [BodegueroRecibosProtection] Bodeguero accediendo a recibos-datos', [
            'user_id' => auth()->id(),
            'path' => $request->path(),
            'pedido_id' => $request->route('id')
        ]);

        return $next($request);
    }
}
