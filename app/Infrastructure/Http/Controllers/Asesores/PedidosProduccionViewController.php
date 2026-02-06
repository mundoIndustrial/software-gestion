<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\GeneroPrenda;
use App\Models\PrendaTallaCot;
use App\Models\LogoCotizacionTecnicaPrendaFoto;

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
            // Logging para diagnóstico
            \Log::info('[obtenerDatosCotizacion] Iniciando carga', [
                'cotizacion_id' => $cotizacionId,
                'usuario_id' => Auth::id(),
                'timestamp' => now()
            ]);
            
            // Validar que el usuario tenga acceso a esta cotización
            $cotizacion = Cotizacion::where('id', $cotizacionId)
                ->where('asesor_id', Auth::id())
                ->first();
                
            if (!$cotizacion) {
                \Log::warning('[obtenerDatosCotizacion] Cotización no encontrada o sin permisos', [
                    'cotizacion_id' => $cotizacionId,
                    'usuario_id' => Auth::id(),
                    'razon' => 'No existe o no pertenece al usuario'
                ]);
                
                return response()->json([
                    'error' => 'Cotización no encontrada o no tienes permisos para acceder a ella'
                ], 404);
            }
            
            \Log::info('[obtenerDatosCotizacion] Cotización encontrada', [
                'cotizacion_id' => $cotizacion->id,
                'cliente' => $cotizacion->cliente,
                'estado' => $cotizacion->estado,
                'prendas_count' => $cotizacion->prendas ? $cotizacion->prendas->count() : 0
            ]);
            
            \Log::info('[obtenerDatosCotizacion] Iniciando carga con relaciones completas...');
            
            // Obtener cotización con sus relaciones COMPLETAS
            $cotizacionConRelaciones = Cotizacion::with([
                'tipoCotizacion:id,nombre',
                'prendas' => function($query) {
                    $query->with([
                        'telas' => function($q) {
                            $q->with(['color:id,nombre', 'tela:id,nombre']);
                        },
                        'fotos:id,prenda_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                        'telaFotos:id,prenda_tela_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                        'tallas:id,prenda_cot_id,talla,cantidad',
                        'variantes:id,prenda_cot_id,tipo_prenda,es_jean_pantalon,tipo_jean_pantalon,genero_id,color,tipo_manga_id,tipo_broche_id,obs_broche,tiene_bolsillos,obs_bolsillos,aplica_manga,tipo_manga,obs_manga,aplica_broche,tiene_reflectivo,obs_reflectivo,descripcion_adicional,telas_multiples',
                        'variantes.manga:id,nombre',
                        'variantes.broche:id,nombre',
                        'variantes.genero:id,nombre'
                    ]);
                },
                'reflectivo',
                'logoCotizacion'
            ])->find($cotizacionId);

            if (!$cotizacionConRelaciones) {
                \Log::error('[obtenerDatosCotizacion] Error al cargar cotización con relaciones', [
                    'cotizacion_id' => $cotizacionId
                ]);
                
                return response()->json([
                    'error' => 'Error al cargar datos completos de la cotización'
                ], 500);
            }
            
            \Log::info('[obtenerDatosCotizacion] Relaciones cargadas exitosamente', [
                'cotizacion_id' => $cotizacionId,
                'prendas_count' => $cotizacionConRelaciones->prendas ? $cotizacionConRelaciones->prendas->count() : 0
            ]);

            // Formatear datos COMPLETOS para el frontend
            $prendas = $cotizacionConRelaciones->prendas->map(function($prenda) {
                // Telas con fotos
                $telas = [];
                if ($prenda->telas) {
                    $telas = $prenda->telas->map(function($tela) use ($prenda) {
                        // Obtener fotos de esta tela desde telaFotos
                        $fotosTela = [];
                        if ($prenda->telaFotos) {
                            $fotosTela = $prenda->telaFotos
                                ->where('prenda_tela_cot_id', $tela->id)
                                ->get()
                                ->map(function($foto) {
                                    $ruta = $foto->ruta_webp;
                                    // Agregar prefijo /storage/ si no lo tiene
                                    if ($ruta && !\Illuminate\Support\Str::startsWith($ruta, '/')) {
                                        $ruta = '/storage/' . $ruta;
                                    }
                                    return $ruta;
                                })
                                ->toArray();
                        }
                        
                        return [
                            'id' => $tela->id,
                            'color' => $tela->color ? [
                                'id' => $tela->color->id,
                                'nombre' => $tela->color->nombre
                            ] : null,
                            'tela' => $tela->tela ? [
                                'id' => $tela->tela->id,
                                'nombre' => $tela->tela->nombre
                            ] : null,
                            'referencia' => $tela->referencia ?? '',
                            'fotos' => $fotosTela
                        ];
                    })->toArray();
                }

                // Fotos de la prenda (principal)
                $fotos = [];
                if ($prenda->fotos) {
                    $fotos = $prenda->fotos->map(function($foto) {
                        $ruta = $foto->ruta_webp;
                        // Agregar prefijo /storage/ si no lo tiene
                        if ($ruta && !\Illuminate\Support\Str::startsWith($ruta, '/')) {
                            $ruta = '/storage/' . $ruta;
                        }
                        return $ruta;
                    })->toArray();
                }

                // Variantes (especificaciones)
                $variantes = $prenda->variantes ? $prenda->variantes->map(function($var) {
                    return [
                        'id' => $var->id,
                        'tipo_manga_id' => $var->tipo_manga_id,
                        'tipo_manga_nombre' => $var->manga ? $var->manga->nombre : null,
                        'tipo_broche_id' => $var->tipo_broche_id,
                        'tipo_broche_nombre' => $var->broche ? $var->broche->nombre : null,
                        'tiene_bolsillos' => $var->tiene_bolsillos ?? false,
                        'aplica_manga' => $var->aplica_manga ?? false,
                        'aplica_broche' => $var->aplica_broche ?? false,
                        'tiene_reflectivo' => $var->tiene_reflectivo ?? false,
                        'obs_manga' => $var->obs_manga,
                        'obs_bolsillos' => $var->obs_bolsillos,
                        'obs_broche' => $var->obs_broche,
                        'obs_reflectivo' => $var->obs_reflectivo
                    ];
                })->toArray() : [];

                // Formatear tallas
                $tallasFormateadas = [];
                \Log::info('[obtenerDatosCotizacion] Revisando tallas de la prenda', [
                    'prenda_id' => $prenda->id,
                    'tiene_tallas_relation' => isset($prenda->tallas),
                    'tallas_count' => $prenda->tallas ? $prenda->tallas->count() : 0,
                    'tallas_data' => $prenda->tallas ? $prenda->tallas->toArray() : null
                ]);
                
                if ($prenda->tallas) {
                    $tallasFormateadas = $prenda->tallas->map(function($talla) {
                        \Log::info('[obtenerDatosCotizacion] Procesando talla', [
                            'talla_id' => $talla->id,
                            'talla_nombre' => $talla->talla,
                            'talla_cantidad' => $talla->cantidad
                        ]);
                        
                        return [
                            'id' => $talla->id,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad
                        ];
                    })->toArray();
                }

                // Obtener género desde la primera variante
                $genero = null;
                if ($prenda->variantes && $prenda->variantes->isNotEmpty()) {
                    $primeraVariante = $prenda->variantes->first();
                    if ($primeraVariante->genero) {
                        $genero = [
                            'id' => $primeraVariante->genero->id,
                            'nombre' => $primeraVariante->genero->nombre
                        ];
                    }
                }

                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $prenda->descripcion ?? '',
                    'cantidad' => $prenda->cantidad ?? 1,
                    'texto_personalizado_tallas' => $prenda->texto_personalizado_tallas,
                    'prenda_bodega' => $prenda->prenda_bodega ?? 0,
                    'telas' => $telas,
                    'fotos' => $fotos,
                    'variantes' => $variantes,
                    'tallas' => $tallasFormateadas,
                    'genero' => $genero,
                    'tipo' => 'prenda'
                ];
                
                \Log::info('[obtenerDatosCotizacion] Datos de prenda formateados', [
                    'prenda_id' => $prenda->id,
                    'tallas_count' => count($tallasFormateadas),
                    'tallas_data' => $tallasFormateadas,
                    'genero' => $genero
                ]);
            })->toArray();

            $reflectivo = null;
            if ($cotizacionConRelaciones->reflectivo) {
                $reflectivo = [
                    'id' => $cotizacionConRelaciones->reflectivo->id,
                    'tipo_reflectivo' => $cotizacionConRelaciones->reflectivo->tipo_reflectivo ?? 'N/A',
                    'cantidad' => $cotizacionConRelaciones->reflectivo->cantidad ?? 1,
                    'tipo' => 'reflectivo'
                ];
            }

            $logo = null;
            if ($cotizacionConRelaciones->logoCotizacion) {
                $logo = [
                    'id' => $cotizacionConRelaciones->logoCotizacion->id,
                    'tipo_logo' => $cotizacionConRelaciones->logoCotizacion->tipo_logo ?? 'N/A',
                    'tipo' => 'logo'
                ];
            }

            return response()->json([
                'error' => null,
                'prendas' => $prendas,
                'reflectivo' => $reflectivo,
                'logo' => $logo,
                'tiene_prendas' => count($prendas) > 0,
                'tiene_reflectivo' => $reflectivo !== null,
                'tiene_logo' => $logo !== null
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosCotizacion:', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener datos de cotización: ' . $e->getMessage(),
                'prendas' => [],
                'reflectivo' => null,
                'logo' => null
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
                        'prenda_pedido_colores_telas.referencia'
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

    /**
     * Obtener prenda completa desde cotización (para crear/editar pedido)
     * GET /asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}
     */
    public function obtenerPrendaCompleta($cotizacionId, $prendaId)
    {
        try {
            // Cargar cotización con todas las relaciones
            $cotizacion = Cotizacion::with([
                'tipoCotizacion',  //  Cargar el tipo de cotización para verificar si es Logo
                'prendas' => function($query) use ($prendaId) {
                    $query->where('id', $prendaId)
                        ->with([
                            'telas' => function($q) {
                                $q->with([
                                    'color:id,nombre,codigo',
                                    'tela:id,nombre,referencia,descripcion'
                                ]);
                            },
                            'fotos:id,prenda_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                            'telaFotos:id,prenda_cot_id,prenda_tela_cot_id,ruta_original,ruta_webp,ruta_miniatura',
                            'variantes' => function($q) {
                                $q->with([
                                    'manga:id,nombre',
                                    'broche:id,nombre',
                                    'genero:id,nombre'
                                ]);
                            },
                            'tallas' => function($q) {
                                $q->with([
                                    'genero:id,nombre'  // Cargar relación de género
                                ]);
                            },
                            'logoCotizacionesTecnicas' => function($q) {
                                $q->with([
                                    'tipoLogo:id,nombre',
                                    'fotos:id,logo_cotizacion_tecnica_prenda_id,ruta_original,ruta_webp,ruta_miniatura,orden,ancho,alto,tamaño,created_at,updated_at'
                                ]);
                            },
                            'logoCotizacionTelasPrenda' => function($q) {  //  Nueva relación
                                // Cargar todas las telas/colores/referencias para esta prenda en logo
                            }
                        ]);
                }
            ])->find($cotizacionId);

            if (!$cotizacion) {
                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            if (count($cotizacion->prendas) === 0) {
                return response()->json(['error' => 'Prenda no encontrada'], 404);
            }

            $prenda = $cotizacion->prendas[0];
            $procesosFormato = [];

            // 🔴 DEBUG: Ver estructura de prenda antes de procesar telas
            \Log::info('[OBTENER-PRENDA-COMPLETA] 🔍 PRENDA CARGADA:', [
                'prenda_id' => $prenda->id,
                'tiene_telas_relation' => !!$prenda->telas,
                'telas_count' => count($prenda->telas ?? []),
                'telas_data' => json_encode($prenda->telas),
                'tiene_telaFotos' => !!$prenda->telaFotos,
                'telaFotos_count' => count($prenda->telaFotos ?? [])
            ]);

            // PROCESAR TELAS
            $telasFormato = [];
            
            //  LÓGICA NUEVA: Si es cotización de tipo Logo, usar telas desde logo_cotizacion_telas_prenda
            $esLogoCotizacion = $cotizacion->tipoCotizacion && 
                                 (strtolower($cotizacion->tipoCotizacion->nombre) === 'logo' || 
                                  strtolower($cotizacion->tipoCotizacion->nombre) === 'bordado');
            
            if ($esLogoCotizacion && $prenda->logoCotizacionTelasPrenda && count($prenda->logoCotizacionTelasPrenda) > 0) {
                \Log::info('[OBTENER-PRENDA-COMPLETA] 🎯 USANDO TELAS DE LOGO_COTIZACION_TELAS_PRENDA', [
                    'prenda_id' => $prenda->id,
                    'telas_logo_count' => count($prenda->logoCotizacionTelasPrenda)
                ]);
                
                foreach ($prenda->logoCotizacionTelasPrenda as $telaLogo) {
                    $tela_data = [
                        'id' => $telaLogo->id,
                        'nombre_tela' => $telaLogo->tela ?? 'SIN NOMBRE',
                        'color' => $telaLogo->color ?? '',
                        'referencia' => $telaLogo->ref ?? '',
                        'descripcion' => '',
                        'imagenes' => []
                    ];

                    // Si hay imagen en logo_cotizacion_telas_prenda, usarla
                    if ($telaLogo->img) {
                        $ruta = $telaLogo->img;
                        // La ruta ya viene como /storage/... desde la base de datos
                        // No hacer cambios adicionales
                        $tela_data['imagenes'][] = [
                            'ruta' => $ruta,
                            'ruta_webp' => $ruta  // Usar la misma ruta si no hay WebP
                        ];
                    }

                    $telasFormato[] = $tela_data;
                }
            } else {
                // Usar lógica tradicional de telas de PrendaTelaCot
                \Log::info('[OBTENER-PRENDA-COMPLETA] 📋 USANDO TELAS TRADICIONALES DE PRENDA_TELA_COT', [
                    'prenda_id' => $prenda->id,
                    'es_logo' => $esLogoCotizacion,
                    'tiene_logo_telas' => !!($prenda->logoCotizacionTelasPrenda && count($prenda->logoCotizacionTelasPrenda) > 0)
                ]);
                
                \Log::info('[OBTENER-PRENDA-COMPLETA] 🔍 PROCESANDO TELAS:', [
                    'prenda_id' => $prenda->id,
                    'tiene_telas' => !!$prenda->telas,
                    'telas_count' => count($prenda->telas ?? []),
                    'telas_content' => $prenda->telas ? json_encode($prenda->telas->toArray()) : 'NULL'
                ]);
                
                if ($prenda->telas && count($prenda->telas) > 0) {
                    foreach ($prenda->telas as $tela) {
                        $tela_data = [
                            'id' => $tela->id,
                            'nombre_tela' => isset($tela->tela) ? $tela->tela->nombre : 'SIN NOMBRE',
                            'color' => isset($tela->color) ? $tela->color->nombre : '',
                            'referencia' => $tela->referencia ?? '',
                            'descripcion' => $tela->descripcion ?? '',
                            'imagenes' => []
                        ];

                        // Agregar imágenes de tela
                        if ($prenda->telaFotos && count($prenda->telaFotos) > 0) {
                            foreach ($prenda->telaFotos as $foto) {
                                if ($foto->prenda_tela_cot_id == $tela->id) {
                                    $ruta = $foto->ruta_original;
                                    $rutaWebp = $foto->ruta_webp;
                                    
                                    // Solo agregar /storage/ si no lo tiene ya
                                    if ($ruta && !str_starts_with($ruta, '/')) {
                                        $ruta = '/storage/' . $ruta;
                                    }
                                    if ($rutaWebp && !str_starts_with($rutaWebp, '/')) {
                                        $rutaWebp = '/storage/' . $rutaWebp;
                                    }
                                    
                                    $tela_data['imagenes'][] = [
                                        'ruta' => $ruta,
                                        'ruta_webp' => $rutaWebp
                                    ];
                                }
                            }
                        }

                        $telasFormato[] = $tela_data;
                    }
                }
            }

            // PROCESAR FOTOS DE PRENDA
            $fotosFormato = [];
            if ($prenda->fotos && count($prenda->fotos) > 0) {
                foreach ($prenda->fotos as $foto) {
                    $ruta = $foto->ruta_original;
                    $rutaWebp = $foto->ruta_webp;
                    
                    // Solo agregar /storage/ si no lo tiene ya
                    if ($ruta && !str_starts_with($ruta, '/')) {
                        $ruta = '/storage/' . $ruta;
                    }
                    if ($rutaWebp && !str_starts_with($rutaWebp, '/')) {
                        $rutaWebp = '/storage/' . $rutaWebp;
                    }
                    
                    $fotosFormato[] = [
                        'ruta' => $ruta,
                        'ruta_webp' => $rutaWebp
                    ];
                }
            }

            // PROCESAR TALLAS
            $tallasDisponibles = [];
            $tallasConCantidades = [];
            $generosPresentes = [];  // Detectar todos los géneros presentes en variantes
            
            // Extraer géneros desde variantes (pueden ser múltiples en JSON)
            if ($prenda->variantes && count($prenda->variantes) > 0) {
                foreach ($prenda->variantes as $variante) {
                    if ($variante->genero_id) {
                        // genero_id puede ser JSON array o un ID simple
                        $generosVariante = is_array($variante->genero_id) ? $variante->genero_id : [$variante->genero_id];
                        foreach ($generosVariante as $generoId) {
                            if (!in_array($generoId, $generosPresentes)) {
                                $generosPresentes[] = $generoId;
                            }
                        }
                    }
                }
            }
            
            \Log::info('[OBTENER-PRENDA-COMPLETA] 🧬 GÉNEROS DETECTADOS EN VARIANTES:', [
                'prenda_id' => $prenda->id,
                'generos_presentes' => $generosPresentes,
                'generos_count' => count($generosPresentes)
            ]);
            
            if ($prenda->tallas && count($prenda->tallas) > 0) {
                foreach ($prenda->tallas as $tallaCot) {
                    $tallasDisponibles[] = $tallaCot->talla;
                    $tallasConCantidades[] = [
                        'talla' => $tallaCot->talla,
                        'cantidad' => $tallaCot->cantidad
                    ];
                }
            }

            // PROCESAR VARIANTES
            $variantes = [];
            if ($prenda->variantes && count($prenda->variantes) > 0) {
                $var = $prenda->variantes[0];
                $variantes = [
                    'tipo_prenda' => $var->tipo_prenda ?? '',
                    'es_jean_pantalon' => (bool)($var->es_jean_pantalon ?? false),
                    'tipo_jean_pantalon' => $var->tipo_jean_pantalon ?? '',
                    'aplica_manga' => (bool)($var->aplica_manga ?? false),
                    'tipo_manga' => $var->manga ? $var->manga->nombre : ($var->tipo_manga ?? 'No aplica'),
                    'tipo_manga_id' => $var->tipo_manga_id,
                    'obs_manga' => $var->obs_manga ?? '',
                    'tiene_bolsillos' => (bool)($var->tiene_bolsillos ?? false),
                    'obs_bolsillos' => $var->obs_bolsillos ?? '',
                    'aplica_broche' => (bool)($var->aplica_broche ?? false),
                    'tipo_broche' => $var->broche ? $var->broche->nombre : ($var->tipo_broche ?? 'No aplica'),
                    'tipo_broche_id' => $var->tipo_broche_id,
                    'obs_broche' => $var->obs_broche ?? '',
                    'tiene_reflectivo' => (bool)($var->tiene_reflectivo ?? false),
                    'obs_reflectivo' => $var->obs_reflectivo ?? '',
                    'genero_id' => $var->genero_id ?? null,
                    'genero' => $var->genero ? $var->genero->nombre : 'UNISEX'
                ];
            }

            
            // PROCESAR TÉCNICAS DE LOGO (Bordado, Estampado, DTF, Sublimado, etc)
            // MODIFICADO: Procesar TODAS las técnicas, no solo la primera
            if ($prenda->logoCotizacionesTecnicas && count($prenda->logoCotizacionesTecnicas) > 0) {
                // Usar los datos ya cargados en la relación (sin hacer query adicional)
                foreach ($prenda->logoCotizacionesTecnicas as $logoTecnica) {
                    // DEBUG: Verificar que las fotos se cargaron correctamente
                    \Log::info('[OBTENER-PRENDA-COMPLETA] 🖼️ LogoTecnica fotos:', [
                        'logo_tecnica_id' => $logoTecnica->id,
                        'tiene_fotos' => !!$logoTecnica->fotos,
                        'fotos_count' => $logoTecnica->fotos ? count($logoTecnica->fotos) : 0,
                        'fotos_data' => $logoTecnica->fotos ? $logoTecnica->fotos->toArray() : 'NULL'
                    ]);
                    
                    // Obtener nombre técnico (Bordado, Estampado, DTF, Sublimado, etc)
                    $nombreTecnica = $logoTecnica->tipoLogo ? $logoTecnica->tipoLogo->nombre : 'Técnica desconocida';
                    // Generar slug desde el nombre (ej: "BORDADO" -> "bordado")
                    $slugTecnica = strtolower(str_replace(' ', '-', $nombreTecnica));
                    
                    // Procesar ubicaciones
                    $ubicacionesLogo = [];
                    $ubicacionesRaw = $logoTecnica->ubicaciones ?? null;
                    
                    if ($ubicacionesRaw) {
                        if (is_string($ubicacionesRaw)) {
                            $ubicacionesRaw = json_decode($ubicacionesRaw, true);
                        }
                        if (is_string($ubicacionesRaw)) {
                            $ubicacionesRaw = json_decode($ubicacionesRaw, true);
                        }
                        
                        if (is_array($ubicacionesRaw)) {
                            foreach ($ubicacionesRaw as $ub) {
                                if (is_array($ub)) {
                                    $ubicacionesLogo[] = [
                                        'ubicacion' => $ub['ubicacion'] ?? '',
                                        'descripcion' => $ub['descripcion'] ?? ''
                                    ];
                                } elseif (is_string($ub)) {
                                    // Si es string simple, agregar como ubicación
                                    $ubicacionesLogo[] = [
                                        'ubicacion' => $ub,
                                        'descripcion' => ''
                                    ];
                                }
                            }
                        }
                    }
                    
                    // Procesar fotos de la técnica (desde la relación ya cargada)
                    $fotosLogoFormato = [];
                    $fotosRelacion = $logoTecnica->fotos ?? null;
                    
                    // Si no se cargaron en eager loading, intentar cargarlas directamente
                    if (!$fotosRelacion || ($fotosRelacion instanceof \Illuminate\Database\Eloquent\Collection && count($fotosRelacion) === 0)) {
                        \Log::warning('[OBTENER-PRENDA-COMPLETA] ⚠️ Fotos no cargadas en eager loading, intentando fallback:', [
                            'logo_tecnica_id' => $logoTecnica->id
                        ]);
                        
                        $fotosRelacion = LogoCotizacionTecnicaPrendaFoto::where('logo_cotizacion_tecnica_prenda_id', $logoTecnica->id)
                            ->orderBy('orden')
                            ->get();
                        
                        \Log::info('[OBTENER-PRENDA-COMPLETA] 📸 Fotos cargadas con fallback:', [
                            'fotos_count' => count($fotosRelacion)
                        ]);
                    }
                    
                    if ($fotosRelacion && count($fotosRelacion) > 0) {
                        foreach ($fotosRelacion as $foto) {
                            $rutaOriginal = $foto->ruta_original;
                            $rutaWebp = $foto->ruta_webp;
                            $rutaMiniatura = $foto->ruta_miniatura;
                            
                            // Agregar /storage/ si no lo tiene ya
                            if ($rutaOriginal && !str_starts_with($rutaOriginal, '/')) {
                                $rutaOriginal = '/storage/' . $rutaOriginal;
                            }
                            if ($rutaWebp && !str_starts_with($rutaWebp, '/')) {
                                $rutaWebp = '/storage/' . $rutaWebp;
                            }
                            if ($rutaMiniatura && !str_starts_with($rutaMiniatura, '/')) {
                                $rutaMiniatura = '/storage/' . $rutaMiniatura;
                            }
                            
                            $fotosLogoFormato[] = [
                                'ruta' => $rutaOriginal,
                                'ruta_webp' => $rutaWebp,
                                'ruta_miniatura' => $rutaMiniatura,
                                'orden' => $foto->orden ?? 0
                            ];
                        }
                    }
                    
                    // Procesar variaciones de prenda (si existen)
                    $variacionesPrenda = [];
                    if ($logoTecnica->variaciones_prenda) {
                        $variacionesRaw = is_string($logoTecnica->variaciones_prenda) 
                            ? json_decode($logoTecnica->variaciones_prenda, true) 
                            : $logoTecnica->variaciones_prenda;
                        
                        if (is_array($variacionesRaw)) {
                            $variacionesPrenda = $variacionesRaw;
                        }
                    }
                    
                    // Procesar tallas
                    $tallasFormatoProceso = [];
                    if ($logoTecnica->talla_cantidad) {
                        $tallaCantidad = is_array($logoTecnica->talla_cantidad) 
                            ? $logoTecnica->talla_cantidad 
                            : json_decode($logoTecnica->talla_cantidad, true);
                        
                        if (is_array($tallaCantidad)) {
                            foreach ($tallaCantidad as $talla => $cantidad) {
                                $tallasFormatoProceso[$talla] = $cantidad;
                            }
                        }
                    }
                    
                    // Agregar técnica al array de procesos
                    // Usar el slug como clave para facilitar acceso en JS
                    $procesosFormato[$slugTecnica] = [
                        'tipo' => $nombreTecnica,
                        'slug' => $slugTecnica,
                        'ubicaciones' => $ubicacionesLogo,
                        'imagenes' => $fotosLogoFormato,
                        'observaciones' => $logoTecnica->observaciones ?? '',
                        'variaciones_prenda' => $variacionesPrenda,
                        'talla_cantidad' => $tallasFormatoProceso
                    ];
                }
            }

            // Retornar respuesta completa
            return response()->json([
                'success' => true,
                'cotizacion_id' => $cotizacionId,
                'numero_cotizacion' => $cotizacion->numero,
                'generosPresentes' => $generosPresentes,  // Incluir géneros para que el frontend los pre-seleccione
                'prenda' => [
                    'id' => $prenda->id,
                    'nombre_producto' => $prenda->nombre_producto,
                    'descripcion' => $prenda->descripcion ?? '',
                    'cantidad' => $prenda->cantidad,
                    'prenda_bodega' => $prenda->prenda_bodega,
                    'tallas_disponibles' => $tallasDisponibles,
                    'tallas' => $tallasConCantidades,
                    'telas' => $telasFormato,
                    'fotos' => $fotosFormato,
                    'variantes' => $variantes,
                    'logoCotizacionTelasPrenda' => $prenda->logoCotizacionTelasPrenda ? $prenda->logoCotizacionTelasPrenda->toArray() : []
                ],
                'procesos' => $procesosFormato
            ]);

        } catch (\Exception $e) {
            \Log::error('[OBTENER-PRENDA-COMPLETA] Error obteniendo prenda completa', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error al obtener prenda completa',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
