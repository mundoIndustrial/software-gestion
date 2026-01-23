<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * CambiarEstadoPedidoCommand
 * 
 * Command para cambiar el estado de un pedido
 * Estados vÃ¡lidos: activo, pendiente, cancelado, completado
 * 
 * @param int|string $pedidoId ID del pedido
 * @param string $nuevoEstado Nuevo estado
 * @param string $razon RazÃ³n del cambio de estado (opcional)
 */
class CambiarEstadoPedidoCommand implements Command
{
    private const ESTADOS_VALIDOS = ['activo', 'pendiente', 'cancelado', 'completado'];

    public function __construct(
        private int|string $pedidoId,
        private string $nuevoEstado,
        private ?string $razon = null,
    ) {
        if (!in_array(strtolower($this->nuevoEstado), self::ESTADOS_VALIDOS)) {
            throw new \InvalidArgumentException("Estado invÃ¡lido: {$nuevoEstado}");
        }
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getNuevoEstado(): string
    {
        return strtolower($this->nuevoEstado);
    }

    public function getRazon(): ?string
    {
        return $this->razon;
    }
}

