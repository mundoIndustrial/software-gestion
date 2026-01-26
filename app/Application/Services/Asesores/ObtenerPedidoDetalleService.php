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
     * @param int|string $pedidoIdentifier NÃºmero de pedido o ID
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
                $q2->orderBy('created_at', 'desc');
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
                $epps[] = [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'nombre' => $pedidoEpp->epp?->nombre ?? 'EPP Desconocido',
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
                'LavanderÃ­a',
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
     * Obtener tallas Ãºnicas del prenda
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
                        // Acceder a travÃ©s de relaciones cargadas
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
                    'color' => $tela['color_nombre'],
                    'tela' => $tela['tela_nombre'],
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
                
                Log::info('[OBTENER-FOTOS-PRENDA] DespuÃ©s de toArray():', [
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
     * Obtener solo información bÃ¡sica
     */
    public function obtenerBasico($pedidoIdentifier): array
    {
        Log::info(' [BASICO] Obteniendo información bÃ¡sica');

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
     * Obtener el pedido (por nÃºmero o ID)
     */
    private function obtenerPedido($pedidoIdentifier): PedidoProduccion
    {
        // Si es nÃºmero (numÃ©rico > 100)
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
     * Soporta DAMA, CABALLERO, UNISEX como gÃ©neros.
     */
    private function construirTallasProcesoRelacional($procesoPrendaDetalleId)
    {
        $tallas = [];
        
        $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
            'proceso_prenda_detalle_id', 
            $procesoPrendaDetalleId
        )->get();
        
        if ($tallasRelacionales->count() > 0) {
            // Agrupar por gÃ©nero
            foreach ($tallasRelacionales as $tallaRecord) {
                $genero = strtolower($tallaRecord->genero); // 'dama', 'caballero', 'unisex'
                
                if (!isset($tallas[$genero])) {
                    $tallas[$genero] = [];
                }
                
                // Agregar talla con su cantidad
                if ($tallaRecord->cantidad > 0) {
                    $tallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
                }
            }
        }
        
        return $tallas;
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
        Log::info('🔍 [PRENDA-DETALLE] Obteniendo prenda con procesos', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId
        ]);

        $prenda = \App\Models\PrendaPedido::where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->with([
                'procesos' => function ($q) {
                    $q->withTrashed()  //  INCLUIR SOFT-DELETED
                      ->with(['tipoProceso', 'imagenes' => function ($q2) {
                          $q2->withTrashed();  //  INCLUIR SOFT-DELETED
                      }])
                      ->orderBy('created_at', 'desc');
                },
                'fotos' => function ($q) {
                    $q->withTrashed();  //  INCLUIR SOFT-DELETED
                },
                'fotosTelas' => function ($q) {
                    $q->withTrashed();  //  INCLUIR SOFT-DELETED
                },
                'variantes' => function ($q) {
                    $q->with(['tipoManga', 'tipoBroche']);
                }
            ])
            ->firstOrFail();

        Log::info('✅ [PRENDA-ENCONTRADA] Prenda básica cargada', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'procesos_count' => $prenda->procesos->count(),
            'fotos_count' => $prenda->fotos->count(),
            'variantes_count' => $prenda->variantes->count()
        ]);

        // Transformar prenda a estructura esperada por el frontend
        $prendaTransformada = $this->transformarPrendaParaEdicion($prenda);

        Log::info('✅ [PRENDA-TRANSFORMADA] Prenda transformada completamente', [
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
        Log::info('🔄 [TRANSFORMAR-INICIO] Iniciando transformación de prenda', [
            'prenda_id' => $prenda->id,
            'prenda_nombre' => $prenda->nombre_prenda,
            'has_fotos_relation' => isset($prenda->fotos),
            'fotos_loaded' => $prenda->relationLoaded('fotos'),
            'fotos_count' => $prenda->fotos ? $prenda->fotos->count() : 'NO CARGADA'
        ]);

        $prendaArray = $prenda->toArray();

        // Procesos
        $procesos = [];
        if ($prenda->procesos) {
            foreach ($prenda->procesos as $proceso) {
                $procesos[] = $this->construirProcesoParaEdicion($proceso, $prenda->id);
            }
        }
        $prendaArray['procesos'] = $procesos;

        Log::info('📦 [PROCESOS-TRANSFORMADOS] ' . count($procesos) . ' procesos transformados');

        // Fotos - usar ruta_webp (o ruta_original si no existe)
        Log::info('🔍 [FOTOS-DEBUG] Intentando acceder a fotos', [
            'has_fotos_in_model' => isset($prenda->fotos),
            'fotos_count' => $prenda->fotos ? $prenda->fotos->count() : 0,
            'has_fotos_in_array' => isset($prendaArray['fotos']),
            'array_fotos_count' => isset($prendaArray['fotos']) ? count($prendaArray['fotos']) : 0
        ]);

        $prendaArray['imagenes'] = ($prenda->fotos && $prenda->fotos->count() > 0) 
            ? $prenda->fotos->map(function($foto) {
                Log::debug('🖼️ [FOTO-MAP] Procesando foto', [
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_original' => $foto->ruta_original
                ]);
                return $foto->ruta_webp ?? $foto->ruta_original ?? '';
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

        $prendaArray['tallas_dama'] = $tallasDama;
        $prendaArray['tallas_caballero'] = $tallasCaballero;

        Log::info('👗 [TALLAS-TRANSFORMADAS] Dama: ' . count($tallasDama) . ', Caballero: ' . count($tallasCaballero));

        // Variantes (mangas, broches, bolsillos)
        $variantes = [];
        if ($prenda->variantes) {
            foreach ($prenda->variantes as $var) {
                $variantes[] = [
                    'id' => $var->id,
                    'tipo_manga' => $var->tipoManga?->nombre ?? 'Sin especificar',
                    'tipo_manga_id' => $var->tipo_manga_id,
                    'tipo_broche_boton' => $var->tipoBroche?->nombre ?? 'Sin especificar',
                    'tipo_broche_boton_id' => $var->tipo_broche_boton_id,
                    'tiene_bolsillos' => (bool)$var->tiene_bolsillos,
                    'manga_obs' => $var->manga_obs,
                    'broche_boton_obs' => $var->broche_boton_obs,
                    'bolsillos_obs' => $var->bolsillos_obs
                ];
            }
        }
        $prendaArray['variantes'] = $variantes;

        Log::info('⚙️ [VARIANTES-TRANSFORMADAS] ' . count($variantes) . ' variantes transformadas');

        // Colores y Telas (prenda_pedido_colores_telas)
        $coloresTelas = [];
        $relaciones = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prenda->id)
            ->get(['id', 'color_id', 'tela_id'])
            ->toArray();

        Log::info('🎨 [COLORES-TELAS-INICIO] Encontradas ' . count($relaciones) . ' relaciones color-tela');

        foreach ($relaciones as $rel) {
            $color = DB::table('colores_prenda')->find($rel->color_id);
            $tela = DB::table('telas_prenda')->find($rel->tela_id);
            
            // Obtener fotos de esta combinación color-tela
            $fotos = DB::table('prenda_fotos_tela_pedido')
                ->where('prenda_pedido_colores_telas_id', $rel->id)
                ->get(['ruta_original', 'ruta_webp', 'orden'])
                ->toArray();

            Log::info('🎨 [COLOR-TELA] Color: ' . ($color->nombre ?? 'N/A') . ', Tela: ' . ($tela->nombre ?? 'N/A') . ', Fotos: ' . count($fotos));

            $coloresTelas[] = [
                'id' => $rel->id,
                'color_id' => $rel->color_id,
                'color_nombre' => $color->nombre ?? 'Color desconocido',
                'color_codigo' => $color->codigo ?? '',
                'tela_id' => $rel->tela_id,
                'tela_nombre' => $tela->nombre ?? 'Tela desconocida',
                'tela_referencia' => $tela->referencia ?? '',
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

        Log::info('✅ [COLORES-TELAS-COMPLETADAS] ' . count($coloresTelas) . ' combinaciones procesadas');
        Log::info('✅ [TRANSFORMAR-COMPLETO] Transformación finalizada exitosamente', [
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
        Log::info(' [PROCESO-DETALLE] Construyendo proceso para edición', [
            'proceso_id' => $proceso->id,
            'tipo_proceso' => $proceso->tipoProceso?->nombre ?? 'Desconocido',
            'imagenes_count' => $proceso->imagenes->count() ?? 0
        ]);

        $tallasProceso = $this->construirTallasProcesoRelacional($proceso->id);

        Log::info('✅ [PROCESO-CONSTRUIDO] Proceso construido', [
            'proceso_id' => $proceso->id,
            'tallas_count' => count($tallasProceso),
            'imagenes_count' => $proceso->imagenes->count() ?? 0
        ]);

        return [
            'id' => $proceso->id,
            'tipo' => $proceso->tipoProceso?->nombre ?? 'Tipo desconocido',
            'ubicaciones' => $proceso->ubicaciones ? (is_array($proceso->ubicaciones) ? $proceso->ubicaciones : explode(',', $proceso->ubicaciones)) : [],
            'observaciones' => $proceso->observaciones ?? '',
            'tallas' => $tallasProceso,
            'imagenes' => $proceso->imagenes->map(function($img) {
                return $img->ruta_webp ?? $img->ruta_original ?? '';
            })->filter()->toArray() ?? [],
        ];
    }
}

