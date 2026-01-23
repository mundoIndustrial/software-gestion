<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar proceso a una prenda
 * 
 * Maneja campos de pedidos_procesos_prenda_detalles:
 * - tipo_proceso_id: referencia a tipos_procesos (obligatorio)
 * - ubicaciones: JSON con array de ubicaciones ['pecho', 'espalda', etc]
 * - observaciones: notas sobre el proceso
 * - estado: estado del proceso (enum: PENDIENTE, EN_REVISION, APROBADO, etc)
 * - aprobado_por: ID del usuario que aprobÃ³
 * - notas_rechazo: razÃ³n de rechazo si aplica
 * - datos_adicionales: JSON con datos extra
 * - tallas: Array de tallas para poblar tallas_dama y tallas_caballero
 *   Estructura: [{ genero: 'DAMA'|'CABALLERO'|'UNISEX', talla: 'S'|'M'|'L'|'XL', cantidad: 5 }, ...]
 */
final class AgregarProcesoPrendaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly int $tipo_proceso_id,
        public readonly ?array $ubicaciones = null,
        public readonly ?string $observaciones = null,
        public readonly string $estado = 'PENDIENTE',
        public readonly ?int $aprobado_por = null,
        public readonly ?string $notas_rechazo = null,
        public readonly ?array $datos_adicionales = null,
        public readonly ?array $tallas = null,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            tipo_proceso_id: $data['tipo_proceso_id'] ?? throw new \InvalidArgumentException('tipo_proceso_id requerido'),
            ubicaciones: $data['ubicaciones'] ?? null,
            observaciones: $data['observaciones'] ?? null,
            estado: $data['estado'] ?? 'PENDIENTE',
            aprobado_por: isset($data['aprobado_por']) ? (int) $data['aprobado_por'] : null,
            notas_rechazo: $data['notas_rechazo'] ?? null,
            datos_adicionales: $data['datos_adicionales'] ?? null,
            tallas: $data['tallas'] ?? null,
        );
    }
}

