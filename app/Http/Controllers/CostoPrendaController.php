<?php

namespace App\Http\Controllers;

use App\Models\CostoPrenda;
use Illuminate\Http\Request;

class CostoPrendaController extends Controller
{
    /**
     * Guarda los costos de las prendas de una cotización
     */
    public function guardar(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            $costos = $request->input('costos', []);

            if (!$cotizacionId || empty($costos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ], 400);
            }

            // Obtener las prendas de la cotización para mapear índices a IDs
            $prendasCotizacion = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)->get();

            // Guardar los nuevos costos (actualizar o crear)
            foreach ($costos as $prendaIndex => $costoPrenda) {
                if (isset($costoPrenda['items']) && !empty($costoPrenda['items'])) {
                    // Calcular total
                    $total = 0;
                    foreach ($costoPrenda['items'] as $item) {
                        $total += floatval($item['precio'] ?? 0);
                    }

                    // Obtener la prenda correspondiente por índice
                    $prendaCot = $prendasCotizacion->get($prendaIndex);
                    
                    if ($prendaCot) {
                        // Buscar si ya existe un costo para esta prenda
                        $costoPrendaExistente = CostoPrenda::where('prenda_cot_id', $prendaCot->id)->first();

                        if ($costoPrendaExistente) {
                            // Actualizar costo existente
                            $costoPrendaExistente->update([
                                'descripcion' => $costoPrenda['descripcion'] ?? null,
                                'items' => $costoPrenda['items'],
                                'total_costo' => $total,
                            ]);
                        } else {
                            // Crear nuevo costo
                            CostoPrenda::create([
                                'cotizacion_id' => $cotizacionId,
                                'prenda_cot_id' => $prendaCot->id,
                                'nombre_prenda' => $costoPrenda['nombre'] ?? $prendaCot->nombre_producto,
                                'descripcion' => $costoPrenda['descripcion'] ?? null,
                                'items' => $costoPrenda['items'],
                                'total_costo' => $total,
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => '✓ Costos guardados correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los costos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los costos de una cotización
     */
    public function obtener($cotizacionId)
    {
        try {
            $costos = CostoPrenda::where('cotizacion_id', $cotizacionId)->get();

            return response()->json([
                'success' => true,
                'costos' => $costos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los costos: ' . $e->getMessage()
            ], 500);
        }
    }
}
