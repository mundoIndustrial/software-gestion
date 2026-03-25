<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoCompleteUseCase;
use App\Application\Pedidos\UseCases\CrearPedidoInput;

/**
 * CrearPedidoController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: Manejar HTTP para creación de pedidos
 * 
 * HTTP Methods:
 * - POST /asesores/pedidos                 → crearPedido()
 * 
 * Dependencias:
 * - CrearPedidoCompleteUseCase: Lógica de negocio para crear
 * 
 * Características:
 * ✅ Solo 1 dependencia (antes 21)
 * ✅ Solo adaptador HTTP (no contiene lógica)
 * ✅ Fácil de testear
 * ✅ Fácil de mantener
 * ✅ Clear responsibility
 */
class CrearPedidoController extends Controller
{
    public function __construct(
        private CrearPedidoCompleteUseCase $crearPedidoUseCase,
    ) {}

    /**
     * POST /asesores/pedidos
     * 
     * Crear pedido transaccional
     * 
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            Log::info('[CrearPedidoController::crearPedido] Iniciado', [
                'usuario_id' => Auth::id(),
            ]);

            // 1. Convertir Request → DTO (Mapeo HTTP)
            $input = CrearPedidoInput::fromRequest($request, Auth::id());

            // 2. Ejecutar lógica de negocio (UseCase)
            $output = $this->crearPedidoUseCase->ejecutar($input);

            // 3. Retornar respuesta HTTP
            $statusCode = $output->success ? 200 : 500;

            Log::info('[CrearPedidoController::crearPedido] Completado', [
                'usuario_id' => Auth::id(),
                'success' => $output->success,
                'pedido_id' => $output->pedido_id ?? null,
            ]);

            return response()->json(
                $output->toArray(),
                $statusCode
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearPedidoController::crearPedido] Validación fallida', [
                'usuario_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CrearPedidoController::crearPedido] Error', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

}
