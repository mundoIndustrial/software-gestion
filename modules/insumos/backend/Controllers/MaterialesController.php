<?php

namespace Modules\Insumos\Backend\Controllers;

use Modules\Insumos\Backend\Services\MaterialesService;
use Illuminate\Http\Request;

class MaterialesController extends BaseController
{
    protected $materialesService;

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(MaterialesService $materialesService)
    {
        $this->materialesService = $materialesService;
        $this->middleware('auth');
        $this->middleware('insumos-access');
    }

    /**
     * Mostrar dashboard de insumos
     */
    public function dashboard()
    {
        try {
            $datos = $this->materialesService->obtenerDashboard();
            return view('insumos::dashboard', $datos);
        } catch (\Exception $e) {
            return back()->withErrors('Error al cargar dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Listar materiales con filtros
     */
    public function index(Request $request)
    {
        try {
            $filtros = $request->all();
            $materiales = $this->materialesService->obtenerMaterialesFiltrados($filtros);
            
            return view('insumos::materiales.index', [
                'materiales' => $materiales,
                'filtros' => $filtros,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors('Error al cargar materiales: ' . $e->getMessage());
        }
    }

    /**
     * Guardar material
     */
    public function store(Request $request, $numeroPedido)
    {
        try {
            $validated = $request->validate([
                'nombre_insumo' => 'required|string',
                'cantidad' => 'required|numeric',
                'estado' => 'required|in:No iniciado,En Ejecución,Anulada',
                'area' => 'required|in:Corte,Creación de orden,Creación',
                'observaciones' => 'nullable|string',
            ]);

            $validated['numero_pedido'] = $numeroPedido;
            
            $this->materialesService->guardarMateriales([$validated]);
            
            return back()->with('success', 'Material guardado exitosamente');
        } catch (\Exception $e) {
            return back()->withErrors('Error al guardar material: ' . $e->getMessage());
        }
    }

    /**
     * Obtener material específico (API)
     */
    public function show($numeroPedido)
    {
        try {
            $materiales = $this->materialesService->obtenerMaterialesFiltrados([
                'numero_pedido' => $numeroPedido,
            ]);

            return response()->json([
                'success' => true,
                'data' => $materiales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar material
     */
    public function destroy($numeroPedido, Request $request)
    {
        try {
            $id = $request->input('id');
            $this->materialesService->eliminarMaterial($id);
            
            return back()->with('success', 'Material eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->withErrors('Error al eliminar material: ' . $e->getMessage());
        }
    }

    /**
     * Obtener opciones de filtro (API)
     */
    public function obtenerFiltros($column)
    {
        try {
            $filtros = $this->materialesService->obtenerOpcionesFiltro($column);
            
            return response()->json([
                'success' => true,
                'options' => $filtros,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
