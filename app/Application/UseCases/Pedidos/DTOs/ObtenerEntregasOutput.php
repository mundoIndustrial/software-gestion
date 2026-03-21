<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerEntregasUseCase
 * 
 * Responsabilidad: Encapsular entregas de una orden
 * Patrón: Transfer Object
 */
class ObtenerEntregasOutput
{
    public function __construct(
        public int $numero_pedido,
        public array $entregas,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'entregas' => $this->entregas,
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
