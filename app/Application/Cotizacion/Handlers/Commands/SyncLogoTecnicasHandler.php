<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\SyncLogoTecnicasCommand;
use App\Application\Services\Cotizacion\ProcesarLogoTecnicasCotizacionRequestService;

/**
 * SyncLogoTecnicasHandler
 *
 * Handler para sincronizar técnicas/logo del Paso 3.
 *
 * Nota: en la siguiente iteración este handler reemplazará la dependencia del service basado en Request,
 * una vez que dicho service reciba solamente DTOs.
 */
final class SyncLogoTecnicasHandler
{
    public function __construct(
        private readonly ProcesarLogoTecnicasCotizacionRequestService $service,
    ) {
    }

    public function handle(SyncLogoTecnicasCommand $command): void
    {
        // TODO (segunda pasada): migrar ProcesarLogoTecnicasCotizacionRequestService a método DTO-based.
        // Por ahora el handler existe para estandarizar el patrón Command/Handler del módulo.
        // Este handler aún no se usa desde el controller.
        unset($command);
    }
}
