<?php

namespace App\Application\Cotizacion\DTOs;

use DateTimeImmutable;

/**
 * CotizacionDTO - DTO para representar una cotización
 *
 * Datos de salida para consultas y operaciones
 */
final readonly class CotizacionDTO
{
    public function __construct(
        public int $id,
        public int $usuarioId,
        public ?string $numeroCotizacion,
        public string $tipo,
        public string $estado,
        public ?int $clienteId,
        public bool $esBorrador,
        public DateTimeImmutable $fechaInicio,
        public ?DateTimeImmutable $fechaEnvio,
        public ?string $cliente = null,
        public array $prendas = [],
        public ?array $logo = null,
        public ?int $tipoCotizacionId = null,
        public ?string $tipoVenta = null,
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        // Obtener el tipo de cotización
        $tipo = $datos['tipo'] ?? 'P'; // Por defecto a 'P'

        // Normalizar estado a mayúsculas para que coincida con el enum
        $estado = strtoupper($datos['estado'] ?? 'BORRADOR');

        return new self(
            id: (int) $datos['id'] ?? 0,
            usuarioId: (int) ($datos['usuario_id'] ?? $datos['asesor_id'] ?? 0),
            numeroCotizacion: $datos['numero_cotizacion'] ?? null,
            tipo: $tipo,
            estado: $estado,
            clienteId: isset($datos['cliente_id']) ? (int) $datos['cliente_id'] : null,
            esBorrador: (bool) $datos['es_borrador'] ?? true,
            fechaInicio: $datos['fecha_inicio'] instanceof DateTimeImmutable
                ? $datos['fecha_inicio']
                : new DateTimeImmutable($datos['fecha_inicio'] ?? 'now'),
            fechaEnvio: isset($datos['fecha_envio'])
                ? ($datos['fecha_envio'] instanceof DateTimeImmutable
                    ? $datos['fecha_envio']
                    : new DateTimeImmutable($datos['fecha_envio']))
                : null,
            cliente: self::extractClienteName($datos['cliente'] ?? $datos['nombre_cliente'] ?? null),
            prendas: $datos['prendas'] ?? [],
            logo: $datos['logo'] ?? null,
            tipoCotizacionId: isset($datos['tipo_cotizacion_id']) ? (int) $datos['tipo_cotizacion_id'] : null,
            tipoVenta: $datos['tipo_venta'] ?? null,
        );
    }

    /**
     * Extraer solo el nombre del cliente si viene como objeto/array
     */
    private static function extractClienteName($cliente): ?string
    {
        if ($cliente === null) {
            return null;
        }

        if (is_string($cliente)) {
            return $cliente;
        }

        if (is_array($cliente)) {
            return $cliente['nombre'] ?? null;
        }

        if (is_object($cliente)) {
            return $cliente->nombre ?? null;
        }

        return null;
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuarioId,
            'numero_cotizacion' => $this->numeroCotizacion,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'cliente_id' => $this->clienteId,
            'cliente' => $this->cliente,
            'es_borrador' => $this->esBorrador,
            'fecha_inicio' => $this->fechaInicio->format('Y-m-d H:i:s'),
            'fecha_envio' => $this->fechaEnvio?->format('Y-m-d H:i:s'),
            'prendas' => $this->prendas,
            'logo' => $this->logo,
            'tipo_cotizacion_id' => $this->tipoCotizacionId,
            'tipo_venta' => $this->tipoVenta,
        ];
    }
}
