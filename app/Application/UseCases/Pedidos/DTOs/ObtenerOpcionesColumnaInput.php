<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ObtenerOpcionesColumnaUseCase
 * 
 * Responsabilidad: Encapsular parámetros para obtener opciones columna
 * Patrón: Transfer Object
 */
class ObtenerOpcionesColumnaInput
{
    public function __construct(
        public string $column,
        public int $page = 1,
        public int $limit = 25,
        public string $search = '',
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request, string $column): self
    {
        return new self(
            column: $column,
            page: (int) $request->input('page', 1),
            limit: (int) $request->input('limit', 25),
            search: $request->input('search', ''),
        );
    }

    /**
     * Obtener columnas válidas
     */
    public static function columnasValidas(): array
    {
        return [
            'estado', 'area', 'cliente', 'forma_pago',
            'dia_entrega', 'encargado_orden', 'asesora',
        ];
    }

    /**
     * Validar columna
     */
    public function columnasValida(): bool
    {
        return in_array($this->column, self::columnasValidas());
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'page' => $this->page,
            'limit' => $this->limit,
            'search' => $this->search,
        ];
    }
}
