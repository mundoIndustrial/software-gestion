<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventarioTela;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AsesoresInventarioTelasController extends Controller
{
    public function index()
    {
        $telas = InventarioTela::orderBy('categoria')->orderBy('nombre_tela')->get();
        
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

            $tela = InventarioTela::create($validated);

            // Registrar en historial si hay stock inicial
            if ($validated['stock'] > 0) {
                DB::table('inventario_telas_historial')->insert([
                    'inventario_tela_id' => $tela->id,
                    'user_id' => Auth::id(),
                    'tipo_accion' => 'entrada',
                    'cantidad' => $validated['stock'],
                    'stock_anterior' => 0,
                    'stock_nuevo' => $validated['stock'],
                    'observaciones' => 'Stock inicial al crear la tela',
                    'fecha_accion' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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

            $tela = InventarioTela::findOrFail($validated['tela_id']);
            $stockAnterior = $tela->stock;

            // Calcular nuevo stock
            if ($validated['tipo_accion'] === 'entrada') {
                $nuevoStock = $stockAnterior + $validated['cantidad'];
            } else {
                $nuevoStock = $stockAnterior - $validated['cantidad'];
            }

            // Validar que el stock no sea negativo
            if ($nuevoStock < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El stock no puede ser negativo'
                ], 400);
            }

            // Actualizar stock
            $tela->stock = $nuevoStock;
            $tela->save();

            // Registrar en historial
            DB::table('inventario_telas_historial')->insert([
                'inventario_tela_id' => $tela->id,
                'user_id' => Auth::id(),
                'tipo_accion' => $validated['tipo_accion'],
                'cantidad' => $validated['cantidad'],
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock,
                'observaciones' => $validated['observaciones'],
                'fecha_accion' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado correctamente',
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $nuevoStock
            ]);
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
            // Obtener historial completo con información de tela y usuario
            $historial = DB::table('inventario_telas_historial as h')
                ->join('inventario_telas as t', 'h.inventario_tela_id', '=', 't.id')
                ->join('users as u', 'h.user_id', '=', 'u.id')
                ->select(
                    'h.*',
                    't.nombre_tela as tela_nombre',
                    't.categoria as tela_categoria',
                    'u.name as usuario_nombre'
                )
                ->orderBy('h.fecha_accion', 'desc')
                ->limit(100)
                ->get();

            // Estadísticas generales
            $estadisticas = [
                'total_entradas' => DB::table('inventario_telas_historial')
                    ->where('tipo_accion', 'entrada')
                    ->count(),
                'total_salidas' => DB::table('inventario_telas_historial')
                    ->where('tipo_accion', 'salida')
                    ->count(),
                'stock_total' => InventarioTela::sum('stock')
            ];

            // Telas más movidas (últimos 30 días)
            $telasMasMovidas = DB::table('inventario_telas_historial as h')
                ->join('inventario_telas as t', 'h.inventario_tela_id', '=', 't.id')
                ->where('h.fecha_accion', '>=', now()->subDays(30))
                ->select(
                    't.nombre_tela',
                    DB::raw('SUM(h.cantidad) as total_movimientos')
                )
                ->groupBy('t.id', 't.nombre_tela')
                ->orderBy('total_movimientos', 'desc')
                ->limit(10)
                ->get();

            // Stock actual por tela
            $stockPorTela = InventarioTela::select('nombre_tela', 'stock')
                ->orderBy('stock', 'desc')
                ->get();

            // Lista de telas para filtros
            $telas = InventarioTela::select('id', 'nombre_tela', 'categoria')
                ->orderBy('nombre_tela')
                ->get();

            return response()->json([
                'success' => true,
                'historial' => $historial,
                'estadisticas' => $estadisticas,
                'telas_mas_movidas' => $telasMasMovidas,
                'stock_por_tela' => $stockPorTela,
                'telas' => $telas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
