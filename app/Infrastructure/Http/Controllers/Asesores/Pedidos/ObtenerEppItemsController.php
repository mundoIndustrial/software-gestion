<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerItemsEppDeCotizacionUseCase;
use App\Application\Services\Asesores\ObtenerCotizacionAsesorService;

class ObtenerEppItemsController extends Controller
{
    public function __construct(
        private ObtenerItemsEppDeCotizacionUseCase $obtenerItemsUseCase,
        private ObtenerCotizacionAsesorService $obtenerCotizacionAsesorService,
    ) {
    }

    public function obtenerItems(int|string $cotizacionId): JsonResponse
    {
        try {
            $asesorId = (int) Auth::id();
            Log::info('[ObtenerEppItemsController::obtenerItems] Iniciado', [
                'usuario_id' => $asesorId,
                'cotizacion_id' => $cotizacionId,
            ]);

            $cotizacionIdAutorizada = $this->obtenerCotizacionAsesorService
                ->obtenerIdSiPerteneceAAsesor((int) $cotizacionId, $asesorId);

            if ($cotizacionIdAutorizada === null) {
                Log::warning('[ObtenerEppItemsController::obtenerItems] No autorizado', [
                    'usuario_id' => $asesorId,
                    'cotizacion_id' => $cotizacionId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para acceder a esta cotizacion',
                ], 403);
            }

            $output = $this->obtenerItemsUseCase->ejecutar($cotizacionIdAutorizada);

            Log::info('[ObtenerEppItemsController::obtenerItems] Completado', [
                'usuario_id' => $asesorId,
                'cotizacion_id' => $cotizacionIdAutorizada,
                'items_count' => count($output->items ?? []),
            ]);

            return response()->json([
                'success' => true,
                'cotizacion_id' => $cotizacionIdAutorizada,
                'items' => $output->items ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('[ObtenerEppItemsController::obtenerItems] Error', [
                'usuario_id' => Auth::id(),
                'cotizacion_id' => $cotizacionId ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items EPP.',
            ], 500);
        }
    }
}
