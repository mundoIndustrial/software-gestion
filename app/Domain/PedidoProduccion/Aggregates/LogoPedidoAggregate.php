<?php

namespace App\Domain\PedidoProduccion\Aggregates;

use App\Domain\Shared\DomainEvent;
use App\Domain\PedidoProduccion\Events\LogoPedidoCreado;

/**
 * LogoPedidoAggregate
 * 
 * Raíz de agregado para Logo del Pedido
 * Encapsula:
 * - Todos los datos del logo
 * - Cantidades de logos
 * - Referencias a cotización
 * - Invariantes de negocio
 * 
 * Un logo es una entidad dentro del agregado del pedido.
 */
class LogoPedidoAggregate
{
    /**
     * Identificador único del logo
     */
    private int|string $id;

    /**
     * Referencia al pedido al que pertenece
     */
    private int|string $pedidoId;

    /**
     * Referencia a logo_cotización (si viene de cotización)
     */
    private ?int $logoCotizacionId;

    /**
     * Cantidad de logos
     */
    private int $cantidad;

    /**
     * Referencia a cotización (si aplica)
     */
    private ?int $cotizacionId;

    /**
     * Eventos de dominio pendientes
     * 
     * @var array<DomainEvent>
     */
    private array $uncommittedEvents = [];

    private function __construct(
        int|string $id,
        int|string $pedidoId,
        int $cantidad,
    ) {
        $this->id = $id;
        $this->pedidoId = $pedidoId;
        $this->cantidad = $cantidad;
    }

    /**
     * Factory method: Crear nuevo logo para un pedido
     */
    public static function crear(
        int|string $id,
        int|string $pedidoId,
        int $cantidad,
        ?int $logoCotizacionId = null,
        ?int $cotizacionId = null,
    ): self {
        // Validar invariantes
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad de logos debe ser mayor a 0');
        }

        $agregado = new self($id, $pedidoId, $cantidad);
        $agregado->logoCotizacionId = $logoCotizacionId;
        $agregado->cotizacionId = $cotizacionId;

        // Registrar evento de creación
        $agregado->recordEvent(
            new LogoPedidoCreado(
                $pedidoId,
                $id,
                $logoCotizacionId,
                $cantidad,
                $cotizacionId,
            )
        );

        return $agregado;
    }

    /**
     * Actualizar cantidad de logos
     */
    public function actualizarCantidad(int $nuevaCantidad): void
    {
        if ($nuevaCantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        $this->cantidad = $nuevaCantidad;
    }

    /**
     * Registrar evento en el agregado
     */
    public function recordEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }

    /**
     * Obtener eventos no publicados
     * 
     * @return array<DomainEvent>
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Marcar eventos como publicados
     */
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }

    // Getters
    public function getId(): int|string
    {
        return $this->id;
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getLogoCotizacionId(): ?int
    {
        return $this->logoCotizacionId;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getCotizacionId(): ?int
    {
        return $this->cotizacionId;
    }
}
