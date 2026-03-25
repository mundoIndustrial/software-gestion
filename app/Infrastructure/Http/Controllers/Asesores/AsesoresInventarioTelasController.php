<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Application\InventarioTelas\UseCases\ObtenerInventarioTelasUseCase;
use App\Application\InventarioTelas\UseCases\CrearInventarioTelaUseCase;
use App\Application\InventarioTelas\UseCases\AjustarStockInventarioTelaUseCase;
use App\Application\InventarioTelas\UseCases\ObtenerHistorialInventarioUseCase;
use App\Application\InventarioTelas\UseCases\EliminarInventarioTelaUseCase;

class AsesoresInventarioTelasController extends Controller
{
    public function __construct(
        private ObtenerInventarioTelasUseCase $obtenerTelasUseCase,
        private CrearInventarioTelaUseCase $crearTelaUseCase,
        private AjustarStockInventarioTelaUseCase $ajustarStockUseCase,
        private ObtenerHistorialInventarioUseCase $obtenerHistorialUseCase,
        private EliminarInventarioTelaUseCase $eliminarTelaUseCase,
    ) {}

    public function index()
    {
        $telas = $this->obtenerTelasUseCase->ejecutar();
        $user = Auth::user();
        
        // Si el usuario tiene el rol "insumos", retornar con layout de insumos
        if ($user->hasRole('insumos')) {
            return view('inventario-telas.index-insumos', compact('telas'));
        }
        
        // Si es asesor, retornar con layout de asesores
        return view('asesores.inventario-telas.index', compact('telas'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'categoria' => 'required|string|max:100',
                'nombre_tela' => 'required|string|max:100',
                'stock' => 'required|numeric|min:0',
                'metraje_sugerido' => 'nullable|numeric|min:0',
            ]);

            $tela = $this->crearTelaUseCase->ejecutar($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tela creada correctamente',
                'tela' => $tela
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tela: ' . $e->getMessage()
            ], 500);
        }
    }

    public function ajustarStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'tela_id' => 'required|exists:inventario_telas,id',
                'tipo_accion' => 'required|in:entrada,salida',
                'cantidad' => 'required|numeric|min:0.01',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->ajustarStockUseCase->ejecutar(
                telaId: $validated['tela_id'],
                tipoAccion: $validated['tipo_accion'],
                cantidad: $validated['cantidad'],
                observaciones: $validated['observaciones'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado correctamente',
                'stock_anterior' => $resultado['stock_anterior'],
                'stock_nuevo' => $resultado['stock_nuevo']
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar el stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function historial()
    {
        try {
            $datos = $this->obtenerHistorialUseCase->ejecutar();

            return response()->json([
                'success' => true,
                'historial' => $datos['historial'],
                'estadisticas' => $datos['estadisticas'],
                'telas_mas_movidas' => $datos['telas_mas_movidas'],
                'stock_por_tela' => $datos['stock_por_tela'],
                'telas' => $datos['telas'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->eliminarTelaUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Tela eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tela: ' . $e->getMessage()
            ], 500);
        }
    }
}

