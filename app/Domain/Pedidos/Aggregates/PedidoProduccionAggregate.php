<?php

namespace App\Domain\Pedidos\Aggregates;

use App\Domain\Shared\DomainEvent;
use App\Domain\Pedidos\Events\PedidoProduccionCreado;

/**
 * PedidoProduccionAggregate
 * 
 * RaÃ­z de agregado para Pedido de ProducciÃ³n
 * Encapsula:
 * - Todos los datos del pedido
 * - Invariantes del negocio
 * - Eventos de dominio que genera
 * 
 * Un agregado es una agrupaciÃ³n de objetos de dominio relacionados
 * que tratamos como una unidad de cambio. PedidoProduccion es la raÃ­z.
 */
class PedidoProduccionAggregate
{
    /**
     * Identificador Ãºnico del agregado
     */
    private int|string $id;

    /**
     * NÃºmero Ãºnico del pedido
     */
    private string $numeroPedido;

    /**
     * Cliente que realiza el pedido
     */
    private string $cliente;

    /**
     * Forma de pago del pedido
     */
    private string $formaPago;

    /**
     * ID del asesor que crea el pedido
     */
    private int $asesorId;

    /**
     * Cantidad total de prendas
     */
    private int $cantidadTotal = 0;

    /**
     * Estado actual del pedido
     */
    private string $estado;

    /**
     * Eventos de dominio pendientes de publicar
     * 
     * @var array<DomainEvent>
     */
    private array $uncommittedEvents = [];

    private function __construct(
        int|string $id,
        string $numeroPedido,
        string $cliente,
        string $formaPago,
        int $asesorId,
        string $estado,
    ) {
        $this->id = $id;
        $this->numeroPedido = $numeroPedido;
        $this->cliente = $cliente;
        $this->formaPago = $formaPago;
        $this->asesorId = $asesorId;
        $this->estado = $estado;
    }

    /**
     * Factory method: Crear nuevo pedido de producciÃ³n
     * Esto emite el evento PedidoProduccionCreado
     */
    public static function crear(
        int|string $id,
        string $numeroPedido,
        string $cliente,
        string $formaPago,
        int $asesorId,
        string $estado,
    ): self {
        $agregado = new self(
            $id,
            $numeroPedido,
            $cliente,
            $formaPago,
            $asesorId,
            $estado,
        );

        // Registrar evento de creaciÃ³n
        $agregado->recordEvent(
            new PedidoProduccionCreado(
                $id,
                $numeroPedido,
                $cliente,
                $formaPago,
                $asesorId,
                cantidadTotal: 0,
                estado: $estado,
            )
        );

        return $agregado;
    }

    /**
     * Aumentar cantidad total del pedido
     * Invariante: cantidad_total no puede disminuir
     */
    public function agregarCantidad(int $cantidad): void
    {
        if ($cantidad < 0) {
            throw new \InvalidArgumentException('La cantidad a agregar no puede ser negativa');
        }

        $this->cantidadTotal += $cantidad;
    }

    /**
     * Cambiar estado del pedido
     * Invariante: estados vÃ¡lidos segÃºn negocio
     */
    public function cambiarEstado(string $nuevoEstado): void
    {
        $estadosValidos = ['PENDIENTE_SUPERVISOR', 'EN_PROCESO', 'COMPLETADO', 'CANCELADO'];

        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new \InvalidArgumentException("Estado '$nuevoEstado' no es vÃ¡lido");
        }

        if ($this->estado === 'CANCELADO') {
            throw new \DomainException('No se puede cambiar estado de un pedido cancelado');
        }

        $this->estado = $nuevoEstado;
    }

    /**
     * Registrar un evento en el agregado (pero no publicarlo aÃºn)
     * Los eventos se publican cuando el agregado es persistido
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
     * Marcar eventos como publicados (limpiar cola)
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

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getFormaPago(): string
    {
        return $this->formaPago;
    }

    public function getAsesorId(): int
    {
        return $this->asesorId;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }
}

