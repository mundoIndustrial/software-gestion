<?php

namespace App\Modules\Cotizaciones\DTOs;

use Carbon\Carbon;

/**
 * DTO: CotizacionListDto
 * 
 * Objeto de transferencia de datos para listados de cotizaciones
 * Principio: Single Responsibility (SRP)
 */
class CotizacionListDto
{
    public function __construct(
        public int $id,
        public string $numero_cotizacion,
        public string $cliente,
        public string $tipo,
        public string $estado,
        public Carbon $created_at,
        public ?string $imageUrl = null,
    ) {}

    /**
     * Crear DTO desde modelo Cotizacion
     */
    public static function fromModel($cotizacion): self
    {
        return new self(
            id: $cotizacion->id,
            numero_cotizacion: $cotizacion->numero_cotizacion ?? 'Por asignar',
            cliente: $cotizacion->cliente ?? 'Sin cliente',
            tipo: $cotizacion->obtenerTipoCotizacion(),
            estado: $cotizacion->estado,
            created_at: $cotizacion->created_at,
            imageUrl: $cotizacion->logoCotizacion?->url_archivo ?? null,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_cotizacion' => $this->numero_cotizacion,
            'cliente' => $this->cliente,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'imageUrl' => $this->imageUrl,
        ];
    }
}
