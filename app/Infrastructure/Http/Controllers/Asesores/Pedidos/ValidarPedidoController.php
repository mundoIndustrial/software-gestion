<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\UseCases\Pedidos\ValidarPedidoUseCase;
use App\Application\UseCases\Pedidos\ValidarPedidoInput;

/**
 * ValidarPedidoController
 * 
 *  RESPONSABILIDAD ÚNICA: Manejar HTTP para validación de pedidos
 * 
 * HTTP Methods:
 * - POST /asesores/pedidos/validar  → validarPedido()
 * 
 * Dependencias:
 * - ValidarPedidoUseCase: Lógica de validación de pedidos
 * 
 * Características:
 *  Solo 1 dependencia
 *  Solo adaptador HTTP (no contiene lógica)
 *  Fácil de testear
 *  Fácil de mantener
 *  Clear responsibility
 * 
 * Flujo:
 * Frontend → ValidarPedidoController → ValidarPedidoUseCase → Response
 */
class ValidarPedidoController extends Controller
{
    public function __construct(
        private ValidarPedidoUseCase $validarPedidoUseCase,
    ) {}

    /**
     * POST /asesores/pedidos/validar
     * 
     * Validar estructura y datos de pedido antes de crear
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            $usuarioId = Auth::id();

            Log::info('[ValidarPedidoController::validarPedido] Iniciado', [
                'usuario_id' => $usuarioId,
            ]);

            // 1. Convertir Request → DTO (Mapeo HTTP)
            $input = ValidarPedidoInput::fromRequest($request, $usuarioId);

            // 2. Ejecutar lógica de negocio (UseCase)
            $output = $this->validarPedidoUseCase->ejecutar($input);

            // 3. Retornar respuesta HTTP
            $statusCode = $output->success ? 200 : 400;

            Log::info('[ValidarPedidoController::validarPedido] Completado', [
                'usuario_id' => $usuarioId,
                'valido' => $output->success,
                'cliente_id' => $output->clienteId ?? null,
            ]);

            return response()->json(
                $output->toArray(),
                $statusCode
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ValidarPedidoController::validarPedido] Validación fallida', [
                'usuario_id' => Auth::id(),
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'esValido' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[ValidarPedidoController::validarPedido] Error', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'esValido' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
