<?php

namespace App\Domain\Pedidos\Repositories;

use App\Domain\Pedidos\Traits\GestionaTallasRelacional;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositorio para acceso a datos de Pedidos de ProducciÃ³n
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
            'prendas.tallas',  // NUEVA: Cargar tallas relacionales
            'prendas.procesos',
            'prendas.procesos.tipoProceso',  //  NUEVO: Cargar el nombre del tipo de proceso
            'prendas.procesos.imagenes',
            'epps.epp.categoria',  //  Cargar la categorÃ­a del EPP
            'epps.imagenes',
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
            ->where('asesor_id', Auth::id())
            ->with(['cotizacion', 'prendas']);

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
     * ✅ Incluye:
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

            // Construir datos base
            $datos = [
                'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
                'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
                'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
                'asesora' => is_object($pedido->asesora) ? $pedido->asesora->name : ($pedido->asesora ?? 'Sin asignar'),
                'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
                'fecha' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
                'fecha_creacion' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
                'observaciones' => $pedido->observaciones ?? '',
                'prendas' => [],
                'total_items' => 0,
            ];

            // Procesar prendas
            foreach ($pedido->prendas as $prenda) {
                \Log::info('[FACTURA] Procesando prenda', ['prenda_id' => $prenda->id, 'nombre' => $prenda->nombre_prenda]);
                
                $cantidadTotal = 0;
                $colores = [];
                $telas = [];
                $referencias = [];
                $variantes_formateadas = [];

                // ✅ Obtener especificaciones de la PRIMERA variante (manga, broche, bolsillos son globales por prenda)
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
                    
                    // ✅ OBTENER MANGA CON NOMBRE
                    if ($primeraVariante->tipo_manga_id) {
                        try {
                            $manga = \DB::table('tipos_manga')
                                ->where('id', $primeraVariante->tipo_manga_id)
                                ->value('nombre');
                            $especificaciones['manga'] = $manga;
                            \Log::debug('[FACTURA] Manga obtenida', [
                                'tipo_manga_id' => $primeraVariante->tipo_manga_id,
                                'manga_nombre' => $manga
                            ]);
                        } catch (\Exception $e) {
                            \Log::debug('[FACTURA] Error manga', ['error' => $e->getMessage()]);
                        }
                    }
                    
                    // ✅ OBTENER BROCHE CON NOMBRE
                    if ($primeraVariante->tipo_broche_boton_id) {
                        try {
                            $broche = \DB::table('tipos_broche_boton')
                                ->where('id', $primeraVariante->tipo_broche_boton_id)
                                ->value('nombre');
                            $especificaciones['broche'] = $broche;
                        } catch (\Exception $e) {
                            \Log::debug('[FACTURA] Error broche', ['error' => $e->getMessage()]);
                        }
                    }
                    
                    // ✅ OBTENER ESPECIFICACIONES DE BOLSILLOS Y OBSERVACIONES
                    $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
                    $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
                    $especificaciones['bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
                    $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';
                }

                // ✅ Procesar TALLAS (no variantes) - Cada talla es una fila en la factura
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        $cantidadTotal += $talla->cantidad ?? 0;
                        
                        // ✅ CREAR ITEM DE TALLA CON ESPECIFICACIONES
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
                        
                        \Log::debug('[FACTURA] Talla formateada', [
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'manga' => $especificaciones['manga'],
                            'broche' => $especificaciones['broche'],
                            'bolsillos' => $especificaciones['bolsillos'],
                        ]);
                    }
                }

                // ✅ Extraer telas y colores de variantes (si existen en colores/telas)
                if ($prenda->variantes) {
                    foreach ($prenda->variantes as $variante) {
                        // ✅ Extraer telas y colores de variante si existen
                        if ($variante->tela_id) {
                            try {
                                $telaData = \DB::table('telas_prenda')
                                    ->where('id', $variante->tela_id)
                                    ->select('nombre', 'referencia')
                                    ->first();
                                
                                if ($telaData) {
                                    if ($telaData->nombre && !in_array($telaData->nombre, $telas)) {
                                        $telas[] = $telaData->nombre;
                                    }
                                    if ($telaData->referencia && !in_array($telaData->referencia, $referencias)) {
                                        $referencias[] = $telaData->referencia;
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::debug('[FACTURA] Error tela', ['error' => $e->getMessage()]);
                            }
                        }
                        
                        if ($variante->color_id) {
                            try {
                                $color = \DB::table('colores_prenda')
                                    ->where('id', $variante->color_id)
                                    ->value('nombre');
                                if ($color && !in_array($color, $colores)) {
                                    $colores[] = $color;
                                }
                            } catch (\Exception $e) {
                                \Log::debug('[FACTURA] Error color', ['error' => $e->getMessage()]);
                            }
                        }
                    }
                }

                // ✅ Obtener foto principal de prenda
                $foto = $prenda->fotos ? $prenda->fotos->first() : null;
                
                // ✅ Obtener fotos de telas
                $fotoTelas = [];
                try {
                    if ($prenda->fotosTelas) {
                        $fotoTelas = $prenda->fotosTelas->map(fn($f) => [
                            'id' => $f->id,
                            'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
                            'ruta' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
                            'ruta_original' => $this->normalizarRutaImagen($f->ruta_original ?? $f->url),
                            'ruta_webp' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
                        ])->toArray();
                    }
                } catch (\Exception $e) {
                    \Log::debug('[FACTURA] Error fotos telas', ['error' => $e->getMessage()]);
                }
                
                // ✅ Obtener todas las fotos de prenda
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
                
                // ✅ Construir array simple de tallas agrupado por género: {DAMA: {M: 20, S: 10}, CABALLERO: {...}}
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
                
                // ✅ Obtener procesos con imágenes
                $procesos = [];
                try {
                    if ($prenda->procesos) {
                        foreach ($prenda->procesos as $proc) {
                            // Construir tallas del proceso
                            $procTallas = [];
                            if (is_array($proc->tallas_dama)) {
                                $procTallas['dama'] = $proc->tallas_dama;
                            }
                            if (is_array($proc->tallas_caballero)) {
                                $procTallas['caballero'] = $proc->tallas_caballero;
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
                            
                            // Obtener imágenes del proceso
                            $imagenesProceso = [];
                            if ($proc->imagenes) {
                                $imagenesProceso = $proc->imagenes->map(fn($img) => $img->url ?? $img->ruta_webp)->toArray();
                            }
                            
                            // Obtener nombre del tipo de proceso
                            $nombreProceso = 'Proceso';
                            if ($proc->tipoProceso && $proc->tipoProceso->nombre) {
                                $nombreProceso = $proc->tipoProceso->nombre;
                            }
                            
                            $procesos[] = [
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
                    \Log::debug('[FACTURA] Error procesos', ['error' => $e->getMessage()]);
                }
                
                // ✅ Construir prenda formateada CON TODAS LAS ESPECIFICACIONES
                $prendasFormato = [
                    'nombre' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'imagen' => $foto ? ($foto->url ?? $foto->ruta_webp) : null,
                    'imagen_tela' => !empty($fotoTelas) ? $fotoTelas[0] : null,
                    'imagenes' => $fotosPrend,
                    'imagenes_tela' => $fotoTelas,
                    'tela' => !empty($telas) ? implode(', ', $telas) : null,
                    'color' => !empty($colores) ? implode(', ', $colores) : null,
                    'ref' => !empty($referencias) ? implode(', ', $referencias) : null,
                    'tallas' => $tallasSimples, // ✅ SOLO TALLA Y CANTIDAD (para vistas simples)
                    'variantes' => $variantes_formateadas, // ✅ CON TODO (talla, cantidad, manga, broche, bolsillos, obs) para factura
                    'procesos' => $procesos,
                ];
                
                $datos['prendas'][] = $prendasFormato;
                $datos['total_items'] += $cantidadTotal;
                
                \Log::info('[FACTURA] Prenda procesada', [
                    'nombre' => $prenda->nombre_prenda,
                    'variantes_count' => count($variantes_formateadas),
                    'has_manga' => count(array_filter(array_column($variantes_formateadas, 'manga'))) > 0,
                    'has_broche' => count(array_filter(array_column($variantes_formateadas, 'broche'))) > 0,
                ]);
            }

            // ✅ AGREGAR EPPs A LOS DATOS DE FACTURA
            $datos['epps'] = [];
            try {
                if ($pedido->epps) {
                    foreach ($pedido->epps as $pedidoEpp) {
            // $pedidoEpp es el modelo PedidoEpp que contiene los datos del EPP agregado al pedido
            $epp = $pedidoEpp->epp;  // Obtener el modelo Epp relacionado
            
            // Obtener la talla de tallas_medidas (es un JSON que puede ser un string o array)
            $talla = '';
            if ($pedidoEpp->tallas_medidas) {
                if (is_array($pedidoEpp->tallas_medidas)) {
                    // Si es array, unir los elementos
                    $talla = implode(', ', $pedidoEpp->tallas_medidas);
                } else if (is_string($pedidoEpp->tallas_medidas)) {
                    // Si es string, usarlo directamente (ej: "S", "M", "L")
                    $talla = $pedidoEpp->tallas_medidas;
                }
            }
            
            $eppFormato = [
                'id' => $pedidoEpp->id,  // ID del registro PedidoEpp
                'epp_id' => $pedidoEpp->epp_id,  // ID del EPP
                'nombre' => $epp->nombre_completo ?? '',
                'nombre_completo' => $epp->nombre_completo ?? '',
                'categoria' => $epp->categoria?->nombre ?? $epp->categoria ?? '',  // Acceder al nombre de la categorÃ­a
                'talla' => $talla,  // Datos especÃ­ficos del pedido
                'cantidad' => $pedidoEpp->cantidad ?? 0,
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'imagen' => null,
                'imagenes' => [],
            ];
            
            // Obtener imÃ¡genes del PedidoEpp desde la tabla pedido_epp_imagenes
            try {
                $imagenesData = \DB::table('pedido_epp_imagenes')
                    ->where('pedido_epp_id', $pedidoEpp->id)
                    ->where('deleted_at', null)
                    ->orderBy('orden', 'asc')
                    ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                
                if ($imagenesData->count() > 0) {
                    $imagenes = $imagenesData->pluck('ruta_web')->filter()->toArray();
                    $eppFormato['imagenes'] = $imagenes;
                    $eppFormato['imagen'] = $imagenes[0] ?? null;
                    
                    \Log::info('[RECIBOS-REPO] ImÃ¡genes encontradas:', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'cantidad' => count($imagenes),
                        'imagenes' => $imagenes,
                        'data_completa' => $imagenesData->toArray()
                    ]);
                } else {
                    \Log::info('[RECIBOS-REPO] Sin imágenes para EPP:', [
                        'pedido_epp_id' => $pedidoEpp->id
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('[RECIBOS-REPO] Error obteniendo imágenes de EPP:', [
                    'pedido_epp_id' => $pedidoEpp->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            $datos['epps'][] = $eppFormato;
            $datos['total_items'] += ($pedidoEpp->cantidad ?? 0);
            
            \Log::info('[RECIBOS-REPO] EPP formateado:', [
                'id' => $eppFormato['id'] ?? $pedidoEpp->id,
                'nombre' => $eppFormato['nombre'],
                'cantidad' => $eppFormato['cantidad'],
                'observaciones' => $eppFormato['observaciones'],
                'imagenes_count' => count($eppFormato['imagenes']),
                'estructura_keys' => array_keys($eppFormato)
            ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[FACTURA] Error obteniendo datos', ['error' => $e->getMessage()]);
                throw $e;
            }

            return $datos;
        } catch (\Exception $e) {
            \Log::error('[FACTURA] Error fatal', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener datos para los recibos dinÃ¡micos
     * Formato especÃ­fico para ReceiptManager en receipt-dynamic.blade.php
     */
    public function obtenerDatosRecibos(int $pedidoId): array
    {
        \Log::info(' [RECIBOS-REPO] obtenerDatosRecibos() llamado con pedidoId: ' . $pedidoId);
        
        $pedido = $this->obtenerPorId($pedidoId);
        
        if (!$pedido) {
            throw new \Exception('Pedido no encontrado');
        }

        //  AGREGAR EPP A LOS DATOS DE FACTURA
        \Log::info('[RECIBOS-REPO] EPPs del pedido:', [
            'count' => $pedido->epps->count(),
            'epps' => $pedido->epps->map(fn($e) => [
                'id' => $e->id,
                'cantidad' => $e->cantidad,
                'observaciones' => $e->observaciones,
                'epp_id' => $e->epp_id,
                'epp_nombre' => $e->epp?->nombre_completo ?? 'N/A'
            ])->toArray()
        ]);

        // Construir datos base iguales a factura
        $datos = [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'asesora' => is_object($pedido->asesora) ? $pedido->asesora->name : ($pedido->asesora ?? 'Sin asignar'),
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
                \Log::info('[RECIBOS] Buscando imágenes para prenda_pedido_id: ' . $prenda->id);
                
                $fotosPrenda = \DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('ruta_webp')
                    ->get();
                
                \Log::info('[RECIBOS] Fotos encontradas para prenda ' . $prenda->id . ': ' . $fotosPrenda->count());
                
                if ($fotosPrenda->count() > 0) {
                    \Log::debug('[RECIBOS] Rutas de fotos (raw): ' . json_encode($fotosPrenda->pluck('ruta_webp')->toArray()));
                }
                
                $imagenesPrenda = $fotosPrenda->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp))->toArray();
                
                \Log::info('[RECIBOS] Imágenes procesadas para prenda ' . $prenda->id . ': ' . count($imagenesPrenda) . ' total');
                if (count($imagenesPrenda) > 0) {
                    \Log::debug('[RECIBOS] Rutas procesadas: ' . json_encode($imagenesPrenda));
                }
            } catch (\Exception $e) {
                \Log::debug('[RECIBOS] Error obteniendo imágenes de prenda: ' . $e->getMessage());
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
                        'telas_prenda.referencia'
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
                \Log::debug('[RECIBOS] Error obteniendo tela/color/imÃ¡genes desde nueva tabla: ' . $e->getMessage());
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
                        \Log::debug('[RECIBOS] Error obteniendo tipo de manga: ' . $e->getMessage());
                    }
                }
                
                // Obtener tipo de broche/botÃ³n
                if ($variante->tipo_broche_boton_id) {
                    try {
                        $broche = \DB::table('tipos_broche_boton')
                            ->where('id', $variante->tipo_broche_boton_id)
                            ->value('nombre');
                        $spec['broche'] = $broche;
                    } catch (\Exception $e) {
                        \Log::debug('[RECIBOS] Error obteniendo tipo de broche: ' . $e->getMessage());
                    }
                }
                
                $especificaciones[] = $spec;
            }

            // Tallas desde tabla relacional (prenda_pedido_tallas)
            $tallas = $this->obtenerTallas($prenda->id);
            
            \Log::info('[RECIBOS] Tallas cargadas para prenda ' . $prendaIndex, ['tallas' => $tallas]);

            // Procesar procesos
            $procesos = [];
            foreach ($prenda->procesos as $proc) {
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
                
                //  Obtener nombre del tipo de proceso (desde la relaciÃ³n cargada)
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

            // Preparar telas agregadas (estructura para ediciÃ³n)
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
                        'telas_prenda.referencia'
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
                \Log::debug('[RECIBOS] Error preparando telas agregadas: ' . $e->getMessage());
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
                
                // Obtener nombre del tipo de broche/botÃ³n - siempre cargar desde BD usando tipo_broche_boton_id
                if ($primerVariante->tipo_broche_boton_id) {
                    $tipoBroche = \DB::table('tipos_broche_boton')
                        ->where('id', $primerVariante->tipo_broche_boton_id)
                        ->value('nombre');
                    if ($tipoBroche) {
                        $obsVariaciones['tipo_broche'] = $tipoBroche;
                    }
                }
            }
            
            \Log::info('[RECIBOS] Variaciones extraÃ­das para prenda ' . $prendaIndex, ['obsVariaciones' => $obsVariaciones, 'tiene_variantes' => !empty($prenda->variantes)]);

            // Construir prenda para recibos
            // Determinar origen desde de_bodega: 1 = bodega, 0 = confecciÃ³n
            $origen = ($prenda->de_bodega == 1) ? 'bodega' : 'confecciÃ³n';
            
            // Obtener tipo_broche_boton_id desde la primera variante
            $tipo_broche_boton_id = null;
            if (!empty($prenda->variantes) && count($prenda->variantes) > 0) {
                $tipo_broche_boton_id = $prenda->variantes[0]->tipo_broche_boton_id ?? null;
            }
            \Log::info('[RECIBOS] tipo_broche_boton_id desde variantes', ['prenda_id' => $prenda->id, 'tipo_broche_boton_id' => $tipo_broche_boton_id, 'variantes_count' => count($prenda->variantes ?? [])]);
            
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
                'de_bodega' => $prenda->de_bodega ?? 0,
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
            \Log::debug('[RECIBOS-REPO] Intentando cargar EPP con relaciÃ³n epps()');
            $epps = $pedido->epps()->get();
            
            \Log::info('[RECIBOS-REPO] EPP encontrados:', ['count' => $epps->count()]);
            
            foreach ($epps as $pedidoEpp) {
                \Log::debug('[RECIBOS-REPO] Procesando EPP:', [
                    'id' => $pedidoEpp->id,
                    'epp_id' => $pedidoEpp->epp_id,
                    'epp' => $pedidoEpp->epp,
                    'cantidad' => $pedidoEpp->cantidad
                ]);
                
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
                        
                        \Log::info('[RECIBOS-REPO] ImÃ¡genes encontradas para EPP:', [
                            'pedido_epp_id' => $pedidoEpp->id,
                            'cantidad' => count($imagenes),
                            'imagenes' => $imagenes
                        ]);
                    } else {
                        \Log::info('[RECIBOS-REPO] Sin imÃ¡genes para EPP:', [
                            'pedido_epp_id' => $pedidoEpp->id
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('[RECIBOS-REPO] Error obteniendo imÃ¡genes de EPP:', [
                        'pedido_epp_id' => $pedidoEpp->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $datos['epps'][] = $eppFormato;
            }
            \Log::info(' [RECIBOS-REPO] EPP cargados exitosamente', ['count' => count($datos['epps'])]);
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

        \Log::info(' [RECIBOS-REPO] Datos retornados', [
            'prendas_count' => count($datos['prendas'] ?? []),
            'epps_count' => count($datos['epps'] ?? []),
            'procesos_debug' => $detalleDebug
        ]);

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

