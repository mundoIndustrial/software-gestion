<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * ProcesarImagenesCotizacionDTO
 *
 * DTO para el procesamiento de imágenes de prendas/telas en una cotización.
 * Diseñado para desacoplar la capa Application de Illuminate\Http\Request.
 */
final readonly class ProcesarImagenesCotizacionDTO
{
    public function __construct(
        public int $cotizacionId,
        public array $prendas,
        public array $prendaFotosArchivosPorIndex,
        public array $prendaFotosGuardadasPorIndex,
        public array $telasArchivosPorPrendaIndex,
        public array $telasFotosExistentesPorPrendaIndex,
    ) {
    }
}
