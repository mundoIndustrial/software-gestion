<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Traits\GestionaTallasRelacional;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositorio para acceso a datos de Pedidos de Producción
 * Responsabilidad: Encapsular todas las queries de pedidos
 */
class PedidoProduccionRepository
{
    use GestionaTallasRelacional;
    /**
     * Obtener pedido por ID con relaciones
     */
    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'cotizacion.tipoCotizacion',
            'prendas.variantes.tipoManga',
            'prendas.variantes.tipoBroche',
            'prendas.fotos',
            'prendas.fotosTelas',
            'prendas.coloresTelas.color',  // NUEVO: Cargar colores y telas desde tabla intermedia
            'prendas.coloresTelas.tela',   // NUEVO: Cargar telas con sus detalles (nombre, referencia)
            'prendas.coloresTelas.fotos', // NUEVO: Cargar fotos de telas para cada combinación color-tela
            'prendas.tallas',  // NUEVA: Cargar tallas relacionales
            'prendas.procesos',
            'prendas.procesos.tipoProceso',  //  NUEVO: Cargar el nombre del tipo de proceso
            'prendas.procesos.imagenes',
            'prendas.procesos.tallas',  // NUEVO: Cargar tallas de cada proceso (desde pedidos_procesos_prenda_tallas)
            'epps.imagenes',  // NO cargar categoria: es opcional
        ])->find($id);
    }

    /**
     * Obtener el Ãºltimo pedido creado (para secuencial de nÃºmeros)
     */
    public function obtenerUltimoPedido(): ?PedidoProduccion
    {
        return PedidoProduccion::orderBy('id', 'desc')->first();
    }

    /**
     * Obtener pedidos del asesor con filtros
     */
    public function obtenerPedidosAsesor(array $filtros = []): LengthAwarePaginator
    {
        $query = PedidoProduccion::query()
            ->select([
                'pedidos_produccion.*',
                'pedidos_produccion.area'  // Asegurar que se incluye el campo area
            ])
            ->with(['cotizacion', 'prendas']);

        // Si el usuario es asesor, solo mostrar sus pedidos
        // Otros roles pueden ver todos los pedidos
        $user = Auth::user();
        if ($user && $user->hasRole('asesor')) {
            $query->where('asesor_id', Auth::id());
        }

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Verificar si el pedido pertenece al asesor
     */
    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool
    {
        return PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->exists();
    }

    /**
     * Actualizar cantidad total del pedido
     */
    public function actualizarCantidadTotal(string $numeroPedido): void
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        if ($pedido) {
            $cantidadTotal = $pedido->prendas()->sum('cantidad');
            $pedido->update(['cantidad_total' => $cantidadTotal]);
        }
    }

    /**
     * MEJORADO: Obtener datos completos de factura de un pedido
     * 
     * Incluye:
     * - Manga con nombre (desde tipos_manga)
     * - Broche con nombre (desde tipos_broche_boton)
     * - Bolsillos (boolean y observaciones)
     * - Todas las observaciones (manga_obs, broche_boton_obs, bolsillos_obs)
     * - Prendas con colores, telas e imágenes
     * - Procesos con imágenes
     */
    public function obtenerDatosFactura(int $pedidoId): array
    {
        try {
            $pedido = $this->obtenerPorId($pedidoId);
            
            if (!$pedido) {
                \Log::warning('[FACTURA] Pedido no encontrado', ['pedido_id' => $pedidoId]);
                throw new \Exception('Pedido no encontrado');
            }

            // Determinar la fecha de creación (priorizar fecha_de_creacion_de_orden, fallback a created_at)
            $fechaCreacion = $pedido->fecha_de_creacion_de_orden 
                ? (is_string($pedido->fecha_de_creacion_de_orden) 
                    ? $pedido->fecha_de_creacion_de_orden 
                    : $pedido->fecha_de_creacion_de_orden->format('d/m/Y'))
                : ($pedido->created_at 
                    ? $pedido->created_at->format('d/m/Y')
                    : date('d/m/Y'));

            // Construir datos base
            $datos = [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
                'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
                'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
                'asesora' => is_object($pedido->asesora) ? $pedido->asesora->name : ($pedido->asesora ?? 'Sin asignar'),
                'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
                'fecha' => $fechaCreacion,
                'fecha_creacion' => $fechaCreacion,
                'observaciones' => $pedido->observaciones ?? '',
                'prendas' => [],
                'total_items' => 0,
            ];

            // Procesar prendas
            foreach ($pedido->prendas as $prenda) {
                $cantidadTotal = 0;
                $variantes_formateadas = [];

                // Obtener especificaciones de la PRIMERA variante (manga, broche, bolsillos son globales por prenda)
                $especificaciones = [
                    'manga' => null,
                    'manga_obs' => '',
                    'broche' => null,
                    'broche_obs' => '',
                    'bolsillos' => false,
                    'bolsillos_obs' => '',
                ];
                
                if ($prenda->variantes && $prenda->variantes->count() > 0) {
                    $primeraVariante = $prenda->variantes->first();
                    
                    // OBTENER MANGA CON NOMBRE - DESDE RELACIÓN PRECARGADA
                    if ($primeraVariante->tipoManga) {
                        $especificaciones['manga'] = $primeraVariante->tipoManga->nombre;
                    }
                    
                    // OBTENER BROCHE CON NOMBRE - DESDE RELACIÓN PRECARGADA
                    if ($primeraVariante->tipoBroche) {
                        $especificaciones['broche'] = $primeraVariante->tipoBroche->nombre;
                    }
                    
                    // OBTENER ESPECIFICACIONES DE BOLSILLOS Y OBSERVACIONES
                    $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
                    $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
                    $especificaciones['bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
                    $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';
                }

                // Procesar TALLAS (no variantes) - Cada talla es una fila en la factura
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        $cantidadTotal += $talla->cantidad ?? 0;
                        
                        // CREAR ITEM DE TALLA CON ESPECIFICACIONES
                        $talla_item = [
                            'talla' => $talla->talla ?? '',
                            'cantidad' => $talla->cantidad ?? 0,
                            'manga' => $especificaciones['manga'],
                            'manga_obs' => $especificaciones['manga_obs'],
                            'broche' => $especificaciones['broche'],
                            'broche_obs' => $especificaciones['broche_obs'],
                            'bolsillos' => $especificaciones['bolsillos'],
                            'bolsillos_obs' => $especificaciones['bolsillos_obs'],
                        ];
                        
                        $variantes_formateadas[] = $talla_item;
                    }
                }

                // Extraer telas y colores de la relación coloresTelas (TABLA INTERMEDIA)
                $colores = [];
                $telas = [];
                $referencias = [];
                $telasArray = [];
                
                if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                    foreach ($prenda->coloresTelas as $colorTela) {
                        // Obtener color
                        if ($colorTela->color) {
                            $colorNombre = $colorTela->color->nombre ?? null;
                            if ($colorNombre && !in_array($colorNombre, $colores)) {
                                $colores[] = $colorNombre;
                            }
                        }
                        
                        // Obtener tela y referencia
                        if ($colorTela->tela) {
                            $telaNombre = $colorTela->tela->nombre ?? null;
                            $telaReferencia = $colorTela->referencia ?? null;
                            
                            if ($telaNombre && !in_array($telaNombre, $telas)) {
                                $telas[] = $telaNombre;
                            }
                            
                            if ($telaReferencia && !in_array($telaReferencia, $referencias)) {
                                $referencias[] = $telaReferencia;
                            }
                            
                            // Procesar fotos de esta combinación color-tela
                            $fotosColorTela = [];
                            if ($colorTela->fotos && $colorTela->fotos->count() > 0) {
                                foreach ($colorTela->fotos as $foto) {
                                    $fotosColorTela[] = [
                                        'id' => $foto->id,
                                        'url' => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original ?? $foto->url),
                                        'ruta' => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original ?? $foto->url),
                                        'ruta_original' => $this->normalizarRutaImagen($foto->ruta_original ?? $foto->url),
                                        'ruta_webp' => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->url),
                                    ];
                                }
                            }
                            
                            // Agregar a array estructurado
                            $telaItem = [
                                'id' => $colorTela->id,
                                'tela_id' => $colorTela->tela_id,
                                'color_id' => $colorTela->color_id,
                                'nombre' => $telaNombre,
                                'tela_nombre' => $telaNombre,
                                'referencia' => $telaReferencia,
                                'tela_referencia' => $telaReferencia,
                                'color' => $colorTela->color ? ($colorTela->color->nombre ?? null) : null,
                                'color_nombre' => $colorTela->color ? ($colorTela->color->nombre ?? null) : null,
                                'color_codigo' => $colorTela->color ? ($colorTela->color->codigo ?? null) : null,
                                'fotos' => $fotosColorTela,
                                'fotos_tela' => $fotosColorTela,
                                'imagenes' => $fotosColorTela,
                            ];
                            
                            // Buscar si ya existe esta tela en el array
                            $telaExistente = false;
                            foreach ($telasArray as $key => $item) {
                                if ($item['tela_id'] == $colorTela->tela_id && $item['color_id'] == $colorTela->color_id) {
                                    $telaExistente = true;
                                    break;
                                }
                            }
                            
                            if (!$telaExistente) {
                                $telasArray[] = $telaItem;
                            }
                        }
                    }
                }

                // Obtener foto principal de prenda
                $foto = null;
                $fotosPrend = [];
                
                // Obtener fotos de prenda primero
                if ($prenda->fotos && $prenda->fotos->count() > 0) {
                    $foto = $prenda->fotos->first();
                }
                
                // Si no hay fotos de prenda, NO usar fotos de proceso como fallback
                // (Se dejará null la imagen principal de prenda)
                
                // Array simple de todas las fotos de tela para compatibilidad (fallback)
                $fotoTelas = [];
                if ($prenda->fotosTelas) {
                    $fotoTelas = $prenda->fotosTelas->map(fn($f) => [
                        'id' => $f->id,
                        'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
                        'ruta' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
                        'ruta_original' => $this->normalizarRutaImagen($f->ruta_original ?? $f->url),
                        'ruta_webp' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                    ])->toArray();
                }
                
                // Obtener todas las fotos de prenda
                $fotosPrend = [];
                try {
                    if ($prenda->fotos) {
                        $fotosPrend = $prenda->fotos->map(fn($f) => [
                            'id' => $f->id,
                            'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                            'ruta' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                            'ruta_original' => $this->normalizarRutaImagen($f->ruta_original ?? $f->url),
                            'ruta_webp' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                        ])->toArray();
                    }
                } catch (\Exception $e) {
                    \Log::debug('[FACTURA] Error fotos prenda', ['error' => $e->getMessage()]);
                }
                
                // Construir array simple de tallas agrupado por género: {DAMA: {M: 20, S: 10}, CABALLERO: {...}}
                $tallasSimples = [];
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $t) {
                        $genero = $t->genero ?? 'GENERAL';
                        if (!isset($tallasSimples[$genero])) {
                            $tallasSimples[$genero] = [];
                        }
                        $tallasSimples[$genero][$t->talla] = (int)$t->cantidad;
                    }
                }
                
                // Obtener procesos con imágenes
                $procesos = [];
                try {
                    if ($prenda->procesos) {
                        foreach ($prenda->procesos as $proc) {
                            // Construir tallas del proceso desde la relación (FUENTE CANÓNICA)
                            $procTallas = [
                                'dama' => [],
                                'caballero' => [],
                                'unisex' => []
                            ];
                            
                            // Obtener desde pedidos_procesos_prenda_tallas
                            if ($proc->tallas && $proc->tallas->count() > 0) {
                                foreach ($proc->tallas as $tallaProceso) {
                                    $genero = strtolower($tallaProceso->genero ?? 'dama');
                                    if (!isset($procTallas[$genero])) {
                                        $procTallas[$genero] = [];
                                    }
                                    $procTallas[$genero][$tallaProceso->talla] = (int)$tallaProceso->cantidad;
                                }
                            }
                            
                            // Ubicaciones puede ser array o string JSON
                            $ubicaciones = [];
                            if ($proc->ubicaciones) {
                                if (is_array($proc->ubicaciones)) {
                                    $ubicaciones = $proc->ubicaciones;
                                } else if (is_string($proc->ubicaciones)) {
                                    $ubicaciones = json_decode($proc->ubicaciones, true) ?? [];
                                }
                            }
                            
                            // Obtener imágenes del proceso - Usar relación precargada
                            $imagenesProceso = [];
                            if ($proc->imagenes && $proc->imagenes->count() > 0) {
                                $imagenesProceso = $proc->imagenes->map(function($img) {
                                    $url = $img->ruta_webp ?? $img->url ?? $img->ruta_original ?? '';
                                    return $this->normalizarRutaImagen($url);
                                })->toArray();
                            }
                            
                            // Obtener nombre del tipo de proceso
                            $nombreProceso = 'Proceso';
                            if ($proc->tipoProceso && $proc->tipoProceso->nombre) {
                                $nombreProceso = $proc->tipoProceso->nombre;
                            }
                            
                            $procesos[] = [
                                'id' => $proc->id,
                                'nombre' => $nombreProceso,
                                'tipo' => $nombreProceso,
                                'nombre_proceso' => $nombreProceso,
                                'tipo_proceso' => $nombreProceso,
                                'tallas' => $procTallas,
                                'observaciones' => $proc->observaciones ?? '',
                                'ubicaciones' => $ubicaciones,
                                'imagenes' => $imagenesProceso,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('[FACTURA] Error procesos', ['error' => $e->getMessage()]);
                }
                
                // Construir prenda formateada CON TODAS LAS ESPECIFICACIONES
                $prendasFormato = [
                    'id' => $prenda->id,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => (bool)($prenda->de_bodega ?? false),
                    'imagen' => $prenda->fotos && $prenda->fotos->count() > 0 ? ($prenda->fotos->first()->url ?? $prenda->fotos->first()->ruta_webp) : null,
                    'imagen_tela' => $prenda->fotosTelas && $prenda->fotosTelas->count() > 0 ? [
                        'id' => $prenda->fotosTelas->first()->id,
                        'url' => $this->normalizarRutaImagen($prenda->fotosTelas->first()->ruta_webp ?? $prenda->fotosTelas->first()->ruta_original ?? $prenda->fotosTelas->first()->url),
                    ] : null,
                    'imagenes' => $prenda->fotos ? $prenda->fotos->map(fn($f) => [
                        'id' => $f->id,
                        'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                    ])->toArray() : [],
                    'imagenes_tela' => $prenda->fotosTelas ? $prenda->fotosTelas->map(fn($f) => [
                        'id' => $f->id,
                        'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
                    ])->toArray() : [],
                    'tela' => !empty($telas) ? implode(', ', $telas) : null,
                    'color' => !empty($colores) ? implode(', ', $colores) : null,
                    'ref' => !empty($referencias) ? implode(', ', $referencias) : null,
                    'telas_array' => $telasArray,
                    'colores_array' => $colores,
                    'referencias_array' => $referencias,
                    'tallas' => [],
                    'variantes' => $variantes_formateadas,
                    'procesos' => $procesos,
                ];
                
                // Construir tallas agrupadas por género
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $t) {
                        $genero = $t->genero ?? 'GENERAL';
                        if (!isset($prendasFormato['tallas'][$genero])) {
                            $prendasFormato['tallas'][$genero] = [];
                        }
                        $prendasFormato['tallas'][$genero][$t->talla] = (int)$t->cantidad;
                    }
                }
                
                $datos['prendas'][] = $prendasFormato;
                $datos['total_items'] += $cantidadTotal;
            }

            // AGREGAR EPPs A LOS DATOS DE FACTURA CON VALIDACIÓN DEFENSIVA
            $datos['epps'] = [];
            try {
                if ($pedido->epps) {
                    foreach ($pedido->epps as $pedidoEpp) {
                        // VALIDACIÓN: Verificar que el EPP tenga relación válida
                        $epp = $pedidoEpp->epp;
                        
                        if (!$epp) {
                            continue;
                        }
                        
                        // Obtener la talla de tallas_medidas
                        $talla = '';
                        if ($pedidoEpp->tallas_medidas) {
                            if (is_array($pedidoEpp->tallas_medidas)) {
                                $talla = implode(', ', $pedidoEpp->tallas_medidas);
                            } else if (is_string($pedidoEpp->tallas_medidas)) {
                                $talla = $pedidoEpp->tallas_medidas;
                            }
                        }
                        
                        // Construir datos del EPP con valores seguros
                        $eppFormato = [
                            'id' => $pedidoEpp->id,
                            'epp_id' => $pedidoEpp->epp_id,
                            'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'talla' => $talla,
                            'cantidad' => $pedidoEpp->cantidad ?? 0,
                            'observaciones' => $pedidoEpp->observaciones ?? '',
                            'imagen' => null,
                            'imagenes' => [],
                        ];
                        
                        // Obtener imágenes del PedidoEpp desde la tabla pedido_epp_imagenes
                        try {
                            $imagenesData = \DB::table('pedido_epp_imagenes')
                                ->where('pedido_epp_id', $pedidoEpp->id)
                                ->orderBy('orden', 'asc')
                                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                            
                            if ($imagenesData->count() > 0) {
                                $imagenes = $imagenesData->map(fn($img) => [
                                    'ruta_webp' => $this->normalizarRutaImagen($img->ruta_web ?? $img->ruta_original),
                                    'ruta_original' => $this->normalizarRutaImagen($img->ruta_original),
                                    'ruta_web' => $this->normalizarRutaImagen($img->ruta_web ?? $img->ruta_original),
                                    'principal' => $img->principal ?? false,
                                    'orden' => $img->orden ?? 0,
                                ])->toArray();
                                
                                $eppFormato['imagenes'] = $imagenes;
                                $eppFormato['imagen'] = $imagenes[0] ?? null;
                            }
                        } catch (\Exception $e) {
                            \Log::warning('[FACTURA] Error obteniendo imágenes de EPP', ['pedido_epp_id' => $pedidoEpp->id]);
                        }
                        
                        $datos['epps'][] = $eppFormato;
                        $datos['total_items'] += ($pedidoEpp->cantidad ?? 0);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[FACTURA] Error procesando EPPs', ['pedido_id' => $pedidoId, 'error' => $e->getMessage()]);
            }

            return $datos;
        } catch (\Exception $e) {
            \Log::error('[FACTURA] Error en obtenerDatosFactura', ['pedido_id' => $pedidoId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener datos para los recibos dinÃ¡micos
     * Formato especÃ­fico para ReceiptManager en receipt-dynamic.blade.php
     */
    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array
    {
        $pedido = $this->obtenerPorId($pedidoId);
        
        if (!$pedido) {
            throw new \Exception('Pedido no encontrado');
        }

        // Construir datos base iguales a factura
        $datos = [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'asesor' => is_object($pedido->asesora) ? $pedido->asesora->name : ($pedido->asesora ?? 'Sin asignar'),
            'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
            'fecha' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'fecha_creacion' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'observaciones' => $pedido->observaciones ?? '',
            'prendas' => [],
            'epps' => [],
        ];

        // Procesar prendas para recibos
        foreach ($pedido->prendas as $prendaIndex => $prenda) {
            $cantidadTotal = 0;
            $colores = [];
            $telas = [];
            $referencias = [];
            $especificaciones = [];
            $imagenesPrenda = [];
            $imagenesTela = [];

            // Obtener imágenes de prenda desde prenda_fotos_pedido
            try {
                $fotosPrenda = \DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('ruta_webp')
                    ->get();
                
                $imagenesPrenda = $fotosPrenda->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp))->toArray();
            } catch (\Exception $e) {
                // Error obteniendo imágenes de prenda
            }

            // Obtener tela, color, referencia e imÃ¡genes desde prenda_pedido_colores_telas
            try {
                $colorTelaData = \DB::table('prenda_pedido_colores_telas')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                    ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                    ->select(
                        'prenda_pedido_colores_telas.id as color_tela_id',
                        'colores_prenda.nombre as color_nombre',
                        'telas_prenda.nombre as tela_nombre',
                        'prenda_pedido_colores_telas.referencia'
                    )
                    ->first();
                
                if ($colorTelaData) {
                    if ($colorTelaData->color_nombre && !in_array($colorTelaData->color_nombre, $colores)) {
                        $colores[] = $colorTelaData->color_nombre;
                    }
                    if ($colorTelaData->tela_nombre && !in_array($colorTelaData->tela_nombre, $telas)) {
                        $telas[] = $colorTelaData->tela_nombre;
                    }
                    if ($colorTelaData->referencia && !in_array($colorTelaData->referencia, $referencias)) {
                        $referencias[] = $colorTelaData->referencia;
                    }
                    
                    // Obtener imágenes de tela desde prenda_fotos_tela_pedido
                    $fotos = \DB::table('prenda_fotos_tela_pedido')
                        ->where('prenda_pedido_colores_telas_id', $colorTelaData->color_tela_id)
                        ->where('deleted_at', null)
                        ->orderBy('orden')
                        ->select('ruta_webp')
                        ->get();
                    
                    $imagenesTela = $fotos->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp))->toArray();
                }
            } catch (\Exception $e) {
                // Error obteniendo tela/color datos
            }

            // Procesar variantes
            foreach ($prenda->variantes as $variante) {
                $cantidadTotal += $variante->cantidad ?? 0;
                
                $spec = [
                    'talla' => $variante->talla ?? '',
                    'cantidad' => $variante->cantidad ?? 0,
                    'manga' => null,
                    'manga_obs' => $variante->manga_obs ?? '',
                    'broche' => null,
                    'broche_obs' => $variante->broche_boton_obs ?? '',
                    'bolsillos' => $variante->tiene_bolsillos ?? false,
                    'bolsillos_obs' => $variante->bolsillos_obs ?? '',
                ];
                
                // Obtener tipo de manga
                if ($variante->tipo_manga_id) {
                    try {
                        $manga = \DB::table('tipos_manga')
                            ->where('id', $variante->tipo_manga_id)
                            ->value('nombre');
                        $spec['manga'] = $manga;
                    } catch (\Exception $e) {
                        // Error obteniendo tipo de manga
                    }
                }
                
                // Obtener tipo de broche/botón
                if ($variante->tipo_broche_boton_id) {
                    try {
                        $broche = \DB::table('tipos_broche_boton')
                            ->where('id', $variante->tipo_broche_boton_id)
                            ->value('nombre');
                        $spec['broche'] = $broche;
                    } catch (\Exception $e) {
                        // Error obteniendo tipo de broche
                    }
                }
                
                $especificaciones[] = $spec;
            }

            // Tallas desde tabla relacional (prenda_pedido_tallas)
            $tallas = $this->obtenerTallas($prenda->id);

            // Procesar procesos
            //  CRÍTICO: Si el pedido está en estado PENDIENTE, NO incluir procesos
            $procesos = [];
            
            foreach ($prenda->procesos as $proc) {
                //  CRÍTICO: Si está configurado para filtrar y el proceso está PENDIENTE, omitir
                if ($filtrarProcesosPendientes && $proc->estado === 'PENDIENTE') {
                    continue; // Saltar este proceso
                }
                
                // Cargar tallas desde tallas_dama y tallas_caballero
                $procTallas = [
                    'dama' => [],
                    'caballero' => [],
                    'unisex' => []
                ];
                
                // Procesar tallas DESDE LA TABLA RELACIONAL
                $tallasRelacionales = \App\Models\PedidosProcesosPrendaTalla::where(
                    'proceso_prenda_detalle_id', 
                    $proc->id
                )->get();
                
                foreach ($tallasRelacionales as $tallaRecord) {
                    $genero = strtolower($tallaRecord->genero);
                    if ($tallaRecord->cantidad > 0) {
                        $procTallas[$genero][$tallaRecord->talla] = $tallaRecord->cantidad;
                    }
                }
                
                // Ubicaciones
                $ubicaciones = [];
                if ($proc->ubicaciones) {
                    if (is_array($proc->ubicaciones)) {
                        $ubicaciones = $proc->ubicaciones;
                    } else if (is_string($proc->ubicaciones)) {
                        $ubicaciones = json_decode($proc->ubicaciones, true) ?? [];
                    }
                }
                
                // ImÃ¡genes del proceso
                $imagenesProceso = $proc->imagenes ? $proc->imagenes->map(fn($img) => $img->url)->toArray() : [];
                
                //  Obtener nombre del tipo de proceso (desde la relación cargada)
                $nombreProceso = 'Proceso';
                if ($proc->tipoProceso && $proc->tipoProceso->nombre) {
                    $nombreProceso = $proc->tipoProceso->nombre;
                }
                
                $proc_item = [
                    // Campos compatibles con frontend (ReceiptManager.js busca estos)
                    'nombre' => $nombreProceso,
                    'tipo' => $nombreProceso,
                    // Campos originales (compatibilidad backwards)
                    'nombre_proceso' => $nombreProceso,
                    'tipo_proceso' => $nombreProceso,
                    // Datos del proceso
                    'tallas' => $procTallas,
                    'observaciones' => $proc->observaciones ?? '',
                    'ubicaciones' => $ubicaciones,
                    'imagenes' => $imagenesProceso,
                    'estado' => $proc->estado ?? 'Pendiente',
                ];
                
                $procesos[] = $proc_item;
            }

            // Preparar telas agregadas (estructura para edición)
            $telasAgregadas = [];
            try {
                $colorTelaData = \DB::table('prenda_pedido_colores_telas')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                    ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                    ->select(
                        'prenda_pedido_colores_telas.id as color_tela_id',
                        'colores_prenda.nombre as color_nombre',
                        'telas_prenda.nombre as tela_nombre',
                        'prenda_pedido_colores_telas.referencia'
                    )
                    ->first();
                
                if ($colorTelaData) {
                    // Obtener imágenes de tela desde prenda_fotos_tela_pedido
                    $fotosTelaDB = \DB::table('prenda_fotos_tela_pedido')
                        ->where('prenda_pedido_colores_telas_id', $colorTelaData->color_tela_id)
                        ->where('deleted_at', null)
                        ->orderBy('orden')
                        ->select('ruta_webp', 'ruta_original')
                        ->get();
                    
                    $imagenesTelaFormato = $fotosTelaDB->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original))->toArray();
                    
                    $telasAgregadas[] = [
                        'tela' => $colorTelaData->tela_nombre,
                        'color' => $colorTelaData->color_nombre,
                        'referencia' => $colorTelaData->referencia ?? '',
                        'imagenes' => $imagenesTelaFormato
                    ];
                }
            } catch (\Exception $e) {
                // Error preparando telas agregadas
            }

            // Preparar generosConTallas desde tallas (estructura: {dama: {L: 20, M: 20, S: 20}})
            $generosConTallas = [];
            if (!empty($tallas) && is_array($tallas)) {
                foreach ($tallas as $genero => $tallasCant) {
                    if (is_array($tallasCant)) {
                        // Detectar tipo de talla (letra o nÃºmero)
                        $tipo = null;
                        $primerasTallas = array_keys($tallasCant);
                        if (!empty($primerasTallas)) {
                            $primeraTalla = $primerasTallas[0];
                            // Si es letra (S, M, L, XL, etc.)
                            if (strlen($primeraTalla) <= 3 && !is_numeric($primeraTalla)) {
                                $tipo = 'letra';
                            }
                            // Si es nÃºmero (34, 36, 38, etc.)
                            else if (is_numeric($primeraTalla)) {
                                $tipo = 'numero';
                            }
                        }
                        
                        $generosConTallas[$genero] = [
                            'tallas' => array_keys($tallasCant),
                            'tipo' => $tipo,
                            'cantidades' => $tallasCant  // Incluir cantidades: {L: 20, M: 20, S: 20}
                        ];
                    }
                }
            }

            // Preparar datos de variaciones desde primera variante
            $obsVariaciones = [
                'obs_manga' => '',
                'obs_bolsillos' => '',
                'obs_broche' => '',
                'obs_reflectivo' => '',
                'tipo_manga' => '',
                'tipo_broche' => ''
            ];
            
            // Extraer desde la primera variante (siempre tiene los datos)
            if (!empty($prenda->variantes) && count($prenda->variantes) > 0) {
                $primerVariante = $prenda->variantes[0];
                $obsVariaciones['obs_manga'] = $primerVariante->manga_obs ?? '';
                $obsVariaciones['obs_bolsillos'] = $primerVariante->bolsillos_obs ?? '';
                $obsVariaciones['obs_broche'] = $primerVariante->broche_boton_obs ?? '';
                // reflectivo_obs no existe en el modelo, usar tiene_reflectivo como indicador
                $obsVariaciones['obs_reflectivo'] = $prenda->tiene_reflectivo ? 'SÃ­' : '';
                
                // Obtener nombre del tipo de manga
                if ($primerVariante->tipoManga && $primerVariante->tipoManga->nombre) {
                    $obsVariaciones['tipo_manga'] = $primerVariante->tipoManga->nombre;
                }
                
                // Obtener nombre del tipo de broche/botón - siempre cargar desde BD usando tipo_broche_boton_id
                if ($primerVariante->tipo_broche_boton_id) {
                    $tipoBroche = \DB::table('tipos_broche_boton')
                        ->where('id', $primerVariante->tipo_broche_boton_id)
                        ->value('nombre');
                    if ($tipoBroche) {
                        $obsVariaciones['tipo_broche'] = $tipoBroche;
                    }
                }
            }

            // Construir prenda para recibos
            // Determinar origen desde de_bodega: 1 = bodega, 0 = confección
            $origen = ($prenda->de_bodega == 1) ? 'bodega' : 'confección';
            
            // Obtener tipo_broche_boton_id desde la primera variante
            $tipo_broche_boton_id = null;
            if (!empty($prenda->variantes) && count($prenda->variantes) > 0) {
                $tipo_broche_boton_id = $prenda->variantes[0]->tipo_broche_boton_id ?? null;
            }
            
            $prendasFormato = [
                'id' => $prenda->id,
                'prenda_pedido_id' => $prenda->id,
                'numero' => $prendaIndex + 1,
                'nombre_prenda' => $prenda->nombre_prenda,
                'nombre' => $prenda->nombre_prenda,
                'origen' => $origen,
                'descripcion' => $prenda->descripcion,
                'tela' => !empty($telas) ? implode(', ', $telas) : null,
                'color' => !empty($colores) ? implode(', ', $colores) : null,
                'ref' => !empty($referencias) ? implode(', ', $referencias) : null,
                'tallas' => $tallas,
                'generosConTallas' => $generosConTallas,
                'telasAgregadas' => $telasAgregadas,
                'variantes' => $especificaciones,
                'de_bodega' => (bool)($prenda->de_bodega ?? false),
                'procesos' => $procesos,
                'imagenes' => $imagenesPrenda,
                'imagenes_tela' => $imagenesTela,
                'fotos_tela' => $imagenesTela,
                'obs_manga' => $obsVariaciones['obs_manga'],
                'obs_bolsillos' => $obsVariaciones['obs_bolsillos'],
                'obs_broche' => $obsVariaciones['obs_broche'],
                'obs_reflectivo' => $obsVariaciones['obs_reflectivo'],
                'tipo_manga' => $obsVariaciones['tipo_manga'],
                'tipo_broche' => $obsVariaciones['tipo_broche'],
                'tipo_broche_boton_id' => $tipo_broche_boton_id,
                'tiene_bolsillos' => $prenda->tiene_bolsillos ?? false,
                'tiene_reflectivo' => $prenda->tiene_reflectivo ?? false,
            ];

            $datos['prendas'][] = $prendasFormato;
        }

        // Cargar EPP del pedido
        try {
            $epps = $pedido->epps()->get();
            
            foreach ($epps as $pedidoEpp) {
                $eppFormato = [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'nombre' => $pedidoEpp->epp->nombre_completo ?? '',
                    'nombre_completo' => $pedidoEpp->epp->nombre_completo ?? '',
                    'epp_nombre' => $pedidoEpp->epp->nombre_completo ?? '',
                    'epp_categoria' => $pedidoEpp->epp->categoria->nombre ?? '',
                    'cantidad' => $pedidoEpp->cantidad ?? 0,
                    'observaciones' => $pedidoEpp->observaciones ?? '',
                    'imagenes' => [],
                    'imagen' => null,
                ];
                
                // Obtener imÃ¡genes del PedidoEpp desde la tabla pedido_epp_imagenes
                try {
                    $imagenesData = \DB::table('pedido_epp_imagenes')
                        ->where('pedido_epp_id', $pedidoEpp->id)
                        ->orderBy('orden', 'asc')
                        ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                    
                    if ($imagenesData->count() > 0) {
                        $imagenes = $imagenesData->pluck('ruta_web')->filter()->toArray();
                        $eppFormato['imagenes'] = $imagenes;
                        $eppFormato['imagen'] = $imagenes[0] ?? null;

                    }
                } catch (\Exception $e) {
                    \Log::error('[RECIBOS-REPO] Error obteniendo imÃ¡genes de EPP:', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $datos['epps'][] = $eppFormato;
            }
        } catch (\Exception $e) {
            \Log::error('[RECIBOS-REPO] Error cargando EPP: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        // ===== SUPER DEBUG: Verificar EXACTAMENTE si procesos está en la respuesta final =====
        $detalleDebug = [];
        if (count($datos['prendas'] ?? []) > 0) {
            $primeraPrenda = $datos['prendas'][0];
            $detalleDebug = [
                'nombre_prenda' => $primeraPrenda['nombre'] ?? 'SIN_NOMBRE',
                'tiene_procesos_key' => isset($primeraPrenda['procesos']) ? 'SI' : 'NO',
                'procesos_es_null' => $primeraPrenda['procesos'] === null ? 'SI' : 'NO',
                'procesos_es_array' => is_array($primeraPrenda['procesos']) ? 'SI' : 'NO',
                'procesos_count' => is_array($primeraPrenda['procesos']) ? count($primeraPrenda['procesos']) : 'N/A',
                'procesos_primero' => is_array($primeraPrenda['procesos']) && count($primeraPrenda['procesos']) > 0 ? [
                    'nombre' => $primeraPrenda['procesos'][0]['nombre'] ?? 'SIN_NOMBRE',
                    'tipo' => $primeraPrenda['procesos'][0]['tipo'] ?? 'SIN_TIPO',
                    'nombre_proceso' => $primeraPrenda['procesos'][0]['nombre_proceso'] ?? 'SIN_NOMBRE',
                    'tipo_proceso' => $primeraPrenda['procesos'][0]['tipo_proceso'] ?? 'SIN_TIPO',
                ] : null,
            ];
        }

        return $datos;
    }

    /**
     * Normalizar ruta de imagen para asegurar que comience con /storage/
     * Convierte rutas relativas en rutas absolutas con prefijo /storage/
     */
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        // Reemplazar backslashes con forward slashes
        $ruta = str_replace('\\', '/', $ruta);

        // Si ya comienza con /storage/, devolverla tal cual
        if (strpos($ruta, '/storage/') === 0) {
            return $ruta;
        }

        // Si comienza con storage/ (sin /), agregar / al inicio
        if (strpos($ruta, 'storage/') === 0) {
            return '/' . $ruta;
        }

        // Si no comienza con /, agregar /storage/
        if (strpos($ruta, '/') !== 0) {
            return '/storage/' . $ruta;
        }

        // Si comienza con / pero no es /storage/, agregar storage antes de la ruta
        return '/storage' . $ruta;
    }
}

