<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * CrearCotizacionDTO - DTO para crear una cotización
 *
 * Datos de entrada para el caso de uso de creación
 */
final readonly class CrearCotizacionDTO
{
    public function __construct(
        public int $usuarioId,
        public string $tipo,
        public string $cliente,
        public string $asesora,
        public array $productos = [],
        public array $logo = [],
        public string $tipoVenta = 'M',
        public bool $esBorrador = true,
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            usuarioId: (int) $datos['usuario_id'] ?? 0,
            tipo: $datos['tipo'] ?? 'P',
            cliente: $datos['cliente'] ?? '',
            asesora: $datos['asesora'] ?? '',
            productos: $datos['productos'] ?? [],
            logo: $datos['logo'] ?? [],
            tipoVenta: $datos['tipo_venta'] ?? 'M',
            esBorrador: $datos['es_borrador'] ?? true,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'usuario_id' => $this->usuarioId,
            'tipo' => $this->tipo,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'productos' => $this->productos,
            'logo' => $this->logo,
            'tipo_venta' => $this->tipoVenta,
            'es_borrador' => $this->esBorrador,
        ];
    }
}
