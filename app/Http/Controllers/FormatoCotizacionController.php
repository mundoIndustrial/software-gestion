<?php

namespace App\Http\Controllers;

use App\Models\FormatoCotizacion;
use App\Models\Cotizacion;
use App\Models\CostoPrenda;
use Illuminate\Http\Request;

class FormatoCotizacionController extends Controller
{
    /**
     * Obtener todos los formatos
     */
    public function index()
    {
        $formatos = FormatoCotizacion::with('cotizacion')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($formatos);
    }

    /**
     * Guardar un nuevo formato
     */
    public function guardar(Request $request)
    {
        $validated = $request->validate([
            'cotizacion_id' => 'required|exists:cotizaciones,id',
        ]);

        $cotizacion = Cotizacion::with('prendas')->findOrFail($validated['cotizacion_id']);
        
        // Recopilar costos de todas las prendas
        $costosPorPrenda = [];
        $costoTotal = 0;

        foreach ($cotizacion->prendas as $prenda) {
            $costos = CostoPrenda::where('prenda_cotizacion_id', $prenda->id)
                ->with('componente')
                ->get();

            $totalPrenda = $costos->sum('costo');
            $costoTotal += $totalPrenda;

            $costosPorPrenda[] = [
                'prenda_id' => $prenda->id,
                'prenda_descripcion' => $prenda->descripcion,
                'imagen_url' => $prenda->imagen_url,
                'costos' => $costos->map(function ($costo) {
                    return [
                        'id' => $costo->id,
                        'componente' => $costo->componente->nombre,
                        'costo' => $costo->costo
                    ];
                })->toArray(),
                'total' => $totalPrenda
            ];
        }

        $formato = FormatoCotizacion::create([
            'cotizacion_id' => $validated['cotizacion_id'],
            'costos_por_prenda' => $costosPorPrenda,
            'costo_total' => $costoTotal,
            'estado' => 'guardado'
        ]);

        return response()->json($formato);
    }

    /**
     * Obtener un formato específico
     */
    public function show($id)
    {
        $formato = FormatoCotizacion::with('cotizacion.prendas')->findOrFail($id);
        return response()->json($formato);
    }

    /**
     * Eliminar un formato
     */
    public function destroy($id)
    {
        $formato = FormatoCotizacion::findOrFail($id);
        $formato->delete();

        return response()->json(['success' => true]);
    }
}
