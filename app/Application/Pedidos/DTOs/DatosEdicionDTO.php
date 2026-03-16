<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DatosEdicionDTO
 * 
 * DTO para datos de pedido en modo edición
 */
class DatosEdicionDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $numero_pedido,
        public readonly string $cliente,
        public readonly array $prendas,
        public readonly array $epps_transformados = [],
        public readonly ?string $estado = null,
        public readonly ?string $area = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'prendas' => $this->prendas,
            'epps_transformados' => $this->epps_transformados,
            'estado' => $this->estado,
            'area' => $this->area,
        ];
    }
}
