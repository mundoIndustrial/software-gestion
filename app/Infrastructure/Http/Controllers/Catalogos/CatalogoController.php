<?php

namespace App\Infrastructure\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\Catalogo\ObtenerTipoPrendasUseCase;
use App\Application\Pedidos\UseCases\Catalogo\ReconocerPrendaUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * CatalogoController
 *
 * Gestión de catálogos de tipos de prenda.
 * Arquitectura DDD: Repository → UseCase → Controller
 */
class CatalogoController extends Controller
{
    public function __construct(
        private readonly ObtenerTipoPrendasUseCase $obtenerTipoPrendasUseCase,
        private readonly ReconocerPrendaUseCase $reconocerPrendaUseCase,
    ) {}

    /**
     * GET /api/tipos-prenda
     * 
     * Obtener listado de todos los tipos de prenda disponibles
     */
    public function tiposPrenda(): JsonResponse
    {
        try {
            $tipos = $this->obtenerTipoPrendasUseCase->execute();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo tipos de prenda', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de prenda'
            ], 500);
        }
    }

    /**
     * POST /api/prenda/reconocer
     * 
     * Reconocer el tipo de prenda por nombre
     */
    public function reconocerPrenda(Request $request): JsonResponse
    {
        try {
            $nombre = $request->input('nombre');

            $tipo = $this->reconocerPrendaUseCase->execute($nombre);

            return response()->json([
                'success' => true,
                'tipo' => $tipo
            ]);
        } catch (\Exception $e) {
            Log::error('Error reconociendo tipo de prenda', [
                'nombre' => $request->input('nombre'),
                'error' => $e->getMessage()
            ]);

            $statusCode = $e->getCode() ?: 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }
}

