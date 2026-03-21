<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de FiltrarOrdenesUseCase
 * 
 * Responsabilidad: Encapsular parámetros de filtrado
 * Patrón: Transfer Object
 */
class FiltrarOrdenesInput
{
    public function __construct(
        public array $filters = [],
        public int $page = 1,
        public int $per_page = 25,
        public ?array $metadata = null,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            filters: $request->input('filters', []),
            page: (int) $request->input('page', 1),
            per_page: (int) $request->input('per_page', 25),
            metadata: $request->input('metadata'),
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'page' => $this->page,
            'per_page' => $this->per_page,
            'metadata' => $this->metadata,
        ];
    }
}
