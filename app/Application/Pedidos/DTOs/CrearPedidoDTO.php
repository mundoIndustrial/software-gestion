<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO: Crear Pedido
 * 
 * Desde HTTP/API hacia Application Layer
 */
class CrearPedidoDTO
{
    public function __construct(
        public int $clienteId,
        public string $descripcion,
        public array $prendas,
        public ?string $observaciones = null
    ) {
        $this->validar();
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            clienteId: (int) $data['cliente_id'],
            descripcion: (string) $data['descripcion'],
            prendas: (array) $data['prendas'],
            observaciones: $data['observaciones'] ?? null
        );
    }

    private function validar(): void
    {
        if ($this->clienteId <= 0) {
            throw new \InvalidArgumentException('Cliente ID invÃ¡lido');
        }

        if (empty($this->descripcion)) {
            throw new \InvalidArgumentException('DescripciÃ³n requerida');
        }

        if (empty($this->prendas)) {
            throw new \InvalidArgumentException('Debe haber al menos una prenda');
        }
    }
}

