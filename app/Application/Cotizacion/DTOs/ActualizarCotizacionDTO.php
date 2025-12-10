<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * ActualizarCotizacionDTO - DTO para actualizar una cotización
 *
 * Datos de entrada para el caso de uso de actualización
 */
final readonly class ActualizarCotizacionDTO
{
    public function __construct(
        public int $cotizacionId,
        public int $usuarioId,
        public string $cliente,
        public string $asesora,
        public array $productos = [],
        public array $logo = [],
        public string $tipoVenta = 'M',
        public string $tipoCotizacionCodigo = 'P',
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            cotizacionId: (int) $datos['cotizacion_id'] ?? 0,
            usuarioId: (int) $datos['usuario_id'] ?? 0,
            cliente: $datos['cliente'] ?? '',
            asesora: $datos['asesora'] ?? '',
            productos: $datos['productos'] ?? [],
            logo: $datos['logo'] ?? [],
            tipoVenta: $datos['tipo_venta'] ?? 'M',
            tipoCotizacionCodigo: $datos['tipo_cotizacion_codigo'] ?? 'P',
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'usuario_id' => $this->usuarioId,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'productos' => $this->productos,
            'logo' => $this->logo,
            'tipo_venta' => $this->tipoVenta,
            'tipo_cotizacion_codigo' => $this->tipoCotizacionCodigo,
        ];
    }
}
