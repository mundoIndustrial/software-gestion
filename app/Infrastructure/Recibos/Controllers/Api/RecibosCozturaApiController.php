<?php

namespace App\Infrastructure\Recibos\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Recibos\RecibosCozturaApplicationService;
use App\Services\Recibos\ProcesosRecibosService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller para Recibos de Costura
 * 
 * Responsabilidades:
 * - Mapear requests HTTP a servicios de aplicación
 * - Validar input HTTP
 * - Serializar respuestas
 * - Manejar errores y excepciones
 * 
 * Stack:
 * - Application Service: RecibosCozturaApplicationService
 * - Application Service: ProcesosRecibosService
 */
class RecibosCozturaApiController extends Controller
{
    protected $recibosService;
    protected $procesosService;

    public function __construct(
        RecibosCozturaApplicationService $recibosService,
        ProcesosRecibosService $procesosService
    ) {
        $this->recibosService = $recibosService;
        $this->procesosService = $procesosService;
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/recibos-costura
     * Obtener lista de recibos con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filtros = [
                'estados' => $request->input('estados', []),
                'areas' => $request->input('areas', []),
                'clientes' => $request->input('clientes', []),
                'descripcion' => $request->input('descripcion'),
                'numero_recibo' => $request->input('numero_recibo'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_dir' => $request->input('sort_dir', 'desc'),
                'per_page' => $request->input('per_page', 50),
                'page' => $request->input('page', 1),
            ];

            $resultado = $this->recibosService->obtenerRecibos($filtros);

            return response()->json([
                'success' => true,
                'data' => $resultado['datos'],
                'pagination' => $resultado['paginacion'],
                'totalCantidadGlobal' => $resultado['totalCantidadGlobal'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en filtros: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/{reciboId}
     * Obtener recibo individual
     */
    public function show(int $reciboId): JsonResponse
    {
        try {
            $recibo = $this->recibosService->obtenerRecibo($reciboId);

            return response()->json([
                'success' => true,
                'data' => $recibo
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/filtros/opciones
     * Obtener opciones de filtro dinámicas
     */
    public function obtenerOpciones(): JsonResponse
    {
        try {
            $opciones = $this->recibosService->obtenerOpcionesFilttro();

            return response()->json([
                'success' => true,
                'data' => $opciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/buscar
     * Buscar recibos en tiempo real
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $termino = $request->input('q', '');
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'El término de búsqueda debe tener al menos 2 caracteres'
                ], 422);
            }

            $resultados = $this->recibosService->buscar($termino);

            return response()->json([
                'success' => true,
                'data' => $resultados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/recibos-costura/{reciboId}/procesos
     * Agregar o actualizar proceso
     */
    public function agregarProceso(int $reciboId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'area' => 'required|string',
                'encargado' => 'sometimes|string|max:100',
                'estado' => 'sometimes|string|in:Pendiente,En Proceso,Completado,Rechazado',
            ]);

            $resultado = $this->procesosService->guardarProceso($reciboId, $validated);

            return response()->json([
                'success' => $resultado['success'],
                'action' => $resultado['action'],
                'data' => $resultado['proceso'],
                'message' => $resultado['mensaje']
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/{reciboId}/procesos
     * Obtener procesos de un recibo
     */
    public function obtenerProcesos(int $reciboId): JsonResponse
    {
        try {
            $procesos = $this->procesosService->obtenerProcesos($reciboId);

            return response()->json([
                'success' => true,
                'data' => $procesos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener procesos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/procesos/encargados
     * Obtener encargados disponibles para un área
     */
    public function obtenerEncargados(Request $request): JsonResponse
    {
        try {
            $area = $request->input('area');
            if (!$area) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro area es requerido'
                ], 422);
            }

            $encargados = $this->procesosService->obtenerEncargados($area);

            return response()->json([
                'success' => true,
                'data' => $encargados
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener encargados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/recibos-costura/procesos/areas
     * Obtener áreas disponibles
     */
    public function obtenerAreas(): JsonResponse
    {
        try {
            $areas = $this->procesosService->obtenerAreas();

            return response()->json([
                'success' => true,
                'data' => $areas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/recibos-costura/{reciboId}/procesos/{procesoId}/completar
     * Marcar proceso como completado
     */
    public function marcarCompletado(int $reciboId, int $procesoId): JsonResponse
    {
        try {
            $resultado = $this->procesosService->marcarCompletado($procesoId);

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['mensaje']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como completado: ' . $e->getMessage()
            ], 500);
        }
    }
}
