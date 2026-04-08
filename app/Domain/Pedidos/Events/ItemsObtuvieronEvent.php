<?php

namespace App\Domain\Pedidos\Events;

/**
 * ItemsObtuvieronEvent
 * 
 * Evento de dominio: Items fueron obtenidos de una cotización
 * Se dispara cuando ObtenerItemsEppCotizacionService obtiene items
 */
class ItemsObtuvieronEvent
{
    public function __construct(
        public int $cotizacionId,
        public int $itemsCount,
        public int $eppsCount,
        public array $metadata = [],
    ) {}

    public function toPayload(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'items_count' => $this->itemsCount,
            'epps_count' => $this->eppsCount,
            'timestamp' => now(),
            'metadata' => $this->metadata,
        ];
    }
}
