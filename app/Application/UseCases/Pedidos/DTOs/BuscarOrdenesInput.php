<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de BuscarOrdenesUseCase
 * 
 * Responsabilidad: Encapsular parámetros de búsqueda
 * Patrón: Transfer Object
 */
class BuscarOrdenesInput
{
    public function __construct(
        public string $search,
        public int $limit = 25,
        public int $page = 1,
        public bool $isTableSearch = false,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->input('search', ''),
            limit: (int) $request->input('limit', 25),
            page: (int) $request->input('page', 1),
            isTableSearch: $request->boolean('isTableSearch', false),
        );
    }

    /**
     * Validar entrada
     */
    public function validar(): array
    {
        $errores = [];

        if (strlen($this->search) < 1) {
            $errores[] = 'Búsqueda vacía';
        }

        if ($this->limit < 1 || $this->limit > 100) {
            $errores[] = 'Límite inválido (1-100)';
        }

        if ($this->page < 1) {
            $errores[] = 'Página inválida';
        }

        return $errores;
    }

    /**
     * Es válida la entrada
     */
    public function esValida(): bool
    {
        return count($this->validar()) === 0;
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'limit' => $this->limit,
            'page' => $this->page,
            'isTableSearch' => $this->isTableSearch,
        ];
    }
}
