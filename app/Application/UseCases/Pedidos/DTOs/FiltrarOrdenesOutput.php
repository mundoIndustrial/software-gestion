<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de FiltrarOrdenesUseCase
 * 
 * Responsabilidad: Encapsular resultados de filtrado
 * Patrón: Transfer Object
 */
class FiltrarOrdenesOutput
{
    public function __construct(
        public int $total,
        public int $page,
        public int $per_page,
        public int $last_page,
        public array $ordenes,
        public ?array $filtros_aplicados = null,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->per_page,
            'last_page' => $this->last_page,
            'ordenes' => $this->ordenes,
            'filtros_aplicados' => $this->filtros_aplicados,
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
