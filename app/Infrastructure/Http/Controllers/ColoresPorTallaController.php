<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Pedidos\Services\ColoresPorTallaService;
use App\Domain\Pedidos\ValueObjects\AsignacionColor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller para el sistema de Colores por Talla
 * Gestiona las asignaciones de colores a tallas de prendas
 */
class ColoresPorTallaController extends Controller
{
    private ColoresPorTallaService $coloresPorTallaService;

    public function __construct(ColoresPorTallaService $coloresPorTallaService)
    {
        $this->coloresPorTallaService = $coloresPorTallaService;
    }

    /**
     * Obtener todas las asignaciones de colores
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['genero', 'talla', 'tela']);
            $asignaciones = $this->coloresPorTallaService->obtenerAsignaciones($filters);
            
            return response()->json([
                'success' => true,
                'data' => $asignaciones,
                'total' => count($asignaciones),
                'total_unidades' => array_sum(array_column($asignaciones, 'total_unidades'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo asignaciones de colores', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asignaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nueva asignación de colores
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'genero' => 'required|string|in:dama,caballero,unisex',
                'talla' => 'required|string',
                'tipo_talla' => 'required|string|in:Letra,Número',
                'tela' => 'required|string',
                'colores' => 'required|array|min:1',
                'colores.*.color' => 'required|string',
                'colores.*.cantidad' => 'required|integer|min:1'
            ], [
                'genero.required' => 'El género es requerido',
                'genero.in' => 'El género debe ser dama, caballero o unisex',
                'talla.required' => 'La talla es requerida',
                'tipo_talla.required' => 'El tipo de talla es requerido',
                'tipo_talla.in' => 'El tipo de talla debe ser Letra o Número',
                'tela.required' => 'La tela es requerida',
                'colores.required' => 'Debe agregar al menos un color',
                'colores.min' => 'Debe agregar al menos un color',
                'colores.*.color.required' => 'El nombre del color es requerido',
                'colores.*.cantidad.required' => 'La cantidad es requerida',
                'colores.*.cantidad.min' => 'La cantidad debe ser mayor a 0'
            ]);

            $asignacion = $this->coloresPorTallaService->guardarAsignacion($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Asignación guardada exitosamente',
                'data' => $asignacion
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error guardando asignación de colores', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una asignación existente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'colores' => 'required|array|min:1',
                'colores.*.color' => 'required|string',
                'colores.*.cantidad' => 'required|integer|min:1'
            ]);

            $asignacion = $this->coloresPorTallaService->actualizarAsignacion($id, $validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Asignación actualizada exitosamente',
                'data' => $asignacion
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando asignación de colores', [
                'id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una asignación
     */
    public function destroy($id): JsonResponse
    {
        try {
            $eliminado = $this->coloresPorTallaService->eliminarAsignacion($id);
            
            if (!$eliminado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Asignación no encontrada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Asignación eliminada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error eliminando asignación de colores', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener colores disponibles para una talla específica
     */
    public function coloresDisponibles($genero, $talla): JsonResponse
    {
        try {
            $colores = $this->coloresPorTallaService->obtenerColoresDisponibles($genero, $talla);
            
            return response()->json([
                'success' => true,
                'data' => $colores,
                'total' => count($colores)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo colores disponibles', [
                'genero' => $genero,
                'talla' => $talla,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tallas disponibles para un género
     */
    public function tallasDisponibles($genero): JsonResponse
    {
        try {
            $tallas = $this->coloresPorTallaService->obtenerTallasDisponibles($genero);
            
            return response()->json([
                'success' => true,
                'data' => $tallas,
                'total' => count($tallas)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo tallas disponibles', [
                'genero' => $genero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tallas disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar asignación del wizard (múltiples tallas)
     */
    public function procesarAsignacionWizard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'genero' => 'required|string|in:dama,caballero,unisex',
                'tipo_talla' => 'required|string|in:Letra,Número',
                'tela' => 'required|string',
                'tallas' => 'required|array|min:1',
                'tallas.*.talla' => 'required|string',
                'tallas.*.colores' => 'required|array|min:1',
                'tallas.*.colores.*.color' => 'required|string',
                'tallas.*.colores.*.cantidad' => 'required|integer|min:1'
            ], [
                'genero.required' => 'El género es requerido',
                'genero.in' => 'El género debe ser dama, caballero o unisex',
                'tipo_talla.required' => 'El tipo de talla es requerido',
                'tipo_talla.in' => 'El tipo de talla debe ser Letra o Número',
                'tela.required' => 'La tela es requerida',
                'tallas.required' => 'Debe seleccionar al menos una talla',
                'tallas.min' => 'Debe seleccionar al menos una talla',
                'tallas.*.talla.required' => 'La talla es requerida',
                'tallas.*.colores.required' => 'Debe agregar al menos un color',
                'tallas.*.colores.min' => 'Debe agregar al menos un color',
                'tallas.*.colores.*.color.required' => 'El nombre del color es requerido',
                'tallas.*.colores.*.cantidad.required' => 'La cantidad es requerida',
                'tallas.*.colores.*.cantidad.min' => 'La cantidad debe ser mayor a 0'
            ]);

            $resultado = $this->coloresPorTallaService->procesarAsignacionWizard($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Asignaciones procesadas exitosamente',
                'data' => $resultado,
                'total_asignaciones' => count($resultado),
                'total_unidades' => array_sum(array_column($resultado, 'total_unidades'))
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error procesando asignación wizard', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la asignación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
