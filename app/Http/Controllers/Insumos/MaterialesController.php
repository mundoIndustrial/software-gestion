<?php

namespace App\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivosRecibosPedidos;
use App\Services\Insumos\MaterialesService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/**
 * Controller para gestión del módulo Insumos
 * Implementa patrón con inyección de dependencias
 */
class MaterialesController extends Controller
{
    protected $materialesService;

    public function __construct(MaterialesService $materialesService)
    {
        $this->materialesService = $materialesService;
        $this->middleware('auth');
        $this->middleware('insumos-access');
    }

    /**
     * Dashboard del módulo de materiales
     */
    public function dashboard()
    {
        $dashboard = $this->materialesService->obtenerDashboard();

        return view('insumos.dashboard', [
            'user' => Auth::user(),
            'dashboard' => $dashboard,
        ]);
    }

    /**
     * Listar materiales con filtros y información de demora
     */
    public function index(Request $request)
    {
        $filtros = $request->only([
            'numero_pedido',
            'cliente',
            'descripcion',
            'estado',
            'area',
            'fecha_de_creacion_de_orden'
        ]);

        // Obtener materiales enriquecidos con demoras
        $conDemora = $request->boolean('con_demora', true);
        $materiales = $this->materialesService->obtenerMaterialesFiltrados(
            $filtros,
            $request->get('per_page', 25),
            $conDemora
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $materiales->items(),
                'pagination' => [
                    'current_page' => $materiales->currentPage(),
                    'per_page' => $materiales->perPage(),
                    'total' => $materiales->total(),
                    'last_page' => $materiales->lastPage(),
                ],
            ]);
        }

        return view('insumos.materiales.index', [
            'materiales' => $materiales,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Guardar materiales de una orden
     */
    public function store(Request $request, $numeroPedido)
    {
        try {
            // Validar acceso
            if (!$this->materialesService->validarAccesoOrden($numeroPedido, Auth::user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta orden'
                ], 403);
            }

            $materiales = $request->get('materiales', []);

            if (empty($materiales)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay materiales para guardar'
                ], 400);
            }

            $resultados = $this->materialesService->guardarMateriales(
                $numeroPedido,
                $materiales
            );

            return response()->json([
                'success' => true,
                'message' => 'Materiales guardados correctamente',
                'data' => $resultados,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar materiales: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar materiales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materiales de una orden con demoras
     */
    public function show($numeroPedido)
    {
        try {
            $materiales = $this->materialesService->obtenerMaterialesFiltrados([
                'numero_pedido' => $numeroPedido
            ], 1000, true); // Obtener todo sin paginación

            // Obtener resumen de demoras
            $resumenDemoras = $this->materialesService->obtenerResumenDemorasPorPedido($numeroPedido);

            return response()->json([
                'success' => true,
                'data' => $materiales->items() ?? $materiales->toArray() ?? [],
                'resumen_demoras' => $resumenDemoras,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener materiales: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener materiales'
            ], 500);
        }
    }

    /**
     * Eliminar un material
     */
    public function destroy(Request $request, $numeroPedido)
    {
        try {
            $prendaPedidoId = $request->get('prenda_pedido_id');

            if (!$prendaPedidoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de prenda requerido'
                ], 400);
            }

            $resultado = $this->materialesService->eliminarMaterial(
                $numeroPedido,
                $prendaPedidoId
            );

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar material: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar material'
            ], 500);
        }
    }

    /**
     * Obtener opciones de filtro
     */
    public function obtenerFiltros($column)
    {
        try {
            $opciones = $this->materialesService->obtenerOpcionesFiltro($column);

            return response()->json([
                'success' => true,
                'column' => $column,
                'opciones' => $opciones,
                'total' => count($opciones),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Error al obtener filtros: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener filtros'
            ], 500);
        }
    }

    /**
     * Cambiar el estado de un pedido
     */
    public function cambiarEstado($numeroPedido, Request $request)
    {
        try {
            $nuevoEstado = $request->input('estado');

            if (!$nuevoEstado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estado requerido'
                ], 400);
            }

            $resultado = $this->materialesService->cambiarEstadoPedido(
                $numeroPedido,
                $nuevoEstado
            );

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado'
            ], 500);
        }
    }

    /**
     * Alterar el estado de marcado de un recibo (marcar_plooter)
     */
    public function toggleMarcado(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'marcado' => 'required|boolean',
            ]);

            $recibo = ConsecutivosRecibosPedidos::findOrFail($reciboId);
            $recibo->update(['marcar_plooter' => $request->boolean('marcado')]);

            return response()->json([
                'success' => true,
                'message' => 'Estado de marcado actualizado',
                'data' => $recibo,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar marcado: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado de marcado'
            ], 500);
        }
    }

    /**
     * Pasar recibo a revisar (cambiar estado a DEVUELTO_ASESOR)
     */
    public function pasarRevisar(Request $request, $reciboId)
    {
        try {
            $request->validate([
                'motivo' => 'required|string|min:10|max:500',
            ]);

            $recibo = ConsecutivosRecibosPedidos::findOrFail($reciboId);
            
            // Cambiar estado a DEVUELTO_ASESOR
            $recibo->update([
                'estado' => 'DEVUELTO_ASESOR',
                'notas' => $request->input('motivo'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recibo pasado a revisar correctamente',
                'data' => $recibo,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al pasar a revisar: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al pasar a revisar: ' . $e->getMessage()
            ], 500);
        }
    }
}
