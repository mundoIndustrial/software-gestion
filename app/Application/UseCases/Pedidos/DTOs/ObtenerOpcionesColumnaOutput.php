<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerOpcionesColumnaUseCase
 * 
 * Responsabilidad: Encapsular opciones de columna con paginación
 * Patrón: Transfer Object
 */
class ObtenerOpcionesColumnaOutput
{
    public function __construct(
        public string $column,
        public int $total,
        public int $page,
        public int $limit,
        public int $last_page,
        public array $opciones,
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
            'last_page' => $this->last_page,
            'opciones' => $this->opciones,
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
