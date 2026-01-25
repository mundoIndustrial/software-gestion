<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;

/**
 * PedidosProduccionViewController
 * 
 * Controlador para servir VISTAS HTML de pedidos (NO creación)
 * El controlador de CREACIÓN es: CrearPedidoEditableController
 * 
 * Responsabilidad: Renderizar vistas y obtener datos para templates
 * 
 * NOTA: Los métodos de creación fueron ELIMINADOS completamente
 * La creación de pedidos se realiza ÚNICAMENTE a través de:
 * POST /asesores/pedidos-editable/crear (CrearPedidoEditableController)
 */
class PedidosProduccionViewController
{

    /**
     * Obtener datos de cotización (AJAX)
     */
    public function obtenerDatosCotizacion($cotizacionId)
    {
        try {
            // Obtener cotización con sus relaciones
            $cotizacion = Cotizacion::with([
                'tipoCotizacion:id,nombre',
                'prendas:id,cotizacion_id,prenda_id,cantidad',
                'prendas.prenda:id,nombre',
                'reflectivo:id,cotizacion_id,tipo_reflectivo,cantidad',
                'logoCotizacion:id,cotizacion_id,tipo_logo'
            ])->find($cotizacionId);

            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }

            // Formatear datos para el frontend
            $prendas = $cotizacion->prendas->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->prenda?->nombre ?? 'Prenda',
                    'cantidad' => $prenda->cantidad,
                    'tipo' => 'prenda'
                ];
            })->toArray();

            $reflectivo = null;
            if ($cotizacion->reflectivo) {
                $reflectivo = [
                    'id' => $cotizacion->reflectivo->id,
                    'tipo' => $cotizacion->reflectivo->tipo_reflectivo,
                    'cantidad' => $cotizacion->reflectivo->cantidad,
                    'tipo' => 'reflectivo'
                ];
            }

            $logo = null;
            if ($cotizacion->logoCotizacion) {
                $logo = [
                    'id' => $cotizacion->logoCotizacion->id,
                    'tipo' => $cotizacion->logoCotizacion->tipo_logo,
                    'tipo' => 'logo'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo_cotizacion' => $cotizacion->tipoCotizacion?->nombre ?? 'Desconocido',
                    'prendas' => $prendas,
                    'reflectivo' => $reflectivo,
                    'logo' => $logo,
                    'tiene_prendas' => count($prendas) > 0,
                    'tiene_reflectivo' => $reflectivo !== null,
                    'tiene_logo' => $logo !== null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar plantilla de pedido
     */
    public function plantilla($id)
    {
        return view('asesores.pedidos.show', [
            'pedido_id' => $id
        ]);
    }

    /**
     * Obtener datos del pedido para edición modal
     * GET /asesores/pedidos-produccion/{id}/datos-edicion
     */
    public function obtenerDatosEdicion($pedidoId)
    {
        try {
            // Usar el mismo servicio que invoice-from-list usa
            $service = app(\App\Application\Services\Asesores\ObtenerDatosFacturaService::class);
            $datos = $service->obtener($pedidoId);
            
            \Log::info('[DATOS-EDICION] Datos cargados', ['pedido_id' => $pedidoId, 'prendas' => count($datos['prendas'] ?? [])]);

            // Retornar en formato que la modal espera
            return response()->json([
                'success' => true,
                'datos' => $datos
            ]);
        } catch (\Exception $e) {
            \Log::error('[DATOS-EDICION] Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos del pedido'
            ], 500);
        }
    }

    /**
     * Obtener datos de UNA prenda específica para edición
     * Usa ÚNICAMENTE las 7 tablas transaccionales del pedido
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     */
    public function obtenerDatosUnaPrenda($pedidoId, $prendaId)
    {
        try {
            \Log::info('[PRENDA-DATOS] Cargando datos de prenda para edición', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            // ============================================
            // 1. OBTENER PRENDA BASE
            // ============================================
            $prenda = \DB::table('prendas_pedido')
                ->where('id', $prendaId)
                ->where('pedido_produccion_id', $pedidoId)
                ->where('deleted_at', null)
                ->first();
            
            if (!$prenda) {
                \Log::warning('[PRENDA-DATOS] Prenda no encontrada', [
                    'prenda_id' => $prendaId,
                    'pedido_id' => $pedidoId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada'
                ], 404);
            }

            // ============================================
            // 2. IMÁGENES DE LA PRENDA (prenda_fotos_pedido)
            // ============================================
            $imagenesPrenda = [];
            try {
                $fotosGuardadas = \DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $prendaId)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('ruta_webp')
                    ->get();
                
                $imagenesPrenda = $fotosGuardadas->map(function($foto) {
                    $ruta = str_replace('\\', '/', $foto->ruta_webp);
                    if (strpos($ruta, '/storage/') === 0) {
                        return $ruta;
                    }
                    if (strpos($ruta, 'storage/') === 0) {
                        return '/' . $ruta;
                    }
                    if (strpos($ruta, '/') !== 0) {
                        return '/storage/' . $ruta;
                    }
                    return $ruta;
                })->toArray();
                
                \Log::info('[PRENDA-DATOS] Imágenes de prenda encontradas', [
                    'prenda_id' => $prendaId,
                    'cantidad' => count($imagenesPrenda)
                ]);
            } catch (\Exception $e) {
                \Log::debug('[PRENDA-DATOS] Error en prenda_fotos_pedido: ' . $e->getMessage());
            }

            // ============================================
            // 3. TELAS Y COLORES (prenda_pedido_colores_telas)
            // ============================================
            $telasAgregadas = [];
            try {
                $colorTelaRecords = \DB::table('prenda_pedido_colores_telas')
                    ->where('prenda_pedido_id', $prendaId)
                    ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                    ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                    ->select(
                        'prenda_pedido_colores_telas.id as color_tela_id',
                        'colores_prenda.nombre as color_nombre',
                        'telas_prenda.nombre as tela_nombre',
                        'telas_prenda.referencia'
                    )
                    ->get();
                
                foreach ($colorTelaRecords as $colorTela) {
                    // Obtener imágenes de esta combinación tela+color (prenda_fotos_tela_pedido)
                    $fotosTelaDB = \DB::table('prenda_fotos_tela_pedido')
                        ->where('prenda_pedido_colores_telas_id', $colorTela->color_tela_id)
                        ->where('deleted_at', null)
                        ->orderBy('orden')
                        ->select('ruta_webp', 'ruta_original')
                        ->get();
                    
                    $imagenesTelaFormato = $fotosTelaDB->map(function($foto) {
                        $ruta = str_replace('\\', '/', $foto->ruta_webp ?? $foto->ruta_original);
                        if (strpos($ruta, '/storage/') === 0) {
                            return $ruta;
                        }
                        if (strpos($ruta, 'storage/') === 0) {
                            return '/' . $ruta;
                        }
                        if (strpos($ruta, '/') !== 0) {
                            return '/storage/' . $ruta;
                        }
                        return $ruta;
                    })->toArray();
                    
                    $telasAgregadas[] = [
                        'tela' => $colorTela->tela_nombre,
                        'color' => $colorTela->color_nombre,
                        'referencia' => $colorTela->referencia ?? '',
                        'imagenes' => $imagenesTelaFormato
                    ];
                }
                
                \Log::info('[PRENDA-DATOS] Telas encontradas', [
                    'prenda_id' => $prendaId,
                    'cantidad' => count($telasAgregadas)
                ]);
            } catch (\Exception $e) {
                \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_colores_telas: ' . $e->getMessage());
            }

            // ============================================
            // 4. VARIANTES DE LA PRENDA (prenda_pedido_variantes)
            // ============================================
            $variantes_formateadas = [];
            try {
                $variantes = \DB::table('prenda_pedido_variantes')
                    ->where('prenda_pedido_id', $prendaId)
                    ->leftJoin('tipos_manga', 'prenda_pedido_variantes.tipo_manga_id', '=', 'tipos_manga.id')
                    ->leftJoin('tipos_broche_boton', 'prenda_pedido_variantes.tipo_broche_boton_id', '=', 'tipos_broche_boton.id')
                    ->select(
                        'tipos_manga.nombre as manga_nombre',
                        'tipos_broche_boton.nombre as broche_nombre',
                        'prenda_pedido_variantes.manga_obs',
                        'prenda_pedido_variantes.bolsillos_obs',
                        'prenda_pedido_variantes.broche_boton_obs',
                        'prenda_pedido_variantes.tiene_bolsillos'
                    )
                    ->get();
                
                foreach ($variantes as $variante) {
                    $variantes_formateadas[] = [
                        'manga' => $variante->manga_nombre ?? '',
                        'obs_manga' => $variante->manga_obs ?? '',
                        'tiene_bolsillos' => (bool)$variante->tiene_bolsillos,
                        'obs_bolsillos' => $variante->bolsillos_obs ?? '',
                        'broche' => $variante->broche_nombre ?? '',
                        'obs_broche' => $variante->broche_boton_obs ?? ''
                    ];
                }
                
                \Log::info('[PRENDA-DATOS] Variantes encontradas', [
                    'prenda_id' => $prendaId,
                    'cantidad' => count($variantes_formateadas)
                ]);
            } catch (\Exception $e) {
                \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_variantes: ' . $e->getMessage());
            }

            // ============================================
            // 5. PROCESOS DE LA PRENDA (pedidos_procesos_prenda_detalles)
            // ============================================
            $procesos = [];
            try {
                $procesosDB = \DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $prendaId)
                    ->where('deleted_at', null)
                    ->join('tipos_procesos', 'pedidos_procesos_prenda_detalles.tipo_proceso_id', '=', 'tipos_procesos.id')
                    ->select(
                        'pedidos_procesos_prenda_detalles.id as proceso_id',
                        'tipos_procesos.id as tipo_id',
                        'tipos_procesos.nombre as tipo_nombre',
                        'pedidos_procesos_prenda_detalles.ubicaciones',
                        'pedidos_procesos_prenda_detalles.observaciones',
                        'pedidos_procesos_prenda_detalles.tallas_dama',
                        'pedidos_procesos_prenda_detalles.tallas_caballero',
                        'pedidos_procesos_prenda_detalles.estado',
                        'pedidos_procesos_prenda_detalles.datos_adicionales'
                    )
                    ->get();
                
                foreach ($procesosDB as $procesoRow) {
                    // Obtener imágenes del proceso (pedidos_procesos_imagenes)
                    $imagenesProc = \DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $procesoRow->proceso_id)
                        ->where('deleted_at', null)
                        ->orderBy('orden')
                        ->select('ruta_webp', 'ruta_original', 'es_principal')
                        ->get();
                    
                    $imagenesFormato = $imagenesProc->map(function($img) {
                        $ruta = str_replace('\\', '/', $img->ruta_webp ?? $img->ruta_original);
                        if (strpos($ruta, '/storage/') === 0) {
                            return $ruta;
                        }
                        if (strpos($ruta, 'storage/') === 0) {
                            return '/' . $ruta;
                        }
                        if (strpos($ruta, '/') !== 0) {
                            return '/storage/' . $ruta;
                        }
                        return $ruta;
                    })->toArray();
                    
                    // Parsear JSON fields
                    $ubicaciones = [];
                    if ($procesoRow->ubicaciones) {
                        $ubicaciones = is_array($procesoRow->ubicaciones) 
                            ? $procesoRow->ubicaciones 
                            : json_decode($procesoRow->ubicaciones, true) ?? [];
                    }
                    
                    // Leer tallas DESDE LA TABLA RELACIONAL
                    $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
                        'proceso_prenda_detalle_id',
                        $procesoRow->proceso_id
                    )->get();
                    
                    $tallas_dama = [];
                    $tallas_caballero = [];
                    $tallas_unisex = [];
                    
                    foreach ($tallasRelacionales as $tallaRec) {
                        $genero = strtolower($tallaRec->genero);
                        if ($tallaRec->cantidad > 0) {
                            if ($genero === 'dama') {
                                $tallas_dama[$tallaRec->talla] = $tallaRec->cantidad;
                            } elseif ($genero === 'caballero') {
                                $tallas_caballero[$tallaRec->talla] = $tallaRec->cantidad;
                            } elseif ($genero === 'unisex') {
                                $tallas_unisex[$tallaRec->talla] = $tallaRec->cantidad;
                            }
                        }
                    }
                    
                    $datos_adicionales = [];
                    if ($procesoRow->datos_adicionales) {
                        $datos_adicionales = is_array($procesoRow->datos_adicionales) 
                            ? $procesoRow->datos_adicionales 
                            : json_decode($procesoRow->datos_adicionales, true) ?? [];
                    }
                    
                    $procesos[] = [
                        'id' => $procesoRow->proceso_id,
                        'tipo_id' => $procesoRow->tipo_id,
                        'tipo_nombre' => $procesoRow->tipo_nombre,
                        'ubicaciones' => $ubicaciones,
                        'observaciones' => $procesoRow->observaciones ?? '',
                        'tallas_dama' => $tallas_dama,
                        'tallas_caballero' => $tallas_caballero,
                        'estado' => $procesoRow->estado ?? 'PENDIENTE',
                        'imagenes' => $imagenesFormato,
                        'datos_adicionales' => $datos_adicionales
                    ];
                }
                
                \Log::info('[PRENDA-DATOS] Procesos encontrados', [
                    'prenda_id' => $prendaId,
                    'cantidad' => count($procesos)
                ]);
            } catch (\Exception $e) {
                \Log::debug('[PRENDA-DATOS] Error en pedidos_procesos_prenda_detalles: ' . $e->getMessage());
            }

            // ============================================
            // 6. TALLAS (prenda_pedido_tallas - tabla relacional)
            // ============================================
            $tallas = [];
            try {
                $tallasDB = \DB::table('prenda_pedido_tallas')
                    ->where('prenda_pedido_id', $prendaId)
                    ->select('genero', 'talla', 'cantidad')
                    ->get();
                
                foreach ($tallasDB as $tallaRow) {
                    $genero = $tallaRow->genero;
                    if (!isset($tallas[$genero])) {
                        $tallas[$genero] = [];
                    }
                    $tallas[$genero][$tallaRow->talla] = $tallaRow->cantidad;
                }
                
                \Log::info('[PRENDA-DATOS] Tallas encontradas', [
                    'prenda_id' => $prendaId,
                    'cantidad' => $tallasDB->count()
                ]);
            } catch (\Exception $e) {
                \Log::debug('[PRENDA-DATOS] Error en prenda_pedido_tallas: ' . $e->getMessage());
            }

            // ============================================
            // 7. EXTRAER GÉNEROS DESDE TALLAS RELACIONALES
            // ============================================
            // El campo genero fue eliminado de prendas_pedido.
            // Los géneros se obtienen de la tabla prenda_pedido_tallas
            $generos = array_keys($tallas);  // Extraer géneros desde tallas que ya están agrupadas

            // ============================================
            // 8. CONSTRUIR RESPUESTA FINAL
            // ============================================
            $datos = [
                'id' => $prenda->id,
                'prenda_pedido_id' => $prenda->id,
                'nombre_prenda' => $prenda->nombre_prenda,
                'nombre' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion ?? '',
                'origen' => $prenda->de_bodega ? 'bodega' : 'cliente',
                'de_bodega' => (bool)$prenda->de_bodega,
                'imagenes' => $imagenesPrenda,
                'telasAgregadas' => $telasAgregadas,
                'tallas' => $tallas,
                'generos' => $generos,
                'variantes' => $variantes_formateadas,
                'procesos' => $procesos
            ];

            \Log::info('[PRENDA-DATOS] Datos compilados exitosamente', [
                'prenda_id' => $prendaId,
                'imagenes_count' => count($imagenesPrenda),
                'telas_count' => count($telasAgregadas),
                'procesos_count' => count($procesos),
                'variantes_count' => count($variantes_formateadas)
            ]);

            return response()->json([
                'success' => true,
                'prenda' => $datos
            ]);
        } catch (\Exception $e) {
            \Log::error('[PRENDA-DATOS] Error obteniendo datos de prenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de la prenda',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
