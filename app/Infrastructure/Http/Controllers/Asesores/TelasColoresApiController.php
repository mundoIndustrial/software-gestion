<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

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
    /**
     * Retorna todas las telas disponibles
     */
    public function getTelas(): JsonResponse
    {
        try {
            // Datos de prueba - retornar array vacío o algunos datos de ejemplo
            $telas = [];
            
            try {
                // Intentar cargar desde la BD si está disponible
                $telas = \App\Models\CatalogoTela::where('activo', true)
                    ->select('id', 'nombre', 'codigo')
                    ->orderBy('nombre')
                    ->get()
                    ->toArray();
            } catch (\Exception $dbError) {
                // Si hay error con la BD, retornar array vacío
                \Log::warning('[TelasColoresApiController] Error loading telas from DB', [
                    'error' => $dbError->getMessage()
                ]);
                $telas = [];
            }

            return response()->json([
                'success' => true,
                'data' => $telas,
                'count' => count($telas),
            ]);
        } catch (\Exception $e) {
            \Log::error('[TelasColoresApiController::getTelas] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna todos los colores disponibles
     */
    public function getColores(): JsonResponse
    {
        try {
            // Datos de prueba - retornar array vacío o algunos datos de ejemplo
            $colores = [];
            
            try {
                // Intentar cargar desde la BD si está disponible
                $colores = \App\Models\ColorPrenda::where('activo', true)
                    ->select('id', 'nombre', 'codigo')
                    ->orderBy('nombre')
                    ->get()
                    ->toArray();
            } catch (\Exception $dbError) {
                // Si hay error con la BD, retornar array vacío
                \Log::warning('[TelasColoresApiController] Error loading colores from DB', [
                    'error' => $dbError->getMessage()
                ]);
                $colores = [];
            }

            return response()->json([
                'success' => true,
                'data' => $colores,
                'count' => count($colores),
            ]);
        } catch (\Exception $e) {
            \Log::error('[TelasColoresApiController::getColores] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
