<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * ActualizarPedidoCommand
 * 
 * Command para actualizar datos de un pedido existente
 * 
 * @param int|string $pedidoId ID del pedido a actualizar
 * @param string $cliente Nuevo cliente (opcional)
 * @param string $formaPago Nueva forma de pago (opcional)
 */
class ActualizarPedidoCommand implements Command
{
    public function __construct(
        private int|string $pedidoId,
        private ?string $cliente = null,
        private ?string $formaPago = null,
    ) {}

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getCliente(): ?string
    {
        return $this->cliente;
    }

    public function getFormaPago(): ?string
    {
        return $this->formaPago;
    }

    public function tieneActualizaciones(): bool
    {
        return $this->cliente !== null || $this->formaPago !== null;
    }
}

