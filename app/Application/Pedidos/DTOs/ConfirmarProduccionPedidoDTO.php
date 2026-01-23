<?php

namespace App\Application\Pedidos\DTOs;

use InvalidArgumentException;

/**
 * ConfirmarProduccionPedidoDTO
 * 
 * DTO para confirmar un pedido de producción
 */
class ConfirmarProduccionPedidoDTO
{
    public string $id;
    public ?string $observaciones = null;

    public function __construct(
        string $id,
        ?string $observaciones = null
    ) {
        $this->id = trim($id);
        $this->observaciones = $observaciones ? trim($observaciones) : null;

        $this->validar();
    }

    public static function fromRequest(string $id, array $datos): self
    {
        return new self(
            $id,
            $datos['observaciones'] ?? null
        );
    }

    private function validar(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException("ID de pedido es requerido");
        }
    }
}
