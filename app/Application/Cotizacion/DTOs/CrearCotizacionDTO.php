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
        public ?int $clienteId = null,
        public array $prendas = [],
        public array $logo = [],
        public ?string $tipoVenta = 'M',
        public array $especificaciones = [],
        public bool $esBorrador = true,
        public string $estado = 'BORRADOR',
        public ?int $numeroCotizacion = null,
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        // Convertir especificaciones si viene como JSON string
        $especificaciones = $datos['especificaciones'] ?? [];
        if (is_string($especificaciones)) {
            $especificaciones = json_decode($especificaciones, true) ?? [];
        }

        $numeroCotizacion = null;
        if (array_key_exists('numero_cotizacion', $datos)) {
            $raw = $datos['numero_cotizacion'];
            if (is_int($raw)) {
                $numeroCotizacion = $raw;
            } elseif (is_string($raw)) {
                // Soportar formato: "COT-00005" u otros que contengan dígitos
                if (preg_match('/\d+/', $raw, $matches)) {
                    $numeroCotizacion = (int) $matches[0];
                } else {
                    $numeroCotizacion = (int) $raw;
                }
            } else {
                $numeroCotizacion = (int) $raw;
            }
        }

        return new self(
            usuarioId: (int) ($datos['usuario_id'] ?? 0),
            tipo: $datos['tipo'] ?? 'P',
            clienteId: isset($datos['cliente_id']) ? (int) $datos['cliente_id'] : null,
            prendas: $datos['prendas'] ?? [],
            logo: $datos['logo'] ?? [],
            tipoVenta: $datos['tipo_venta'] ?? 'M',
            especificaciones: $especificaciones,
            esBorrador: (bool) ($datos['es_borrador'] ?? true),
            estado: $datos['estado'] ?? 'BORRADOR',
            numeroCotizacion: $numeroCotizacion,
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
            'cliente_id' => $this->clienteId,
            'prendas' => $this->prendas,
            'logo' => $this->logo,
            'tipo_venta' => $this->tipoVenta,
            'especificaciones' => $this->especificaciones,
            'es_borrador' => $this->esBorrador,
            'estado' => $this->estado,
            'numero_cotizacion' => $this->numeroCotizacion,
        ];
    }
}
