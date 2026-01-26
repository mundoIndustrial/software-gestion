<?php

namespace App\Application\Pedidos\DTOs;

use InvalidArgumentException;

/**
 * AnularProduccionPedidoDTO
 * 
 * DTO para anular un pedido de producción
 */
class AnularProduccionPedidoDTO
{
    public string $id;
    public string $razon;

    public function __construct(
        string $id,
        string $razon
    ) {
        $this->id = trim($id);
        $this->razon = trim($razon);

        $this->validar();
    }

    public static function fromRequest(string $id, array $datos): self
    {
        return new self(
            $id,
            $datos['razon'] ?? ''
        );
    }

    private function validar(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException("ID de pedido es requerido");
        }

        if (empty($this->razon)) {
            throw new InvalidArgumentException("Razón de anulación es requerida");
        }

        if (strlen($this->razon) > 500) {
            throw new InvalidArgumentException("Razón no puede exceder 500 caracteres");
        }
    }
}

