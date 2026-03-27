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
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        public int $id,
        public int $usuarioId,
        public ?string $numeroCotizacion,
        public string $tipo,
        public string $estado,
        public ?int $clienteId,
        public bool $esBorrador,
        public DateTimeImmutable $createdAt,
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
            createdAt: $datos['created_at'] instanceof DateTimeImmutable
                ? $datos['created_at']
                : new DateTimeImmutable($datos['created_at'] ?? $datos['fecha_inicio'] ?? 'now'),
            fechaInicio: $datos['fecha_inicio'] instanceof DateTimeImmutable
                ? $datos['fecha_inicio']
                : new DateTimeImmutable($datos['fecha_inicio'] ?? 'now'),
            fechaEnvio: self::convertirADateTimeImmutable($datos['fecha_envio'] ?? null),
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
        $nombre = null;

        if ($cliente === null) {
            $nombre = null;
        } elseif (is_string($cliente)) {
            $nombre = $cliente;
        } elseif (is_array($cliente)) {
            $nombre = $cliente['nombre'] ?? null;
        } elseif (is_object($cliente)) {
            $nombre = $cliente->nombre ?? null;
        }

        return $nombre;
    }

    /**
     * Convertir a DateTimeImmutable si no lo es ya
     */
    private static function convertirADateTimeImmutable($fecha): ?DateTimeImmutable
    {
        if ($fecha === null) {
            return null;
        }

        if ($fecha instanceof DateTimeImmutable) {
            return $fecha;
        }

        return new DateTimeImmutable($fecha);
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
            'created_at' => $this->createdAt->format(self::DATE_FORMAT),
            'fecha_inicio' => $this->fechaInicio->format(self::DATE_FORMAT),
            'fecha_envio' => $this->fechaEnvio?->format(self::DATE_FORMAT),
            'prendas' => $this->prendas,
            'logo' => $this->logo,
            'tipo_cotizacion_id' => $this->tipoCotizacionId,
            'tipo_venta' => $this->tipoVenta,
        ];
    }
}
