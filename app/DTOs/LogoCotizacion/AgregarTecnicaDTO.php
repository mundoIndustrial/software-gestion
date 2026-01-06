<?php

namespace App\DTOs\LogoCotizacion;

/**
 * AgregarTecnicaDTO - Data Transfer Object para agregar técnica
 */
final class AgregarTecnicaDTO
{
    public function __construct(
        public readonly int $logoCotizacionId,
        public readonly int $tipoLogoCotizacionId,
        public readonly array $prendas,
        public readonly ?string $observacionesTecnica = null,
        public readonly ?string $instruccionesEspeciales = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            logoCotizacionId: (int) $data['logo_cotizacion_id'],
            tipoLogoCotizacionId: (int) $data['tipo_logo_cotizacion_id'],
            prendas: $data['prendas'] ?? [],
            observacionesTecnica: $data['observaciones_tecnica'] ?? null,
            instruccionesEspeciales: $data['instrucciones_especiales'] ?? null,
        );
    }
}
