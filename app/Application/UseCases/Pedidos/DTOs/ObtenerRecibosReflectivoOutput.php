<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerRecibosReflectivoUseCase
 * 
 * Responsabilidad: Encapsular recibos de reflectivo con metadata y enriquecimiento
 * Patrón: Transfer Object
 */
class ObtenerRecibosReflectivoOutput
{
    public function __construct(
        public array $recibos,
        public int $total,
        public int $cantidad_total,
        public array $filtros_aplicados = [],
        public ?array $metadata = null,
    ) {}

    /**
     * Convertir a array para response JSON
     */
    public function toArray(): array
    {
        return [
            'recibos' => $this->recibos,
            'total' => $this->total,
            'cantidad_total' => $this->cantidad_total,
            'filtros_aplicados' => $this->filtros_aplicados,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convertir a response JSON
     */
    public function toJsonResponse(): array
    {
        return [
            'success' => true,
            'recibos' => $this->recibos,
            'total' => $this->total,
            'total_cantidad' => $this->cantidad_total,
            'filtros_aplicados' => $this->filtros_aplicados,
        ];
    }

    /**
     * Convertir a datos para view
     */
    public function toViewData(): array
    {
        return [
            'recibos' => $this->recibos,
            'totalCantidadGlobal' => $this->cantidad_total,
            'title' => 'Recibos de Reflectivo',
        ];
    }
}
