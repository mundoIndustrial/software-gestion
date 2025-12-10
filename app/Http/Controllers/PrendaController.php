<?php

namespace App\Http\Controllers;

use App\Application\Actions\CrearPrendaAction;
use App\Application\DTOs\CrearPrendaDTO;
use App\Application\Services\PrendaServiceNew;
use App\Http\Requests\CrearPrendaRequest;
use App\Http\Resources\PrendaResource;
use App\Http\Resources\PrendaColeccionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrendaController extends Controller
{
    public function __construct(
        private PrendaServiceNew $prendaService,
        private CrearPrendaAction $crearPrendaAction,
    ) {}

    /**
     * Listar prendas
     */
    public function index(Request $request): JsonResponse
    {
        \Log::info('📋 Listando prendas');

        try {
            $pagina = $request->get('page', 1);
            $porPagina = $request->get('per_page', 15);

            $prendas = $this->prendaService->listar($pagina, $porPagina);

            return response()->json([
                'success' => true,
                'data' => PrendaColeccionResource::collection($prendas),
                'pagination' => [
                    'total' => $prendas->total(),
                    'per_page' => $prendas->perPage(),
                    'current_page' => $prendas->currentPage(),
                    'last_page' => $prendas->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error listando prendas', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al listar prendas',
            ], 500);
        }
    }

    /**
     * Crear nueva prenda
     */
    public function store(CrearPrendaRequest $request): JsonResponse
    {
        \Log::info('🚀 Creando nueva prenda', [
            'nombre' => $request->input('nombre_producto'),
        ]);

        try {
            // Transformar request a DTO
            $dto = CrearPrendaDTO::fromRequest($request->validated());

            // Ejecutar acción
            $prenda = $this->crearPrendaAction->ejecutar($dto);

            \Log::info('✅ Prenda creada exitosamente', [
                'prenda_id' => $prenda->id,
            ]);

            return response()->json([
                'success' => true,
                'data' => new PrendaResource($prenda),
                'message' => 'Prenda creada exitosamente',
            ], 201);

        } catch (\Exception $e) {
            \Log::error('❌ Error creando prenda', [
                'error' => $e->getMessage(),
                'nombre' => $request->input('nombre_producto'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener prenda por ID
     */
    public function show(int $id): JsonResponse
    {
        \Log::info('📖 Obteniendo prenda', ['prenda_id' => $id]);

        try {
            $prenda = $this->prendaService->obtenerPorId($id);

            return response()->json([
                'success' => true,
                'data' => new PrendaResource($prenda),
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error obteniendo prenda', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada',
            ], 404);
        }
    }

    /**
     * Actualizar prenda
     */
    public function update(int $id, CrearPrendaRequest $request): JsonResponse
    {
        \Log::info('🔄 Actualizando prenda', ['prenda_id' => $id]);

        try {
            $dto = CrearPrendaDTO::fromRequest($request->validated());
            $prenda = $this->prendaService->actualizar($id, $dto);

            \Log::info('✅ Prenda actualizada', ['prenda_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => new PrendaResource($prenda),
                'message' => 'Prenda actualizada exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error actualizando prenda', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda',
            ], 500);
        }
    }

    /**
     * Eliminar prenda
     */
    public function destroy(int $id): JsonResponse
    {
        \Log::info('🗑️ Eliminando prenda', ['prenda_id' => $id]);

        try {
            $this->prendaService->eliminar($id);

            \Log::info('✅ Prenda eliminada', ['prenda_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error eliminando prenda', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar prenda',
            ], 500);
        }
    }

    /**
     * Buscar prendas
     */
    public function search(Request $request): JsonResponse
    {
        \Log::info('🔍 Buscando prendas', [
            'termino' => $request->input('q'),
        ]);

        try {
            $termino = $request->input('q', '');
            $pagina = $request->get('page', 1);
            $porPagina = $request->get('per_page', 15);

            if (empty($termino)) {
                return $this->index($request);
            }

            $prendas = $this->prendaService->buscar($termino, $pagina, $porPagina);

            return response()->json([
                'success' => true,
                'data' => PrendaColeccionResource::collection($prendas),
                'pagination' => [
                    'total' => $prendas->total(),
                    'per_page' => $prendas->perPage(),
                    'current_page' => $prendas->currentPage(),
                    'last_page' => $prendas->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error buscando prendas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar prendas',
            ], 500);
        }
    }

    /**
     * Obtener estadísticas
     */
    public function estadisticas(): JsonResponse
    {
        \Log::info('📊 Obteniendo estadísticas de prendas');

        try {
            $estadisticas = $this->prendaService->obtenerEstadisticas();

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Error obteniendo estadísticas', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
            ], 500);
        }
    }
}
