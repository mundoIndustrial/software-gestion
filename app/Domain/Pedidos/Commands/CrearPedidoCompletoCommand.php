<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * CrearPedidoCompletoCommand
 * 
 * Command para crear un pedido completo con todos sus items (prendas)
 * Este es el command principal que orquesta la creación de:
 * - Pedido de producción
 * - Prendas con sus agregados (tallas, variantes, procesos, imágenes)
 * 
 * Diferencia con CrearPedidoCommand:
 * - CrearPedidoCommand: Solo crea la entidad raíz (pedido)
 * - CrearPedidoCompletoCommand: Crea pedido + prendas + agregados
 * 
 * Parámetros:
 * - cliente: ID o nombre del cliente
 * - formaPago: Forma de pago (contado, crédito, etc)
 * - asesorId: ID del asesor asignado
 * - items: Array de prendas con todos sus datos (tallas, procesos, imágenes, etc)
 * - descripcion: Descripción general del pedido (opcional)
 */
class CrearPedidoCompletoCommand implements Command
{
    public function __construct(
        private int|string $cliente,
        private string $formaPago,
        private int $asesorId,
        private array $items,
        private ?string $descripcion = null,
    ) {
        // Validar que hay al menos un item
        if (empty($this->items)) {
            throw new \InvalidArgumentException('El pedido debe tener al menos una prenda');
        }
    }

    public function getCliente(): int|string
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

    public function getItems(): array
    {
        return $this->items;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
}
