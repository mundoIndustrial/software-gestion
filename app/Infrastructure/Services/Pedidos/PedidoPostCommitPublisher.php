<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Events\PedidoCreatedEvent;
use Illuminate\Support\Facades\Event;

/**
 * Centraliza side effects post-commit del flujo de pedidos.
 */
class PedidoPostCommitPublisher
{
    public function __construct(
        private PedidoNotificationService $pedidoNotificationService,
    ) {}

    public function publicarPedidoCreado(
        object $pedido,
        object $cliente,
        int $usuarioId,
        int $cantidadTotalPrendas,
        int $cantidadTotalEpps
    ): void {
        Event::dispatch(new PedidoCreatedEvent(
            pedidoId: $pedido->id,
            usuarioId: $usuarioId,
            estado: 'pendiente',
            metadata: [
                'numero_pedido' => $pedido->numero_pedido,
                'cantidad_prendas' => $cantidadTotalPrendas,
                'cantidad_epps' => $cantidadTotalEpps,
            ]
        ));

        $this->pedidoNotificationService->notificarPedidoCreado(
            $pedido,
            $cliente,
            $usuarioId,
            $cantidadTotalPrendas,
            $cantidadTotalEpps
        );
    }
}
