<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ObtenerPrendasInput;
use App\Application\Pedidos\UseCases\ObtenerPrendasAutocompleteUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerPrendasAutocompleteController
 *
 * Responsabilidad unica: manejar HTTP para busqueda autocomplete de prendas.
 */
class ObtenerPrendasAutocompleteController extends Controller
{
    public function __construct(
        private readonly ObtenerPrendasAutocompleteUseCase $obtenerPrendasUseCase,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * GET /asesores/api/prendas/autocomplete
     */
    public function obtenerPrendas(Request $request): JsonResponse
    {
        try {
            Log::info('[ObtenerPrendasAutocompleteController::obtenerPrendas] Iniciado', [
                'query' => $request->input('q'),
                'limit' => $request->input('limit', 50),
            ]);

            $input = ObtenerPrendasInput::fromRequest($request);
            $output = $this->obtenerPrendasUseCase->ejecutar($input);

            Log::info('[ObtenerPrendasAutocompleteController::obtenerPrendas] Completado', [
                'query' => $request->input('q'),
                'resultados' => count($output->prendas ?? []),
            ]);

            return $this->json([
                'success' => true,
                'prendas' => $output->prendas ?? [],
                'total' => count($output->prendas ?? []),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ObtenerPrendasAutocompleteController::obtenerPrendas] Validacion fallida', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Parametros invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[ObtenerPrendasAutocompleteController::obtenerPrendas] Error', [
                'query' => $request->input('q'),
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al obtener prendas: ' . $e->getMessage(), 500);
        }
    }
}
