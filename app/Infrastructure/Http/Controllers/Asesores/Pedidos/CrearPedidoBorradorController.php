<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\UseCases\Pedidos\GuardarBorradorUseCase;
use App\Application\UseCases\Pedidos\ActualizarBorradorUseCase;
use App\Application\UseCases\Pedidos\GuardarBorradorInput;
use App\Application\UseCases\Pedidos\ActualizarBorradorInput;

/**
 * CrearPedidoBorradorController
 * 
 *  RESPONSABILIDAD ÚNICA: Manejar HTTP para guardar/actualizar borradores de pedidos
 * 
 * HTTP Methods:
 * - POST /asesores/pedidos/borrador              → guardarBorrador()
 * - PUT /asesores/pedidos/{pedidoId}/borrador    → actualizarBorrador()
 * 
 * Dependencias:
 * - GuardarBorradorUseCase: Lógica para guardar nuevo
 * - ActualizarBorradorUseCase: Lógica para actualizar existente
 * 
 * Características:
 *  Solo 2 dependencias
 *  Solo adaptador HTTP
 *  Clara separación saveVS update
 */
class CrearPedidoBorradorController extends Controller
{
    public function __construct(
        private GuardarBorradorUseCase $guardarBorradorUseCase,
        private ActualizarBorradorUseCase $actualizarBorradorUseCase,
    ) {}

    /**
     * POST /asesores/pedidos/borrador
     * 
     * Guardar un nuevo borrador de pedido
     * 
     * @return JsonResponse
     */
    public function guardarBorrador(Request $request): JsonResponse
    {
        try {
            Log::info('[CrearPedidoBorradorController::guardarBorrador] Iniciado', [
                'usuario_id' => Auth::id(),
            ]);

            // 1. Convertir Request → DTO
            $input = GuardarBorradorInput::fromRequest($request, Auth::id());

            // 2. Ejecutar lógica de negocio
            $output = $this->guardarBorradorUseCase->ejecutar($input);

            // 3. Retornar respuesta
            $statusCode = $output->success ? 200 : 500;

            Log::info('[CrearPedidoBorradorController::guardarBorrador] Completado', [
                'usuario_id' => Auth::id(),
                'success' => $output->success,
                'pedido_id' => $output->pedido_id ?? null,
            ]);

            return response()->json(
                $output->toArray(),
                $statusCode
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearPedidoBorradorController::guardarBorrador] Validación fallida', [
                'usuario_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CrearPedidoBorradorController::guardarBorrador] Error', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /asesores/pedidos/{pedidoId}/borrador
     * 
     * Actualizar un borrador existente
     * 
     * @param int|string $pedidoId
     * @return JsonResponse
     */
    public function actualizarBorrador($pedidoId, Request $request): JsonResponse
    {
        try {
            Log::info('[CrearPedidoBorradorController::actualizarBorrador] Iniciado', [
                'usuario_id' => Auth::id(),
                'pedido_id' => $pedidoId,
            ]);

            // 1. Convertir Request → DTO
            $input = ActualizarBorradorInput::fromRequest(
                $request,
                (int) $pedidoId,
                Auth::id()
            );

            // 2. Ejecutar lógica de negocio
            $output = $this->actualizarBorradorUseCase->ejecutar($input);

            // 3. Retornar respuesta
            $statusCode = $output->success ? 200 : 500;

            Log::info('[CrearPedidoBorradorController::actualizarBorrador] Completado', [
                'usuario_id' => Auth::id(),
                'pedido_id' => $pedidoId,
                'success' => $output->success,
            ]);

            return response()->json(
                $output->toArray(),
                $statusCode
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearPedidoBorradorController::actualizarBorrador] Validación fallida', [
                'usuario_id' => Auth::id(),
                'pedido_id' => $pedidoId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CrearPedidoBorradorController::actualizarBorrador] Error', [
                'usuario_id' => Auth::id(),
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar borrador: ' . $e->getMessage(),
            ], 500);
        }
    }
}
