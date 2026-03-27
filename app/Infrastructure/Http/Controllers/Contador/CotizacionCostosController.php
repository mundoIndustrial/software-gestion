<?php

namespace App\Infrastructure\Http\Controllers\Contador;

use App\Helpers\DescripcionPrendaHelper;
use App\Http\Controllers\Controller;
use App\Models\CostoPrenda;
use App\Models\Cotizacion;
use Illuminate\Http\JsonResponse;

final class CotizacionCostosController extends Controller
{
    public function show(int $cotizacion): JsonResponse
    {
        try {
            $cotizacionModelo = Cotizacion::with(['prendas.fotos', 'prendas.telaFotos'])->findOrFail($cotizacion);

            $costosCotizacion = CostoPrenda::with('prenda.fotos', 'prenda.telaFotos')
                ->where('cotizacion_id', $cotizacion)
                ->get();

            if ($costosCotizacion->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'prendas' => [],
                ]);
            }

            $cotizacionProductos = [];
            if ($cotizacionModelo->productos) {
                $cotizacionProductos = is_string($cotizacionModelo->productos)
                    ? json_decode($cotizacionModelo->productos, true)
                    : $cotizacionModelo->productos;
            }

            $prendas = [];
            foreach ($costosCotizacion as $costoPrenda) {
                $items = [];
                $costoTotal = $costoPrenda->total_costo ?? 0;

                if ($costoPrenda->items) {
                    $itemsArray = is_string($costoPrenda->items)
                        ? json_decode($costoPrenda->items, true)
                        : $costoPrenda->items;
                    if (is_array($itemsArray)) {
                        $items = $itemsArray;
                    }
                }

                $prenda = $costoPrenda->prenda;
                if (!$prenda) {
                    $nombreFinal = $costoPrenda->nombre_prenda;
                    if (empty($nombreFinal)) {
                        $descripcion = $costoPrenda->descripcion ?? '';
                        if (!empty($descripcion)) {
                            $palabras = explode(' ', trim($descripcion));
                            $nombreFinal = $palabras[0];
                        } else {
                            $nombreFinal = 'Prenda ' . (count($prendas) + 1);
                        }
                    }

                    $prendas[] = [
                        'id' => null,
                        'nombre_producto' => $nombreFinal,
                        'descripcion' => $costoPrenda->descripcion ?? '',
                        'color' => '',
                        'tela' => '',
                        'tela_referencia' => '',
                        'manga_nombre' => '',
                        'costo_total' => $costoTotal,
                        'items' => $items,
                        'fotos' => [],
                    ];
                    continue;
                }

                $productoIndex = $cotizacionModelo->prendas->pluck('id')->search($prenda->id) ?? 0;
                $color = '';
                $tela = '';
                $tela_referencia = '';
                $manga_nombre = '';
                $descripcion = $costoPrenda->descripcion ?? '';

                if (!empty($cotizacionProductos) && isset($cotizacionProductos[$productoIndex])) {
                    $producto = $cotizacionProductos[$productoIndex];
                    $variantes = $producto['variantes'] ?? [];

                    $color = $variantes['color'] ?? '';
                    $tela = $variantes['tela'] ?? '';
                    $tela_referencia = $variantes['tela_referencia'] ?? '';
                    $manga_nombre = $variantes['manga_nombre'] ?? '';

                    $datosFormato = [
                        'numero' => 1,
                        'tipo' => $prenda->nombre_producto ?? '',
                        'color' => $color,
                        'tela' => $tela,
                        'ref' => $tela_referencia,
                        'manga' => $manga_nombre,
                        'obs_manga' => '',
                        'logo' => '',
                        'bolsillos' => [],
                        'broche' => '',
                        'reflectivos' => [],
                        'otros' => [],
                        'tallas' => [],
                    ];

                    if ($prenda->descripcion) {
                        $desc = $prenda->descripcion;

                        if (preg_match('/Logo:\s*(.+?)(?:Bolsillos?:|Otros:|$)/is', $desc, $matches)) {
                            $logoText = trim($matches[1]);
                            $logoText = preg_replace('/^(SI|NO)\s*-\s*/i', '', $logoText);
                            if ($logoText) {
                                $datosFormato['logo'] = trim($logoText);
                            }
                        }

                        if (preg_match('/Bolsillos?:\s*(.+?)(?:Otros:|Broche:|$)/is', $desc, $matches)) {
                            $bolsillosText = trim($matches[1]);
                            $datosFormato['bolsillos'] = array_filter(array_map('trim', preg_split('/(?:\x{2022}|-|\R)+/u', $bolsillosText)));
                        }

                        if (preg_match('/Broche:\s*(.+?)(?:Otros:|Bolsillos?:|$)/is', $desc, $matches)) {
                            $brocheText = trim($matches[1]);
                            $brocheText = str_replace('|', '', $brocheText);
                            $datosFormato['broche'] = trim($brocheText);
                        }

                        if (preg_match('/Otros\s+detalles?:\s*(.+?)(?:Bolsillos?:|Broche:|$)/is', $desc, $matches)) {
                            $otrosText = trim($matches[1]);
                            $datosFormato['otros'] = array_filter(array_map('trim', preg_split('/(?:\x{2022}|-|\R)+/u', $otrosText)));
                        }
                    }

                    $descripcion = DescripcionPrendaHelper::generarDescripcion($datosFormato);
                }

                $fotos = [];
                if ($prenda->fotos && $prenda->fotos->count() > 0) {
                    $fotos = $prenda->fotos
                        ->filter(function ($foto) {
                            $ruta = $foto->ruta_webp ?? $foto->ruta_original ?? '';
                            return !str_contains(strtolower($ruta), 'logo');
                        })
                        ->map(function ($foto) {
                            return $foto->url;
                        })
                        ->values()
                        ->toArray();
                }

                $telaFotos = [];
                if ($prenda->telaFotos && $prenda->telaFotos->count() > 0) {
                    $telaFotos = $prenda->telaFotos->map(function ($foto) {
                        return $foto->url;
                    })->toArray();
                }

                $prendas[] = [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto ?: $costoPrenda->nombre_prenda,
                    'descripcion' => $descripcion,
                    'color' => $color,
                    'tela' => $tela,
                    'tela_referencia' => $tela_referencia,
                    'manga_nombre' => $manga_nombre,
                    'costo_total' => $costoTotal,
                    'items' => $items,
                    'fotos' => $fotos,
                    'tela_fotos' => $telaFotos,
                ];
            }

            return response()->json([
                'success' => true,
                'prendas' => $prendas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener costos: ' . $e->getMessage(),
            ], 500);
        }
    }
}
