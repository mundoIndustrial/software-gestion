<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ObtenerPedidoDetalleService
{
    /**
     * Obtener un pedido con todos sus detalles y relaciones
     * 
     * @param int|string $pedidoIdentifier numero de pedido o ID
     * @return PedidoProduccion
     * @throws \Exception
     */
    public function obtener($pedidoIdentifier): PedidoProduccion
    {
        Log::info('ðŸ“– [DETALLE] Obteniendo detalles del pedido', [
            'identificador' => $pedidoIdentifier
        ]);

        $pedido = $this->obtenerPedido($pedidoIdentifier);

        // Verificar permisos
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        Log::info(' [DETALLE] Pedido encontrado', [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente
        ]);

        return $pedido;
    }

    /**
     * Obtener un pedido con prendas
     */
    public function obtenerConPrendas($pedidoIdentifier): PedidoProduccion
    {
        Log::info(' [DETALLE-PRENDAS] Obteniendo con prendas');

        $pedido = PedidoProduccion::findOrFail($this->obtenerPedido($pedidoIdentifier)->id);
        $pedido->load(['prendas' => function ($q) {
            $q->with(['procesos' => function ($q2) {
                $q2->with(['tipoProceso', 'imagenes'])  //  Cargar tipoProceso e imagenes de procesos
                   ->orderBy('created_at', 'desc');
            }]);
        }]);

        Log::info(' [DETALLE-PRENDAS] Cargadas', [
            'prendas_count' => $pedido->prendas->count()
        ]);

        return $pedido;
    }

    /**
     * Obtener un pedido con todos sus datos
     */
    public function obtenerCompleto($pedidoIdentifier): PedidoProduccion
    {
        Log::info(' [DETALLE-COMPLETO] Obteniendo datos completos');

        $pedido = $this->obtenerPedido($pedidoIdentifier);

        // Verificar permisos
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        //  Cargar TODAS las relaciones necesarias
        $pedido->load([
            'prendas' => function ($q) {
                $q->with([
                    'procesos' => function ($q2) {
                        $q2->with(['tipoProceso', 'imagenes'])  //  Cargar tipoProceso e imagenes
                          ->orderBy('created_at', 'desc');
                    },
                    'fotos',           //  Fotos de prendas
                    'fotosTelas',      //  Fotos de telas
                    'variantes' => function ($q3) {  //  Variantes con todas sus relaciones
                        $q3->with(['tela', 'color', 'tipoManga', 'tipoBrocheBoton']);
                    }
                ]);
            },
            'asesora',
            'logoPedidos',
            'epps' => function ($q) {  //  EPPs del pedido
                $q->with(['epp', 'imagenes']);
            }
        ]);

        Log::info(' [DETALLE-COMPLETO] Datos completos listos', [
            'prendas' => $pedido->prendas->count(),
            'epps' => $pedido->epps->count() ?? 0,
            'logos' => $pedido->logoPedidos->count() ?? 0
        ]);

        return $pedido;
    }

    /**
     * Obtener datos de edición (formulario)
     *  NUEVO: Transforma prendas a la estructura esperada por GestorPrendaSinCotizacion
     */
    public function obtenerParaEdicion($pedidoIdentifier): array
    {
        Log::info(' [EDICION] Obteniendo datos para edición');

        $pedido = $this->obtenerCompleto($pedidoIdentifier);

        //  Transformar prendas a estructura del gestor
        $prendasTransformadas = $pedido->prendas->map(fn($prenda) => $this->transformarPrendaParaEdicion($prenda))->toArray();

        //  Clonar el pedido y reemplazar prendas
        $pedidoData = $pedido->toArray();
        $pedidoData['prendas'] = $prendasTransformadas;

        //  Transformar EPPs
        $epps = [];
        if ($pedido->epps) {
            foreach ($pedido->epps as $pedidoEpp) {
                $eppNombre = $pedidoEpp->epp?->nombre_completo ?? ($pedidoEpp->epp_id ? "EPP #{$pedidoEpp->epp_id}" : 'EPP Desconocido');
                $epps[] = [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'nombre' => $eppNombre,
                    'descripcion' => $pedidoEpp->epp?->descripcion ?? '',
                    'cantidad' => $pedidoEpp->cantidad,
                    'imagenes' => $pedidoEpp->imagenes ?? [],
                    'observaciones' => $pedidoEpp->observaciones ?? ''
                ];
            }
        }

        $datos = [
            'pedido' => (object) $pedidoData,
            'epps' => $epps,  //  Incluir EPPs
            'estados' => [
                'No iniciado',
                'En Ejecución',
                'Entregado',
                'Anulada'
            ],
            'areas' => [
                'Creación de Orden',
                'Corte',
                'Costura',
                'Bordado',
                'Estampado',
                'Control-Calidad',
                'Entrega',
                'Polos',
                'Taller',
                'Insumos',
                'Lavanderia',
                'Arreglos',
                'Despachos'
            ]
        ];

        Log::info(' [EDICION] Datos listos', [
            'pedido_id' => $pedido->id,
            'prendas_transformadas' => count($prendasTransformadas),
            'epps' => count($epps)
        ]);

        return $datos;
    }

    /**
     * Construir generosConTallas desde cantidad_talla
     * 
     * cantidad_talla en BD: {"dama": {"L": 120, "M": 20, "S": 30}}
     * Devolvemos: {"dama": {"L": 120, "M": 20, "S": 30}}
     */
    private function construirGenerosConTallas($prenda)
    {
        $generosConTallas = [];
        
        //  cantidad_talla viene como JSON string
        $cantidadTalla = $prenda->cantidad_talla;
        
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }
        
        // La estructura en BD ya es correcta: {genero: {talla: cantidad}}
        if ($cantidadTalla && is_array($cantidadTalla)) {
            $generosConTallas = $cantidadTalla;
        }

        return $generosConTallas;
    }

    /**
     * Obtener tallas unicas del prenda
     */
    private function obtenerTallasDelPrenda($prenda)
    {
        $tallas = [];
        
        //  cantidad_talla viene como JSON string
        $cantidadTalla = $prenda->cantidad_talla;
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }
        
        // cantidad_talla estructura: {genero: {talla: cantidad}}
        if ($cantidadTalla && is_array($cantidadTalla)) {
            foreach ($cantidadTalla as $genero => $tallasCantidades) {
                if (is_array($tallasCantidades)) {
                    foreach ($tallasCantidades as $talla => $cantidad) {
                        if (!in_array($talla, $tallas)) {
                            $tallas[] = $talla;
                        }
                    }
                }
            }
        }
        return $tallas;
    }

    /**
     * Obtener cantidades por talla
     */
    private function obtenerCantidadesPorTalla($prenda)
    {
        $cantidadTalla = $prenda->cantidad_talla;
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }
        return $cantidadTalla ?? [];
    }

    /**
     * Construir telas agregadas con sus fotos
     */
    private function construirTelasAgregadas($prenda)
    {
        //  Obtener telas desde prenda_pedido_variantes (con relaciones tela y color cargadas)
        $telas = [];
        
        if (isset($prenda->variantes) && $prenda->variantes && count($prenda->variantes) > 0) {
            // Agrupar por tela_id y color_id
            $telasUnicas = [];
            foreach ($prenda->variantes as $variante) {
                $telaId = $variante->tela_id ?? null;
                $colorId = $variante->color_id ?? null;
                
                if ($telaId || $colorId) {
                    $key = "$telaId-$colorId";
                    if (!isset($telasUnicas[$key])) {
                        // Acceder a atraves de relaciones cargadas
                        $telaNombre = $variante->tela?->nombre ?? 'N/A';
                        $colorNombre = $variante->color?->nombre ?? 'N/A';
                        $colorCodigo = $variante->color?->codigo ?? '';
                        
                        $telasUnicas[$key] = [
                            'tela_id' => $telaId,
                            'color_id' => $colorId,
                            'tela_nombre' => $telaNombre,
                            'color_nombre' => $colorNombre,
                            'referencia' => $colorCodigo,
                        ];
                    }
                }
            }
            
            // Construir array de telas agregadas con sus fotos
            $telaFotos = $this->obtenerFotosTelas($prenda);
            foreach ($telasUnicas as $tela) {
                $telas[] = [
                    'nombre_tela' => $tela['tela_nombre'],
                    'color' => $tela['color_nombre'],
                    'referencia' => $tela['referencia'],
                    'imagenes' => $telaFotos,
                ];
            }
        }
        
        return $telas;
    }

    /**
     * Obtener fotos de telas
     */
    private function obtenerFotosTelas($prenda)
    {
        $fotos = [];
        if (isset($prenda->fotosTelas) && $prenda->fotosTelas) {
            $fotosTelas = is_array($prenda->fotosTelas) ? $prenda->fotosTelas : $prenda->fotosTelas->toArray();
            //  Filtrar solo las fotos que sean de telas (contienen '/telas/' en la ruta)
            foreach ($fotosTelas as $foto) {
                if (isset($foto['url']) && strpos($foto['url'], '/telas/') !== false) {
                    // Procesar la URL para remover /storage/ duplicado
                    if (strpos($foto['url'], '/storage/') === 0) {
                        $foto['url'] = ltrim($foto['url'], '/');
                    }
                    $fotos[] = $foto;
                }
            }
        }
        return $fotos;
    }

    /**
     * Obtener fotos de prenda
     */
    private function obtenerFotosPrenda($prenda)
    {
        $fotos = [];
        if (isset($prenda->fotos) && $prenda->fotos) {
            foreach ($prenda->fotos as $foto) {
                Log::info('[OBTENER-FOTOS-PRENDA] Foto de prenda en BD:', [
                    'foto_id' => $foto->id,
                    'ruta_webp_bd' => $foto->ruta_webp,
                    'ruta_original_bd' => $foto->ruta_original
                ]);
                
                $fotoArray = $foto->toArray();
                
                Log::info('[OBTENER-FOTOS-PRENDA] despues de toArray():', [
                    'foto_id' => $foto->id,
                    'url_en_array' => $fotoArray['url'] ?? 'NO EXISTE',
                    'ruta_en_array' => $fotoArray['ruta'] ?? 'NO EXISTE',
                    'ruta_webp_en_array' => $fotoArray['ruta_webp'] ?? 'NO EXISTE'
                ]);
                
                // Procesar la URL para remover /storage/ duplicado
                if (isset($fotoArray['url'])) {
                    $urlOriginal = $fotoArray['url'];
                    // Si comienza con /storage/, remover /storage/ para que el frontend lo agregue
                    if (strpos($fotoArray['url'], '/storage/') === 0) {
                        $fotoArray['url'] = ltrim($fotoArray['url'], '/');
                        Log::info('[OBTENER-FOTOS-PRENDA] URL procesada:', [
                            'foto_id' => $foto->id,
                            'url_original' => $urlOriginal,
                            'url_procesada' => $fotoArray['url']
                        ]);
                    }
                }
                $fotos[] = $fotoArray;
            }
        }
        return $fotos;
    }

    /**
     * Construir procesos en estructura esperada
     */
    /**
     * Construir variantes
     */
    private function construirVariantes($prenda)
    {
        return [
            'tipo_manga' => $prenda->tipo_manga ?? 'No aplica',
            'obs_manga' => $prenda->obs_manga ?? '',
            'tipo_broche' => $prenda->tipo_broche ?? 'No aplica',
            'obs_broche' => $prenda->obs_broche ?? '',
            'tiene_bolsillos' => (bool)($prenda->tiene_bolsillos ?? false),
            'obs_bolsillos' => $prenda->obs_bolsillos ?? '',
            'tiene_reflectivo' => (bool)($prenda->tiene_reflectivo ?? false),
            'obs_reflectivo' => $prenda->obs_reflectivo ?? '',
            'telas_multiples' => []
        ];
    }

    /**
     * Obtener solo información basica
     */
    public function obtenerBasico($pedidoIdentifier): array
    {
        Log::info(' [BASICO] Obteniendo información basica');

        $pedido = $this->obtenerPedido($pedidoIdentifier);

        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver este pedido', 403);
        }

        return [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'estado' => $pedido->estado,
            'forma_de_pago' => $pedido->forma_de_pago,
            'fecha_creacion' => $pedido->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Obtener el pedido (por numero o ID)
     */
    private function obtenerPedido($pedidoIdentifier): PedidoProduccion
    {
        // Si es numero (numerico > 100)
        if (is_numeric($pedidoIdentifier) && $pedidoIdentifier > 100) {
            $pedido = PedidoProduccion::where('numero_pedido', $pedidoIdentifier)->first();
            if ($pedido) {
                return $pedido;
            }
        }

        // Intentar por ID
        $pedido = PedidoProduccion::find($pedidoIdentifier);
        if ($pedido) {
            return $pedido;
        }

        throw new \Exception('Pedido no encontrado', 404);
    }

    /**
     * Verificar si un pedido pertenece al usuario autenticado
     */
    public function esDelUsuario($pedidoIdentifier): bool
    {
        try {
            $pedido = $this->obtenerPedido($pedidoIdentifier);
            return $pedido->asesor_id === Auth::id();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener cantidad de prendas
     */
    public function obtenerCantidadPrendas($pedidoIdentifier): int
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        return $pedido->prendas()->count();
    }

    /**
     * Construir tallas de proceso DESDE LA TABLA RELACIONAL
     * 
     * Lee de pedidos_procesos_prenda_tallas (estructura: {genero: {talla: cantidad}})
     * Soporta DAMA, CABALLERO, UNISEX como generos.
     */
    private function construirTallasProcesoRelacional($procesoPrendaDetalleId)
    {
        $tallas = [];
        $datosExtendidos = [];
        
        $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
            'proceso_prenda_detalle_id', 
            $procesoPrendaDetalleId
        )->get();
        
        if ($tallasRelacionales->count() > 0) {
            // Agrupar por género
            foreach ($tallasRelacionales as $tallaRecord) {
                $genero = strtolower($tallaRecord->genero); // 'dama', 'caballero', 'unisex'
                
                if (!isset($tallas[$genero])) {
                    $tallas[$genero] = [];
                }
                if (!isset($datosExtendidos[$genero])) {
                    $datosExtendidos[$genero] = [];
                }
                
                // Agregar talla con su cantidad
                // VERIFICAR si tiene colores asociados en pedidos_procesos_prenda_talla_colores
                $coloresAsociados = DB::table('pedidos_procesos_prenda_talla_colores')
                    ->where('pedidos_procesos_prenda_talla_id', $tallaRecord->id)
                    ->get();

                if ($coloresAsociados->count() > 0) {
                    // Tiene colores: crear una entrada por cada color con key TALLA__COLOR
                    foreach ($coloresAsociados as $colorRecord) {
                        $tallaColorKey = $tallaRecord->talla . '__' . $colorRecord->color_nombre;
                        $cantidadColor = $colorRecord->cantidad ?? $tallaRecord->cantidad;
                        
                        if ($cantidadColor > 0) {
                            $tallas[$genero][$tallaColorKey] = $cantidadColor;
                        }
                        
                        // Procesar ubicaciones del color
                        $ubicacionesColor = [];
                        if ($colorRecord->ubicaciones) {
                            $decoded = json_decode($colorRecord->ubicaciones, true);
                            if (is_array($decoded)) {
                                $ubicacionesColor = $decoded;
                            } else {
                                $ubicacionesColor = [$colorRecord->ubicaciones];
                            }
                        }
                        
                        // Cargar imágenes específicas para esta talla
                        $imagenesPorTalla = DB::table('pedidos_procesos_imagenes')
                            ->where('proceso_prenda_talla_id', $tallaRecord->id)
                            ->whereNull('deleted_at')
                            ->orderBy('orden', 'asc')
                            ->get()
                            ->map(function($img) {
                                $ruta_webp = $img->ruta_webp ?? '';
                                $ruta_original = $img->ruta_original ?? '';
                                if ($ruta_webp && !str_starts_with($ruta_webp, '/storage/')) {
                                    $ruta_webp = '/storage/' . ltrim($ruta_webp, '/');
                                }
                                if ($ruta_original && !str_starts_with($ruta_original, '/storage/')) {
                                    $ruta_original = '/storage/' . ltrim($ruta_original, '/');
                                }
                                return [
                                    'id' => $img->id,
                                    'ruta_webp' => $ruta_webp,
                                    'ruta_original' => $ruta_original,
                                    'url' => $ruta_webp ?: $ruta_original,
                                    'es_principal' => $img->es_principal ?? false
                                ];
                            })
                            ->filter(fn($img) => $img['ruta_webp'] || $img['ruta_original'])
                            ->values()
                            ->toArray();
                        
                        $datosExtendidos[$genero][$tallaColorKey] = [
                            'cantidadSeleccionada' => $cantidadColor,
                            'ubicaciones' => $ubicacionesColor,
                            'observaciones' => $colorRecord->observaciones ?? '',
                            'imagenes' => $imagenesPorTalla
                        ];
                    }
                } else {
                    // Sin colores: usar talla simple
                    if ($tallaRecord->cantidad > 0) {
                        $tallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
                    }
                
                    // Agregar datos extendidos para renderización con ubicaciones/observaciones
                    $tallaKey = $tallaRecord->talla;
                    
                    // Procesar ubicaciones - si es JSON, decodificar a array; si es string, convertir a array
                    $ubicacionesProcesadas = [];
                    if ($tallaRecord->ubicaciones) {
                        if (is_array($tallaRecord->ubicaciones)) {
                            $ubicacionesProcesadas = $tallaRecord->ubicaciones;
                        } else {
                            $decoded = json_decode($tallaRecord->ubicaciones, true);
                            if (is_array($decoded)) {
                                $ubicacionesProcesadas = $decoded;
                            } else {
                                $ubicacionesProcesadas = [$tallaRecord->ubicaciones];
                            }
                        }
                    }
                    
                    // Cargar imágenes específicas para esta talla desde pedidos_procesos_imagenes
                    $imagenesPorTalla = DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_talla_id', $tallaRecord->id)
                        ->whereNull('deleted_at')
                        ->orderBy('orden', 'asc')
                        ->get()
                        ->map(function($img) {
                            $ruta_webp = $img->ruta_webp ?? '';
                            $ruta_original = $img->ruta_original ?? '';
                            
                            if ($ruta_webp && !str_starts_with($ruta_webp, '/storage/')) {
                                $ruta_webp = '/storage/' . ltrim($ruta_webp, '/');
                            }
                            if ($ruta_original && !str_starts_with($ruta_original, '/storage/')) {
                                $ruta_original = '/storage/' . ltrim($ruta_original, '/');
                            }
                            
                            return [
                                'id' => $img->id,
                                'ruta_webp' => $ruta_webp,
                                'ruta_original' => $ruta_original,
                                'url' => $ruta_webp ?: $ruta_original,
                                'es_principal' => $img->es_principal ?? false
                            ];
                        })
                        ->filter(fn($img) => $img['ruta_webp'] || $img['ruta_original'])
                        ->values()
                        ->toArray();
                    
                    $datosExtendidos[$genero][$tallaKey] = [
                        'cantidadSeleccionada' => $tallaRecord->cantidad,
                        'ubicaciones' => $ubicacionesProcesadas,
                        'observaciones' => $tallaRecord->observaciones ?? '',
                        'imagenes' => $imagenesPorTalla
                    ];
                }
            }
        }
        
        return [
            'tallas' => $tallas,
            'datosExtendidos' => $datosExtendidos
        ];
    }

    /**
     * Obtener cantidad de procesos
     */
    public function obtenerCantidadProcesos($pedidoIdentifier): int
    {
        $pedido = $this->obtenerPedido($pedidoIdentifier);
        return $pedido->prendas()
            ->with('procesos')
            ->get()
            ->flatMap->procesos
            ->count();
    }

    /**
     * Obtener una prenda específica del pedido con todos sus procesos
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array
     */
    public function obtenerPrendaConProcesos($pedidoId, $prendaId): array
    {
        Log::info(' [PRENDA-DETALLE] Obteniendo prenda con procesos', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId
        ]);

        $prenda = \App\Models\PrendaPedido::where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->with([
                'procesos' => function ($q) {
                    $q->with(['tipoProceso', 'imagenes'])  // 🔴 NO usar withTrashed: excluir imágenes eliminadas
                      ->orderBy('created_at', 'desc');
                },
                'fotos' => function ($q) {
                    //  CAMBIO: NO incluir fotos eliminadas (deleted_at) en el modal de edición
                    $q->whereNull('deleted_at');  // Solo fotos activas
                },
                'fotosTelas' => function ($q) {
                    $q->whereNull('deleted_at');  // Solo fotos de telas activas
                },
                'variantes' => function ($q) {
                    $q->with(['tipoManga', 'tipoBrocheBoton']);
                }
            ])
            ->firstOrFail();

        Log::info(' [PRENDA-ENCONTRADA] Prenda básica cargada', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'procesos_count' => $prenda->procesos->count(),
            'fotos_count' => $prenda->fotos->count(),
            'variantes_count' => $prenda->variantes->count()
        ]);

        // Transformar prenda a estructura esperada por el frontend
        $prendaTransformada = $this->transformarPrendaParaEdicion($prenda);

        Log::info(' [PRENDA-TRANSFORMADA] Prenda transformada completamente', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($prendaTransformada['procesos'] ?? []),
            'tallas_dama_count' => count($prendaTransformada['tallas_dama'] ?? []),
            'tallas_caballero_count' => count($prendaTransformada['tallas_caballero'] ?? []),
            'variantes_count' => count($prendaTransformada['variantes'] ?? []),
            'colores_telas_count' => count($prendaTransformada['colores_telas'] ?? [])
        ]);

        return $prendaTransformada;
    }

    /**
     * Transformar una prenda individual para edición (sin array)
     */
    private function transformarPrendaParaEdicion($prenda): array
    {
        Log::info(' [TRANSFORMAR-INICIO] Iniciando transformación de prenda', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'has_fotos_relation' => isset($prenda->fotos),
            'fotos_loaded' => $prenda->relationLoaded('fotos'),
            'fotos_count' => $prenda->fotos ? $prenda->fotos->count() : 'NO CARGADA'
        ]);

        $prendaArray = $prenda->toArray();

        //  FIX: Convertir de_bodega a origen para que el frontend tenga ambos formatos
        $prendaArray['origen'] = $prendaArray['de_bodega'] == 1 ? 'bodega' : 'confeccion';

        // Procesos
        $procesos = [];
        if ($prenda->procesos) {
            foreach ($prenda->procesos as $proceso) {
                $procesos[] = $this->construirProcesoParaEdicion($proceso, $prenda->id);
            }
        }
        $prendaArray['procesos'] = $procesos;

        Log::info(' [PROCESOS-TRANSFORMADOS] ' . count($procesos) . ' procesos transformados');

        // Fotos - usar ruta_webp (o ruta_original si no existe)
        Log::info(' [FOTOS-DEBUG] Intentando acceder a fotos', [
            'has_fotos_in_model' => isset($prenda->fotos),
            'fotos_count' => $prenda->fotos ? $prenda->fotos->count() : 0,
            'has_fotos_in_array' => isset($prendaArray['fotos']),
            'array_fotos_count' => isset($prendaArray['fotos']) ? count($prendaArray['fotos']) : 0
        ]);

        $prendaArray['imagenes'] = ($prenda->fotos && $prenda->fotos->count() > 0) 
            ? $prenda->fotos->map(function($foto) {
                Log::debug('🖼️ [FOTO-MAP] Procesando foto', [
                    'id' => $foto->id,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_original' => $foto->ruta_original
                ]);
                // 🔴 NUEVO: Devolver objeto con ID y ruta para que el frontend pueda marcar para eliminación
                // Normalizar rutas para agregar /storage/ si no lo tienen
                $rutaWebp = $foto->ruta_webp ?? '';
                $rutaOriginal = $foto->ruta_original ?? '';
                
                // Agregar /storage/ si no comienza con /storage/
                if ($rutaWebp && !str_starts_with($rutaWebp, '/storage/')) {
                    $rutaWebp = '/storage/' . ltrim($rutaWebp, '/');
                }
                if ($rutaOriginal && !str_starts_with($rutaOriginal, '/storage/')) {
                    $rutaOriginal = '/storage/' . ltrim($rutaOriginal, '/');
                }
                
                return [
                    'id' => $foto->id,
                    'url' => $rutaWebp ?: $rutaOriginal,
                    'ruta_webp' => $rutaWebp,
                    'ruta_original' => $rutaOriginal,
                ];
            })->filter()->toArray() 
            : [];
        
        $prendaArray['imagenes_tela'] = ($prenda->fotosTelas && $prenda->fotosTelas->count() > 0)
            ? $prenda->fotosTelas->map(function($foto) {
                return $foto->ruta_webp ?? $foto->ruta_original ?? '';
            })->filter()->toArray() 
            : [];

        Log::info('📸 [IMAGENES-TRANSFORMADAS] ' . count($prendaArray['imagenes']) . ' imagenes, ' . count($prendaArray['imagenes_tela']) . ' imagenes de tela');


        // Tallas por género - Extraer de tabla prenda_pedido_tallas
        $tallasDama = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->where('genero', 'DAMA')
            ->get(['talla', 'cantidad'])
            ->toArray();
        
        $tallasCaballero = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->where('genero', 'CABALLERO')
            ->get(['talla', 'cantidad'])
            ->toArray();

        $tallasUnisex = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->where('genero', 'UNISEX')
            ->get(['talla', 'cantidad'])
            ->toArray();

        $tallasSobremedida = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->where('genero', 'SOBREMEDIDA')
            ->get(['talla', 'cantidad'])
            ->toArray();

        // 🔴 NUEVO: Obtener SOLO CANTIDAD (genero='GENERICO')
        $tallasGenerico = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prenda->id)
            ->where('genero', 'GENERICO')
            ->get(['talla', 'cantidad'])
            ->toArray();

        $prendaArray['tallas_dama'] = $tallasDama;
        $prendaArray['tallas_caballero'] = $tallasCaballero;
        $prendaArray['tallas_unisex'] = $tallasUnisex;
        $prendaArray['tallas_sobremedida'] = $tallasSobremedida;
        $prendaArray['tallas_generico'] = $tallasGenerico;  // 🔴 NUEVO: Para detectar SOLO CANTIDAD

        Log::info(' [TALLAS-TRANSFORMADAS] Dama: ' . count($tallasDama) . ', Caballero: ' . count($tallasCaballero) . ', Unisex: ' . count($tallasUnisex) . ', Sobremedida: ' . count($tallasSobremedida) . ', GENERICO: ' . count($tallasGenerico));

        // === Colores por talla (prenda_pedido_talla_colores) ===
        $tallaColores = DB::table('prenda_pedido_talla_colores as ptc')
            ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
            ->where('pt.prenda_pedido_id', $prenda->id)
            ->select([
                'ptc.id',
                'ptc.prenda_pedido_talla_id',
                'pt.genero',
                'pt.talla',
                'ptc.tela_id',
                'ptc.tela_nombre',
                'ptc.color_id',
                'ptc.color_nombre',
                'ptc.cantidad',
                'ptc.referencia',
                'ptc.observaciones',
                'ptc.imagen_ruta'
            ])
            ->get()
            ->toArray();

        // Asignar talla_colores al array de la prenda
        $prendaArray['talla_colores'] = $tallaColores;

        // NUEVO: Normalizar imagen_ruta para agregar /storage/ si no lo tiene
        if (!empty($prendaArray['talla_colores'])) {
            $prendaArray['talla_colores'] = array_map(function($color) {
                if ($color->imagen_ruta) {
                    $ruta = str_replace('\\', '/', $color->imagen_ruta);
                    if (!str_starts_with($ruta, '/storage/')) {
                        if (str_starts_with($ruta, 'storage/')) {
                            $ruta = '/' . $ruta;
                        } elseif (!str_starts_with($ruta, '/')) {
                            $ruta = '/storage/' . $ruta;
                        }
                    }
                    $color->imagen_ruta = $ruta;
                }
                return $color;
            }, $prendaArray['talla_colores']);
        }
        
        Log::info('🎨 [TALLA-COLORES] Encontrados ' . count($tallaColores) . ' registros de colores por talla');
        
        $mapaColorTelaId = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prenda->id)
            ->get(['id', 'color_id', 'tela_id'])
            ->reduce(function(array $carry, $rel) {
                $clave = ($rel->color_id ?? '') . ':' . ($rel->tela_id ?? '');
                if ($clave !== ':') {
                    $carry[$clave] = (int) $rel->id;
                }
                return $carry;
            }, []);

        // NUEVO: Construir asignacionesColoresPorTalla desde talla_colores para compatibilidad con invoice y edit
        $asignacionesColoresPorTalla = [];
        if (!empty($prendaArray['talla_colores'])) {
            foreach ($prendaArray['talla_colores'] as $color) {
                // Determinar tipo de talla (IGUAL AL FRONTEND: "Letra" o "Número")
                $tipoTalla = preg_match('/^\d+$/', $color->talla) ? 'Número' : 'Letra';
                
                // Clave: genero-tipoTalla-talla (ej: dama-Letra-L o caballero-Número-36)
                // ESTO DEBE COINCIDIR CON EL FRONTEND (cargar-prendas-cotizacion.js)
                $generoLower = strtolower($color->genero);
                $clave = $generoLower . '-' . $tipoTalla . '-' . $color->talla;
                $claveRelacion = ($color->color_id ?? '') . ':' . ($color->tela_id ?? '');
                $colorTelaId = $mapaColorTelaId[$claveRelacion] ?? null;
                
                if (!isset($asignacionesColoresPorTalla[$clave])) {
                    $asignacionesColoresPorTalla[$clave] = [
                        'genero' => $generoLower,
                        'tela' => $color->tela_nombre ?? 'SIN_TELA',
                        'tela_id' => $color->tela_id ? (int) $color->tela_id : null,
                        'tipo' => $tipoTalla,
                        'talla' => $color->talla,
                        'prenda_pedido_colores_telas_id' => $colorTelaId,
                        'colores' => []
                    ];
                }
                
                $asignacionesColoresPorTalla[$clave]['colores'][] = [
                    'nombre' => $color->color_nombre,
                    'cantidad' => $color->cantidad,
                    'referencia' => $color->referencia,
                    'observaciones' => $color->observaciones,
                    'imagen_ruta' => $color->imagen_ruta,  // ← CRUCIAL: Incluir imagen_ruta
                    'prenda_pedido_colores_telas_id' => $colorTelaId,
                    'color_id' => $color->color_id ? (int) $color->color_id : null,
                    'tela_id' => $color->tela_id ? (int) $color->tela_id : null,
                ];
            }
        }
        $prendaArray['asignacionesColoresPorTalla'] = $asignacionesColoresPorTalla;
        
        Log::info('🎨 [ASIGNACIONES-COLORES-CONSTRUIDAS] Construido desde talla_colores', [
            'cantidad_claves' => count($asignacionesColoresPorTalla),
            'claves' => array_keys($asignacionesColoresPorTalla)
        ]);
        
        // DEBUG: Log detallado de talla_colores para supervisor-pedidos
        Log::info('🐛 [DEBUG-TALLA-COLORES] Estructura completa de talla_colores', [
            'cantidad' => count($tallaColores),
            'contenido' => $tallaColores,
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedidoId ?? 'unknown'
        ]);

        // Variantes (mangas, broches, bolsillos)
        $variantes = [];
        if ($prenda->variantes) {
            foreach ($prenda->variantes as $var) {
                $variantes[] = [
                    'id' => $var->id,
                    'tipo_manga' => $var->tipoManga?->nombre ?? 'Sin especificar',
                    'tipo_manga_id' => $var->tipo_manga_id,
                    'tipo_broche_boton' => $var->tipoBrocheBoton?->nombre ?? 'Sin especificar',
                    'tipo_broche_boton_id' => $var->tipo_broche_boton_id,
                    'tiene_bolsillos' => (bool)$var->tiene_bolsillos,
                    'manga_obs' => $var->manga_obs,
                    'broche_boton_obs' => $var->broche_boton_obs,
                    'bolsillos_obs' => $var->bolsillos_obs
                ];
            }
        }
        $prendaArray['variantes'] = $variantes;

        Log::info(' [VARIANTES-TRANSFORMADAS] ' . count($variantes) . ' variantes transformadas');

        // Colores y Telas (prenda_pedido_colores_telas)
        $coloresTelas = [];
        $relaciones = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prenda->id)
            ->get(['id', 'color_id', 'tela_id', 'referencia'])
            ->toArray();

        Log::info(' [COLORES-TELAS-INICIO] Encontradas ' . count($relaciones) . ' relaciones color-tela');

        foreach ($relaciones as $rel) {
            $color = DB::table('colores_prenda')->find($rel->color_id);
            // Resolver tela de forma robusta (evitar "Tela desconocida" por registros faltantes)
            $tela = DB::table('telas_prenda')->where('id', $rel->tela_id)->first(['id', 'nombre', 'referencia']);

            $telaNombre = $tela->nombre ?? null;
            $telaReferencia = $tela->referencia ?? null;

            // Fallback: tomar nombre desde prenda_pedido_talla_colores (en algunos pedidos legacy)
            if (!$telaNombre) {
                $telaNombre = DB::table('prenda_pedido_talla_colores as ptc')
                    ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                    ->where('pt.prenda_pedido_id', $prenda->id)
                    ->where('ptc.tela_id', $rel->tela_id)
                    ->value('ptc.tela_nombre');
            }

            if (!$telaNombre && $rel->tela_id) {
                $telaNombre = 'Tela #' . $rel->tela_id;
            }
            
            // Obtener fotos de esta combinación color-tela
            $fotos = DB::table('prenda_fotos_tela_pedido')
                ->where('prenda_pedido_colores_telas_id', $rel->id)
                ->get(['ruta_original', 'ruta_webp', 'orden'])
                ->toArray();

            Log::info(' [COLOR-TELA] Color: ' . ($color->nombre ?? 'N/A') . ', Tela: ' . ($tela->nombre ?? 'N/A') . ', Fotos: ' . count($fotos));

            $coloresTelas[] = [
                'id' => $rel->id,
                'color_id' => $rel->color_id,
                'color_nombre' => $color->nombre ?? 'Sin color',
                'color_codigo' => $color->codigo ?? '',
                'tela_id' => $rel->tela_id,
                'tela_nombre' => $telaNombre ?? 'Tela desconocida',
                'tela_referencia' => $rel->referencia ?? $telaReferencia ?? '',
                'fotos_tela' => array_map(function($f) {
                    return [
                        'ruta_original' => $f->ruta_original,
                        'ruta_webp' => $f->ruta_webp,
                        'url' => $f->ruta_webp ?? $f->ruta_original
                    ];
                }, $fotos)
            ];
        }
        $prendaArray['colores_telas'] = $coloresTelas;

        Log::info(' [COLORES-TELAS-COMPLETADAS] ' . count($coloresTelas) . ' combinaciones procesadas');
        Log::info(' [TRANSFORMAR-COMPLETO] Transformación finalizada exitosamente', [
            'prenda_id' => $prenda->id,
            'tallas_dama' => count($tallasDama),
            'tallas_caballero' => count($tallasCaballero),
            'variantes' => count($variantes),
            'colores_telas' => count($coloresTelas),
            'procesos' => count($procesos)
        ]);

        return $prendaArray;
    }

    /**
     * Construir un proceso para edición
     */
    private function construirProcesoParaEdicion($proceso, $prendaId): array
    {
        Log::info('🔴 [PROCESO-DETALLE] Construyendo proceso para edición', [
            'proceso_id' => $proceso->id,
            'tipo_proceso' => $proceso->tipoProceso?->nombre ?? 'Desconocido',
            'imagenes_count' => $proceso->imagenes->count() ?? 0
        ]);

        $tallasProceso = $this->construirTallasProcesoRelacional($proceso->id);
        
        // Desempaquetar respuesta que ahora es array con 'tallas' y 'datosExtendidos'
        $tallas = $tallasProceso['tallas'] ?? [];
        $datosExtendidos = $tallasProceso['datosExtendidos'] ?? [];

        Log::info('🔴 [PROCESO-CONSTRUIDO] Proceso construido', [
            'proceso_id' => $proceso->id,
            'tallas_count' => count($tallas),
            'tieneDetallePorTalla' => count(array_filter($datosExtendidos, fn($g) => count($g) > 0)) > 0,
            'datosExtendidos_estructura' => array_map(fn($g) => count($g), $datosExtendidos),
            'datosExtendidos_muestra' => array_slice($datosExtendidos, 0, 1),
            'imagenes_proceso_count' => $proceso->imagenes->count() ?? 0
        ]);

        $procesoArray = [
            'id' => $proceso->id,
            'tipo' => $proceso->tipoProceso?->nombre ?? 'Tipo desconocido',
            'ubicaciones' => $proceso->ubicaciones ? (is_array($proceso->ubicaciones) ? $proceso->ubicaciones : (json_decode($proceso->ubicaciones, true) ?? [$proceso->ubicaciones])) : [],
            'observaciones' => $proceso->observaciones ?? '',
            'modo_tallas' => $proceso->modo_tallas ?? 'general',
            'tallas' => $tallas,
            'datosExtendidos' => $datosExtendidos,
            'imagenes' => $proceso->imagenes->map(function($img) {
                // CRITICO: Devolver objeto completo con TODOS los campos, no solo URL
                $ruta_webp = str_replace('\\', '/', $img->ruta_webp ?? '');
                $ruta_original = str_replace('\\', '/', $img->ruta_original ?? '');
                
                // Normalizar ruta_webp
                if ($ruta_webp) {
                    if (strpos($ruta_webp, '/storage/') !== 0) {
                        if (strpos($ruta_webp, 'storage/') === 0) {
                            $ruta_webp = '/' . $ruta_webp;
                        } elseif (strpos($ruta_webp, '/') !== 0) {
                            $ruta_webp = '/storage/' . $ruta_webp;
                        }
                    }
                }
                
                // Normalizar ruta_original
                if ($ruta_original) {
                    if (strpos($ruta_original, '/storage/') !== 0) {
                        if (strpos($ruta_original, 'storage/') === 0) {
                            $ruta_original = '/' . $ruta_original;
                        } elseif (strpos($ruta_original, '/') !== 0) {
                            $ruta_original = '/storage/' . $ruta_original;
                        }
                    }
                }
                
                // Retornar objeto completo con id y ambas rutas
                return [
                    'id' => $img->id,
                    'ruta_webp' => $ruta_webp,
                    'ruta_original' => $ruta_original,
                    'url' => $ruta_webp ?: $ruta_original, // Para compatibilidad con frontend
                    'es_principal' => $img->es_principal ?? false
                ];
            })->filter(function($img) {
                // Filtrar: devolver solo si tiene ruta_webp o ruta_original
                return $img['ruta_webp'] || $img['ruta_original'];
            })->toArray() ?? [],
        ];

        return $procesoArray;
    }
}
