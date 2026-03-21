<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerPrendasUseCase
 * 
 * Responsabilidad: Encapsular prendas de una orden
 * Patrón: Transfer Object
 */
class ObtenerPrendasOutput
{
    public function __construct(
        public int $numero_pedido,
        public array $prendas,
        public int $total_prendas,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'prendas' => $this->prendas,
            'total_prendas' => $this->total_prendas,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convertir a response JSON
     */
    public function toResponse(): array
    {
        return array_merge(
            $this->toArray(),
            ['success' => true]
        );
    }
}
