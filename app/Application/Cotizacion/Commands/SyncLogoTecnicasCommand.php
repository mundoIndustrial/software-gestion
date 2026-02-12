<?php

namespace App\Application\Cotizacion\Commands;

use App\Application\Cotizacion\DTOs\SyncLogoTecnicasDTO;

/**
 * SyncLogoTecnicasCommand
 *
 * Caso de uso: sincronizar técnicas/logo del Paso 3 (crear/actualizar/borrar técnicas, fotos y prendas huérfanas).
 */
final readonly class SyncLogoTecnicasCommand
{
    public function __construct(
        public SyncLogoTecnicasDTO $datos,
    ) {
    }

    public static function crear(SyncLogoTecnicasDTO $datos): self
    {
        return new self($datos);
    }
}
