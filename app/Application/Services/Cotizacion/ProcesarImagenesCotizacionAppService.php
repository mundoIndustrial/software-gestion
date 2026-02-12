<?php

namespace App\Application\Services\Cotizacion;

use Illuminate\Http\Request;
use App\Infrastructure\Http\Mappers\ProcesarImagenesCotizacionRequestMapper;

final class ProcesarImagenesCotizacionAppService
{
    public function __construct(
        private readonly ProcesarImagenesCotizacionRequestService $procesarImagenesCotizacionRequestService,
        private readonly ProcesarImagenesCotizacionRequestMapper $procesarImagenesCotizacionRequestMapper,
    ) {
    }

    public function ejecutar(Request $request, int $cotizacionId): void
    {
        try {
            $dto = $this->procesarImagenesCotizacionRequestMapper->map($request, $cotizacionId);
            $this->procesarImagenesCotizacionRequestService->ejecutar($dto);
        } catch (\Exception $e) {
            // Mantener el comportamiento actual (no lanzar), para no romper update/store.
            \Illuminate\Support\Facades\Log::error('ProcesarImagenesCotizacionAppService: Error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
