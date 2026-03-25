<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerPrendasAutocompleteUseCase;
use App\Application\Pedidos\DTOs\ObtenerPrendasInput;

/**
 * ObtenerPrendasAutocompleteController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: Manejar HTTP para búsqueda autocomplete de prendas
 * 
 * HTTP Methods:
 * - GET /asesores/api/prendas/autocomplete  → obtenerPrendas()
 * 
 * Dependencias:
 * - ObtenerPrendasAutocompleteUseCase: Lógica para búsqueda
 * 
 * Características:
 * ✅ Solo 1 dependencia
 * ✅ Solo adaptador HTTP
 * ✅ Validación de query en DTO
 */
class ObtenerPrendasAutocompleteController extends Controller
{
    public function __construct(
        private ObtenerPrendasAutocompleteUseCase $obtenerPrendasUseCase,
    ) {}

    /**
     * GET /asesores/api/prendas/autocomplete
     * 
     * Obtener prendas que coincidan con el término de búsqueda
     * 
     * Query Parameters:
     * - q (string, required): Término de búsqueda
     * - limit (int, optional, default 50): Límite de resultados
     * 
     * @return JsonResponse
     */
    public function obtenerPrendas(Request $request): JsonResponse
    {
        try {
            Log::info('[ObtenerPrendasAutocompleteController::obtenerPrendas] Iniciado', [
                'query' => $request->input('q'),
                'limit' => $request->input('limit', 50),
            ]);

            // 1. Convertir Request → DTO (incluye validación)
            $input = ObtenerPrendasInput::fromRequest($request);

            // 2. Ejecutar lógica de negocio
            $output = $this->obtenerPrendasUseCase->ejecutar($input);

            // 3. Retornar respuesta
            Log::info('[ObtenerPrendasAutocompleteController::obtenerPrendas] Completado', [
                'query' => $request->input('q'),
                'resultados' => count($output->prendas ?? []),
            ]);

            return response()->json([
                'success' => true,
                'prendas' => $output->prendas ?? [],
                'total' => count($output->prendas ?? []),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ObtenerPrendasAutocompleteController::obtenerPrendas] Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[ObtenerPrendasAutocompleteController::obtenerPrendas] Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas: ' . $e->getMessage(),
            ], 500);
        }
    }
}
