<?php

namespace App\Http\Controllers;

use App\Models\ComponentePrenda;
use App\Models\CostoPrenda;
use App\Models\PrendaCotizacion;
use Illuminate\Http\Request;

class CostoPrendaController extends Controller
{
    /**
     * Obtener componentes disponibles
     */
    public function getComponentes()
    {
        return response()->json(ComponentePrenda::all());
    }

    /**
     * Obtener costos de una prenda
     */
    public function getCostos($prendaId)
    {
        $costos = CostoPrenda::where('prenda_cotizacion_id', $prendaId)
            ->with('componente')
            ->get();

        $total = $costos->sum('costo');

        return response()->json([
            'costos' => $costos,
            'total' => $total
        ]);
    }

    /**
     * Guardar o actualizar costo
     */
    public function guardarCosto(Request $request)
    {
        $validated = $request->validate([
            'prenda_cotizacion_id' => 'required|exists:prendas_cotizacion,id',
            'componente_prenda_id' => 'required|exists:componentes_prenda,id',
            'costo' => 'required|numeric|min:0'
        ]);

        $costo = CostoPrenda::updateOrCreate(
            [
                'prenda_cotizacion_id' => $validated['prenda_cotizacion_id'],
                'componente_prenda_id' => $validated['componente_prenda_id']
            ],
            ['costo' => $validated['costo']]
        );

        return response()->json($costo);
    }

    /**
     * Eliminar costo
     */
    public function eliminarCosto($costoId)
    {
        $costo = CostoPrenda::findOrFail($costoId);
        $costo->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Crear nuevo componente
     */
    public function crearComponente(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|unique:componentes_prenda,nombre',
            'descripcion' => 'nullable|string'
        ]);

        $componente = ComponentePrenda::create($validated);

        return response()->json($componente);
    }
}
