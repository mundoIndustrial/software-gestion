<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\UseCases\GuardarPedidoDesdeJSONUseCase;
use App\Application\Pedidos\UseCases\ValidarPedidoDesdeJSONUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

/**
 * GuardarPedidoJSONController - REFACTORIZADO CON USE CASES
 * 
 * Responsabilidad:
 * - Recibir JSON del frontend
 * - Validar estructura
 * - Usar Use Cases de DDD
 * - Retornar respuesta HTTP
 * 
 * Patrón: Use Cases (DDD) + Dependency Injection
 * SRP: Solo HTTP, delegando lógica a Use Cases
 */
class GuardarPedidoJSONController extends Controller
{
    public function __construct(
        private GuardarPedidoDesdeJSONUseCase $guardarUseCase,
        private ValidarPedidoDesdeJSONUseCase $validarUseCase,
    ) {}

    /**
     * Guardar pedido completo desde JSON
     * 
     * Endpoint: POST /api/pedidos/guardar-desde-json
     */
    public function guardar(Request $request): JsonResponse
    {
        try {
            Log::info('[GuardarPedidoJSONController] POST /api/pedidos/guardar-desde-json');

            // Extraer datos del request
            $datos = $request->all();
            $pedidoId = $datos['pedido_produccion_id'] ?? null;
            $prendas = $datos['prendas'] ?? [];

            Log::info('Datos recibidos', [
                'pedido_id' => $pedidoId,
                'cantidad_prendas' => count($prendas),
            ]);

            // Validar usando Use Case
            $validacion = $this->validarUseCase->ejecutar($datos);

            if (!$validacion['valid']) {
                Log::warning('Validación fallida', $validacion['errors']);
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validacion['errors'],
                ], 422);
            }

            Log::info('Validación exitosa');

            // Guardar usando Use Case
            $resultado = $this->guardarUseCase->ejecutar($pedidoId, $prendas);

            Log::info('[GuardarPedidoJSONController] Pedido guardado exitosamente', [
                'pedido_id' => $resultado['pedido_id'],
                'numero_pedido' => $resultado['numero_pedido'],
                'cantidad_prendas' => $resultado['cantidad_prendas'],
                'cantidad_items' => $resultado['cantidad_items'],
            ]);

            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            Log::error('[GuardarPedidoJSONController] Error al guardar pedido', [
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
     */
    public function validar(Request $request): JsonResponse
    {
        try {
            Log::info('[GuardarPedidoJSONController] POST /api/pedidos/validar-json');

            $datos = $request->all();
            
            // Validar usando Use Case
            $validacion = $this->validarUseCase->ejecutar($datos);

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
            Log::error('Error validando pedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'valid' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
