<?php

namespace App\Application\Pedidos\DTOs;

use InvalidArgumentException;

/**
 * ActualizarProduccionPedidoDTO
 * 
 * DTO para actualizar un pedido de producciÃ³n existente
 */
class ActualizarProduccionPedidoDTO
{
    public string $id;
    public ?string $cliente = null;
    public array $prendas = [];

    public function __construct(
        string $id,
        ?string $cliente = null,
        array $prendas = []
    ) {
        $this->id = trim($id);
        $this->cliente = $cliente ? trim($cliente) : null;
        $this->prendas = $prendas;

        $this->validar();
    }

    public static function fromRequest(string $id, array $datos): self
    {
        return new self(
            $id,
            $datos['cliente'] ?? null,
            $datos['prendas'] ?? []
        );
    }

    private function validar(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException("ID de pedido es requerido");
        }
    }
}

