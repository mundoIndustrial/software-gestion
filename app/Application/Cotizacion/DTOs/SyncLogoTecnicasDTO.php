<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * SyncLogoTecnicasDTO
 *
 * DTO de entrada para sincronizar técnicas/logo del Paso 3 (cotización combinada).
 *
 * Nota: este DTO está diseñado para desacoplar la capa Application de Illuminate\Http\Request.
 */
final readonly class SyncLogoTecnicasDTO
{
    public function __construct(
        public int $cotizacionId,
        public ?string $tipoCotizacion,
        public ?string $tipoVenta,
        public array $observacionesGenerales,
        public array $tecnicasAgregadas,
        public bool $tecnicasAgregadasPresent,
        public array $logoArchivos,
        public array $imagenesPaso3Archivos,
        public array $logoFotosGuardadas,
        public array $logoFotosExistentes,
    ) {
    }
}
