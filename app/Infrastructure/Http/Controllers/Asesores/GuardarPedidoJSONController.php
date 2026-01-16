<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Domain\PedidoProduccion\Services\GuardarPedidoDesdeJSONService;
use App\Domain\PedidoProduccion\Validators\PedidoJSONValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Controlador: Guardar Pedido desde JSON
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Recibir JSON del frontend
 * - Validar estructura
 * - Delegar guardado al servicio de dominio
 * - Retornar respuesta HTTP
 * 
 * PATRÓN: CQRS + Inyección de Dependencias
 * 
 * NO hace:
 * - Lógica de negocio
 * - Acceso directo a BD
 * - Transacciones (es responsabilidad del servicio)
 * 
 * @author Senior Developer
 */
class GuardarPedidoJSONController extends Controller
{
    public function __construct(
        private GuardarPedidoDesdeJSONService $guardarService,
    ) {}

    /**
     * Guardar pedido completo desde JSON
     * 
     * Endpoint: POST /api/pedidos/guardar-desde-json
     * 
     * Body esperado:
     * {
     *   pedido_produccion_id: number,
     *   prendas: [...]
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function guardar(Request $request): JsonResponse
    {
        try {
            Log::info('📥 [GuardarPedidoJSONController] POST /api/pedidos/guardar-desde-json');

            // 1. EXTRER DATOS DEL REQUEST
            $datos = $request->all();
            $pedidoId = $datos['pedido_produccion_id'] ?? null;
            $prendas = $datos['prendas'] ?? [];

            Log::info('📦 Datos recibidos', [
                'pedido_id' => $pedidoId,
                'cantidad_prendas' => count($prendas),
            ]);

            // 2. VALIDAR DATOS
            $validacion = PedidoJSONValidator::validar($datos);

            if (!$validacion['valid']) {
                Log::warning('⚠️ Validación fallida', $validacion['errors']);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validacion['errors'],
                ], 422);
            }

            Log::info('✅ Validación exitosa');

            // 3. GUARDAR EN BD (dentro de transacción)
            $resultado = $this->guardarService->guardar($pedidoId, $prendas);

            Log::info('✅ [GuardarPedidoJSONController] Pedido guardado exitosamente', [
                'pedido_id' => $resultado['pedido_id'],
                'numero_pedido' => $resultado['numero_pedido'],
                'cantidad_prendas' => $resultado['cantidad_prendas'],
                'cantidad_items' => $resultado['cantidad_items'],
            ]);

            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            Log::error('❌ [GuardarPedidoJSONController] Error al guardar pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar pedido sin guardar
     * 
     * Endpoint: POST /api/pedidos/validar-json
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validar(Request $request): JsonResponse
    {
        try {
            Log::info('✅ [GuardarPedidoJSONController] POST /api/pedidos/validar-json');

            $datos = $request->all();
            $validacion = PedidoJSONValidator::validar($datos);

            if (!$validacion['valid']) {
                return response()->json([
                    'valid' => false,
                    'errors' => $validacion['errors'],
                ], 422);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Pedido válido',
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error validando pedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'valid' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
