<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Http\Controllers\Controller;
use App\Models\CostoPrenda;
use Illuminate\Http\Request;

final class CostoPrendaController extends Controller
{
    public function guardar(Request $request)
    {
        try {
            $cotizacionId = $request->input('cotizacion_id');
            $costos = $request->input('costos', []);

            if (!$cotizacionId || empty($costos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos incompletos',
                ], 400);
            }

            $prendasCotizacion = \App\Models\PrendaCot::where('cotizacion_id', $cotizacionId)->get();

            foreach ($costos as $prendaKey => $costoPrenda) {
                if (isset($costoPrenda['items']) && !empty($costoPrenda['items'])) {
                    $total = 0;
                    foreach ($costoPrenda['items'] as $item) {
                        $total += floatval($item['precio'] ?? 0);
                    }

                    $prendaCotId = null;
                    if (isset($costoPrenda['prenda_cot_id']) && $costoPrenda['prenda_cot_id']) {
                        $prendaCotId = intval($costoPrenda['prenda_cot_id']);
                    } elseif (is_numeric($prendaKey)) {
                        $prendaCotId = intval($prendaKey);
                    }

                    $prendaCot = null;
                    if ($prendaCotId) {
                        $prendaCot = $prendasCotizacion->firstWhere('id', $prendaCotId);
                    }
                    if (!$prendaCot && is_numeric($prendaKey)) {
                        $prendaCot = $prendasCotizacion->get(intval($prendaKey));
                    }

                    if ($prendaCot) {
                        $costoPrendaExistente = CostoPrenda::where('prenda_cot_id', $prendaCot->id)->first();

                        if ($costoPrendaExistente) {
                            $costoPrendaExistente->update([
                                'descripcion' => $costoPrenda['descripcion'] ?? null,
                                'items' => $costoPrenda['items'],
                                'total_costo' => $total,
                            ]);
                        } else {
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
                'message' => 'Costos guardados correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los costos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function obtener(int $cotizacion_id)
    {
        try {
            $costos = CostoPrenda::where('cotizacion_id', $cotizacion_id)->get();

            return response()->json([
                'success' => true,
                'costos' => $costos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los costos: ' . $e->getMessage(),
            ], 500);
        }
    }
}

