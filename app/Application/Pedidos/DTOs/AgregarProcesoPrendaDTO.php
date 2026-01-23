<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar proceso a una prenda
 * 
 * Maneja campos de pedidos_procesos_prenda_detalles:
 * - tipo_proceso_id: referencia a tipos_procesos
 * - ubicaciones: JSON con array de ubicaciones
 * - tallas_dama: JSON con array de tallas dama ['S', 'M', 'L', etc]
 * - tallas_caballero: JSON con array de tallas caballero ['S', 'M', 'L', etc]
 * - estado: estado del proceso
 * - aprobado_por: usuario que aprobó
 */
final class AgregarProcesoPrendaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly int $tipoProcesosId,
        public readonly ?array $ubicaciones = null,
        public readonly ?array $tallasDama = null,
        public readonly ?array $tallasCaballero = null,
        public readonly string $estado = 'PENDIENTE',
        public readonly ?int $aprobadoPor = null,
        public readonly ?string $observaciones = null,
        public readonly ?string $notasRechazo = null,
        public readonly ?array $datosAdicionales = null,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            tipoProcesosId: $data['tipo_proceso_id'] ?? throw new \InvalidArgumentException('tipo_proceso_id requerido'),
            ubicaciones: $data['ubicaciones'] ?? null,
            tallasDama: $data['tallas_dama'] ?? null,
            tallasCaballero: $data['tallas_caballero'] ?? null,
            estado: $data['estado'] ?? 'PENDIENTE',
            aprobadoPor: isset($data['aprobado_por']) ? (int) $data['aprobado_por'] : null,
            observaciones: $data['observaciones'] ?? null,
            notasRechazo: $data['notas_rechazo'] ?? null,
            datosAdicionales: $data['datos_adicionales'] ?? null,
        );
    }
}
