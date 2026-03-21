<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para salida de ObtenerRecibosCozturaUseCase
 * 
 * Responsabilidad: Encapsular recibos de costura con metadata y enriquecimiento
 * Patrón: Transfer Object
 */
class ObtenerRecibosCozturaOutput
{
    public function __construct(
        public array $recibos,
        public int $total,
        public int $cantidad_total,
        public array $filtros_aplicados = [],
        public ?string $html = null, // Para respuestas HTML
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
        $response = [
            'success' => true,
            'total' => $this->total,
            'total_cantidad' => $this->cantidad_total,
            'filtros_aplicados' => $this->filtros_aplicados,
        ];

        // Si hay HTML (para renderizar tabla)
        if ($this->html) {
            $response['recibos'] = [
                'html' => $this->html,
                'data' => $this->recibos,
            ];
        } else {
            $response['recibos'] = $this->recibos;
        }

        return $response;
    }

    /**
     * Convertir a datos para view
     */
    public function toViewData(): array
    {
        return [
            'recibos' => $this->recibos,
            'totalCantidadGlobal' => $this->cantidad_total,
            'title' => 'Recibos de Costura',
        ];
    }
}
