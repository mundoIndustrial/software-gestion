<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\Cotizacion;
use App\Domain\Shared\DomainEventDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated La tabla logo_pedidos ha sido eliminada de la base de datos (22/01/2026)
 * Este servicio se mantiene solo por compatibilidad y lanza excepciones.
 */
class LogoPedidoService
{
    public function __construct(
        private NumeracionService $numeracionService,
        private DomainEventDispatcher $eventDispatcher,
    ) {}

    /**
     * @deprecated La tabla logo_pedidos ha sido eliminada
     */
    public function crearDesdeCotizacion(Cotizacion $cotizacion): int
    {
        throw new \RuntimeException(
            'La funcionalidad de LogoPedido ha sido removida. '
            . 'La tabla logo_pedidos ya no existe en la base de datos. '
            . 'Por favor, contáctese con el administrador del sistema.'
        );
    }

    /**
     * @deprecated La tabla logo_pedidos ha sido eliminada
     */
    public function guardarDesdeRequest(array $data): int
    {
        throw new \RuntimeException(
            'La funcionalidad de LogoPedido ha sido removida. '
            . 'La tabla logo_pedidos ya no existe en la base de datos. '
            . 'Por favor, contáctese con el administrador del sistema.'
        );
    }

    /**
     * @deprecated La tabla logo_pedidos ha sido eliminada
     */
    public function guardarDatos(
        int $pedidoId,
        string $logoCotizacionId,
        int $cantidad,
        ?int $cotizacionId,
        array $datos = []
    ): array {
        throw new \RuntimeException(
            'La funcionalidad de LogoPedido ha sido removida. '
            . 'La tabla logo_pedidos ya no existe en la base de datos. '
            . 'Por favor, contáctese con el administrador del sistema.'
        );
    }
}
