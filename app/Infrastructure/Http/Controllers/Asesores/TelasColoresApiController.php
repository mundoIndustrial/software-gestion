<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ObtenerCatalogoColoresAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerCatalogoTelasAsesorUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * TelasColoresApiController
 * 
 * API endpoints que sirven datos de telas y colores para el frontend
 * GET /asesores/api/telas
 * GET /asesores/api/colores
 */
class TelasColoresApiController extends Controller
{
    public function __construct(
        private readonly ObtenerCatalogoTelasAsesorUseCase $obtenerCatalogoTelasAsesorUseCase,
        private readonly ObtenerCatalogoColoresAsesorUseCase $obtenerCatalogoColoresAsesorUseCase
    ) {
    }

    /**
     * Retorna todas las telas disponibles
     */
    public function getTelas(): JsonResponse
    {
        try {
            $telas = $this->obtenerCatalogoTelasAsesorUseCase->ejecutar();

            return response()->json([
                'success' => true,
                'data' => $telas,
                'count' => count($telas),
            ]);
        } catch (\Throwable $e) {
            \Log::error('[TelasColoresApiController::getTelas] Error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener telas.',
            ], 500);
        }
    }

    /**
     * Retorna todos los colores disponibles
     */
    public function getColores(): JsonResponse
    {
        try {
            $colores = $this->obtenerCatalogoColoresAsesorUseCase->ejecutar();

            return response()->json([
                'success' => true,
                'data' => $colores,
                'count' => count($colores),
            ]);
        } catch (\Throwable $e) {
            \Log::error('[TelasColoresApiController::getColores] Error', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener colores.',
            ], 500);
        }
    }
}
