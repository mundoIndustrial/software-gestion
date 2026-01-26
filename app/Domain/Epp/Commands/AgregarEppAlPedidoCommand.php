<?php

namespace App\Domain\Epp\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * AgregarEppAlPedidoCommand
 * 
 * Command para agregar un EPP a un pedido
 */
class AgregarEppAlPedidoCommand implements Command
{
    /**
     * @param int $pedidoId ID del pedido
     * @param int $eppId ID del EPP
     * @param int $cantidad Cantidad de EPP
     * @param string|null $observaciones Observaciones adicionales
     * @param array $imagenes Array de archivos de imágenes (opcional)
     */
    public function __construct(
        private int $pedidoId,
        private int $eppId,
        private int $cantidad,
        private ?string $observaciones = null,
        private array $imagenes = [],
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getEppId(): int
    {
        return $this->eppId;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function getImagenes(): array
    {
        return $this->imagenes;
    }
}
