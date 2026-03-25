<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Application\Pedidos\UseCases\ObtenerItemsEppDeCotizacionUseCase;

/**
 * ObtenerEppItemsController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: Manejar HTTP para obtener items EPP de una cotización
 * 
 * HTTP Methods:
 * - GET /asesores/api/cotizaciones/{id}/items-epp  → obtenerItems()
 * 
 * Dependencias:
 * - ObtenerItemsEppDeCotizacionUseCase: Lógica para obtener items
 * 
 * Características:
 * ✅ Solo 1 dependencia
 * ✅ Solo adaptador HTTP
 * ✅ Autorización en el controller (validar propiedad)
 */
class ObtenerEppItemsController extends Controller
{
    public function __construct(
        private ObtenerItemsEppDeCotizacionUseCase $obtenerItemsUseCase,
    ) {}

    /**
     * GET /asesores/api/cotizaciones/{cotizacion}/items-epp
     * 
     * Obtener items EPP de una cotización
     * 
     * @param Cotizacion $cotizacion (Route Model Binding)
     * @return JsonResponse
     */
    public function obtenerItems(Cotizacion $cotizacion): JsonResponse
    {
        try {
            Log::info('[ObtenerEppItemsController::obtenerItems] Iniciado', [
                'usuario_id' => Auth::id(),
                'cotizacion_id' => $cotizacion->id,
            ]);

            // 1. Verificar autorización (solo el asesor que creó la cotización)
            if ((int) $cotizacion->asesor_id !== (int) Auth::id()) {
                Log::warning('[ObtenerEppItemsController::obtenerItems] No autorizado', [
                    'usuario_id' => Auth::id(),
                    'cotizacion_asesor_id' => $cotizacion->asesor_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para acceder a esta cotización',
                ], 403);
            }

            // 2. Obtener items del UseCase
            $output = $this->obtenerItemsUseCase->ejecutar($cotizacion->id);

            // 3. Retornar respuesta
            Log::info('[ObtenerEppItemsController::obtenerItems] Completado', [
                'usuario_id' => Auth::id(),
                'cotizacion_id' => $cotizacion->id,
                'items_count' => count($output->items ?? []),
            ]);

            return response()->json([
                'success' => true,
                'cotizacion_id' => (int) $cotizacion->id,
                'items' => $output->items ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('[ObtenerEppItemsController::obtenerItems] Error', [
                'usuario_id' => Auth::id(),
                'cotizacion_id' => $cotizacion->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items EPP: ' . $e->getMessage(),
            ], 500);
        }
    }
}
