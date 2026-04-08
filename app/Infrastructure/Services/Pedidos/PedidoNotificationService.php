<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\News;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Maneja notificaciones derivadas del flujo de pedidos.
 */
class PedidoNotificationService
{
    public function notificarPedidoCreado(
        object $pedido,
        object $cliente,
        int $usuarioId,
        int $cantidadPrendas,
        int $cantidadEpps
    ): void {
        try {
            $user = Auth::user();
            $nombreAsesor = $user->name ?? 'Sistema';

            News::create([
                'event_type' => 'pedido_creado',
                'table_name' => 'pedidos_produccion',
                'record_id' => $pedido->id,
                'description' => "Asesor {$nombreAsesor} creo el Pedido #{$pedido->numero_pedido} - Cliente: {$cliente->nombre}",
                'user_id' => $usuarioId,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'pedido_creado',
                    'pedido_id' => $pedido->id,
                    'cliente' => $cliente->nombre,
                    'prendas' => $cantidadPrendas,
                    'epps' => $cantidadEpps,
                ],
            ]);

            Log::info('[PedidoNotificationService] Notificacion creada', [
                'pedido_id' => $pedido->id,
                'usuario_id' => $usuarioId,
            ]);
        } catch (\Exception $e) {
            Log::warning('[PedidoNotificationService] Error creando News', [
                'pedido_id' => $pedido->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
