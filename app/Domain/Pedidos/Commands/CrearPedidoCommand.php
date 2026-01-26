<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * CrearPedidoCommand
 * 
 * Command para crear un nuevo pedido de producción
 * 
 * ParÃ¡metros:
 * - numero_pedido: NÃºmero Ãºnico del pedido
 * - cliente: Nombre o ID del cliente
 * - forma_pago: Forma de pago (contado, crÃ©dito, etc)
 * - asesor_id: ID del asesor asignado
 */
class CrearPedidoCommand implements Command
{
    public function __construct(
        private string $numeroPedido,
        private int|string $cliente,
        private string $formaPago,
        private int $asesorId,
        private ?int $cantidadInicial = 0,
    ) {}

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
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

    public function getCantidadInicial(): int
    {
        return $this->cantidadInicial ?? 0;
    }
}

