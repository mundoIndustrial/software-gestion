<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de BuscarOrdenesUseCase
 * 
 * Responsabilidad: Encapsular resultados de búsqueda
 * Patrón: Transfer Object
 */
class BuscarOrdenesOutput
{
    public function __construct(
        public int $total,
        public int $page,
        public int $per_page,
        public int $last_page,
        public array $data,
        public ?array $metadata = null,
    ) {}

    /**
     * Factory: Crear desde resultados paginados Eloquent
     */
    public static function fromPaginator($paginator, array $data): self
    {
        return new self(
            total: $paginator->total(),
            page: $paginator->currentPage(),
            per_page: $paginator->perPage(),
            last_page: $paginator->lastPage(),
            data: $data,
        );
    }

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
            'data' => $this->data,
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
