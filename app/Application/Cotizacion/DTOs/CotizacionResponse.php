<?php

namespace App\Application\Cotizacion\DTOs;

class CotizacionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $numero_cotizacion,
        public readonly int $tipo_cotizacion_id,
        public readonly string $estado,
        public readonly bool $es_borrador,
        public readonly ?int $cliente_id,
        public readonly array $data,
    ) {
    }

    public static function fromModel($cotizacion): self
    {
        return new self(
            id: $cotizacion->id,
            numero_cotizacion: $cotizacion->numero_cotizacion,
            tipo_cotizacion_id: $cotizacion->tipo_cotizacion_id,
            estado: $cotizacion->estado,
            es_borrador: $cotizacion->es_borrador,
            cliente_id: $cotizacion->cliente_id,
            data: $cotizacion->toArray(),
        );
    }
}
