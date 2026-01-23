<?php

namespace App\Domain\PedidoProduccion\Repositories;

use App\Domain\PedidoProduccion\Traits\GestionaTallasRelacional;
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
            'prendas.tallas',  // NUEVA: Cargar tallas relacionales
            'prendas.procesos',
            'prendas.procesos.tipoProceso',  //  NUEVO: Cargar el nombre del tipo de proceso
            'prendas.procesos.imagenes',
            'epps.epp.categoria',  //  Cargar la categoría del EPP
            'epps.imagenes',
        ])->find($id);
    }

    /**
     * Obtener el último pedido creado (para secuencial de números)
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
     * Obtener datos completos de factura de un pedido
     * Incluye prendas con variantes, colores, telas e imágenes
     */
    public function obtenerDatosFactura(int $pedidoId): array
    {
        $pedido = $this->obtenerPorId($pedidoId);
        
        if (!$pedido) {
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
            $cantidadTotal = 0;
            $colores = [];
            $telas = [];
            $referencias = [];
            $especificaciones = [];  // Nuevas especificaciones

            // Obtener telas desde prenda_pedido_colores_telas (nueva tabla)
            try {
                $telasColorData = \DB::table('prenda_pedido_colores_telas')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                    ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                    ->select('telas_prenda.nombre as tela_nombre', 'telas_prenda.referencia', 'colores_prenda.nombre as color_nombre')
                    ->get();
                
                foreach ($telasColorData as $tc) {
                    if ($tc->tela_nombre && !in_array($tc->tela_nombre, $telas)) {
                        $telas[] = $tc->tela_nombre;
                    }
                    if ($tc->referencia && !in_array($tc->referencia, $referencias)) {
                        $referencias[] = $tc->referencia;
                    }
                    if ($tc->color_nombre && !in_array($tc->color_nombre, $colores)) {
                        $colores[] = $tc->color_nombre;
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('[FACTURA] Error obteniendo telas desde prenda_pedido_colores_telas: ' . $e->getMessage());
            }

            // Contar cantidad total desde variantes y extraer especificaciones
            foreach ($prenda->variantes as $variante) {
                $cantidadTotal += $variante->cantidad ?? 0;
                
                // Extraer especificaciones de la variante
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
                
                // Obtener nombre del tipo de manga
                if ($variante->tipo_manga_id) {
                    try {
                        $manga = \DB::table('tipos_manga')
                            ->where('id', $variante->tipo_manga_id)
                            ->value('nombre');
                        $spec['manga'] = $manga;
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo tipo de manga: ' . $e->getMessage());
                    }
                }
                
                // Obtener nombre del tipo de broche/botón
                if ($variante->tipo_broche_boton_id) {
                    try {
                        $broche = \DB::table('tipos_broche_boton')
                            ->where('id', $variante->tipo_broche_boton_id)
                            ->value('nombre');
                        $spec['broche'] = $broche;
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo tipo de broche: ' . $e->getMessage());
                    }
                }
                
                $especificaciones[] = $spec;
                
                // Obtener tela y referencia
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
                        \Log::debug('[FACTURA] Error obteniendo tela y referencia: ' . $e->getMessage());
                    }
                }
                
                // Obtener color
                if ($variante->color_id) {
                    try {
                        $color = \DB::table('colores_prenda')
                            ->where('id', $variante->color_id)
                            ->value('nombre');
                        if ($color && !in_array($color, $colores)) {
                            $colores[] = $color;
                        }
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo color: ' . $e->getMessage());
                    }
                }
            }

            // Obtener foto principal
            $foto = $prenda->fotos->first();
            
            // Obtener fotos de telas desde prenda_fotos_tela_pedido (nueva relación)
            $fotoTelas = [];
            try {
                $fotosTelasData = \DB::table('prenda_fotos_tela_pedido')
                    ->whereIn('prenda_pedido_colores_telas_id', 
                        \DB::table('prenda_pedido_colores_telas')
                            ->where('prenda_pedido_id', $prenda->id)
                            ->pluck('id')
                    )
                    ->orderBy('orden', 'asc')
                    ->get();
                
                $fotoTelas = $fotosTelasData->map(fn($f) => [
                    'id' => $f->id,
                    'url' => $f->ruta_webp ?? $f->ruta_original,
                    'ruta' => $f->ruta_webp ?? $f->ruta_original,
                    'ruta_original' => $f->ruta_original,
                    'ruta_webp' => $f->ruta_webp,
                ])->toArray();
            } catch (\Exception $e) {
                \Log::debug('[FACTURA] Error obteniendo fotos de telas desde prenda_fotos_tela_pedido: ' . $e->getMessage());
            }
            
            // Obtener todas las fotos de prenda
            $fotosPrend = $prenda->fotos->map(fn($f) => [
                'id' => $f->id,
                'url' => $f->url,
                'ruta' => $f->url,
                'ruta_original' => $f->ruta_original ?? $f->url,
                'ruta_webp' => $f->ruta_webp ?? $f->url,
            ])->toArray();
            
            \Log::info('[FACTURA] Fotos de prenda: ' . json_encode([
                'nombre_prenda' => $prenda->nombre_prenda,
                'fotos_prenda_count' => $prenda->fotos->count(),
                'fotos_telas_count' => $prenda->fotosTelas ? $prenda->fotosTelas->count() : 0,
                'fotos_telas' => $fotoTelas,
            ]));

            // Tallas desde tabla relacional (prenda_pedido_tallas)
            $tallas = $this->obtenerTallas($prenda->id);
            
            \Log::info('[FACTURA] Tallas de prenda: ' . json_encode([
                'nombre_prenda' => $prenda->nombre_prenda,
                'tallas_final' => $tallas,
            ]));

            // Obtener procesos
            $procesos = [];
            foreach ($prenda->procesos as $proc) {
                // Construir tallas desde tallas_dama y tallas_caballero (campos reales)
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
                $imagenesProceso = $proc->imagenes ? $proc->imagenes->map(fn($img) => $img->url)->toArray() : [];
                
                $proc_item = [
                    'tipo' => $proc->tipo ?? 'Proceso',
                    'tallas' => $procTallas,
                    'observaciones' => $proc->observaciones ?? '',
                    'ubicaciones' => $ubicaciones,
                    'imagenes' => $imagenesProceso,  // Agregar imágenes del proceso
                ];
                
                $procesos[] = $proc_item;
                
                \Log::info('[FACTURA] Proceso de prenda: ' . json_encode($proc_item));
            }

            // Construir array de prenda
            $prendasFormato = [
                'nombre' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion,
                'imagen' => $foto ? $foto->url : null,  // Usar el accessor 'url' del modelo
                'imagen_tela' => !empty($fotoTelas) ? $fotoTelas[0] : null,  // Primera foto de tela
                'imagenes' => $fotosPrend,  // Todas las fotos de prenda
                'imagenes_tela' => $fotoTelas,  // Todas las fotos de tela
                'tela' => !empty($telas) ? implode(', ', $telas) : null,
                'color' => !empty($colores) ? implode(', ', $colores) : null,
                'ref' => !empty($referencias) ? implode(', ', $referencias) : null,
                'tallas' => $tallas,
                'variantes' => $especificaciones,  // NUEVAS ESPECIFICACIONES
                'procesos' => $procesos,
            ];
            
            \Log::info('[FACTURA] Prenda formateada: ' . json_encode($prendasFormato));

            $datos['prendas'][] = $prendasFormato;
            $datos['total_items'] += $cantidadTotal;
        }

        //  AGREGAR EPP A LOS DATOS DE FACTURA
        $datos['epps'] = [];
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
                'codigo' => $epp->codigo ?? '',
                'categoria' => $epp->categoria?->nombre ?? $epp->categoria ?? '',  // Acceder al nombre de la categoría
                'talla' => $talla,  // Datos específicos del pedido
                'cantidad' => $pedidoEpp->cantidad ?? 0,
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'imagen' => null,
                'imagenes' => [],
            ];
            
            // Obtener imágenes del PedidoEpp desde la tabla pedido_epp_imagenes
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
                    
                    \Log::info('[RECIBOS-REPO] Imágenes encontradas:', [
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

        return $datos;
    }

    /**
     * Obtener datos para los recibos dinámicos
     * Formato específico para ReceiptManager en receipt-dynamic.blade.php
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
                
                $imagenesPrenda = $fotosPrenda->map(function($foto) {
                    $ruta = str_replace('\\', '/', $foto->ruta_webp);
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
                    return $ruta;
                })->toArray();
                
                \Log::info('[RECIBOS] Imágenes procesadas para prenda ' . $prenda->id . ': ' . count($imagenesPrenda) . ' total');
                if (count($imagenesPrenda) > 0) {
                    \Log::debug('[RECIBOS] Rutas procesadas: ' . json_encode($imagenesPrenda));
                }
            } catch (\Exception $e) {
                \Log::debug('[RECIBOS] Error obteniendo imágenes de prenda: ' . $e->getMessage());
            }

            // Obtener tela, color, referencia e imágenes desde prenda_pedido_colores_telas
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
                    
                    $imagenesTela = $fotos->map(function($foto) {
                        $ruta = str_replace('\\', '/', $foto->ruta_webp);
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
                        return $ruta;
                    })->toArray();
                }
            } catch (\Exception $e) {
                \Log::debug('[RECIBOS] Error obteniendo tela/color/imágenes desde nueva tabla: ' . $e->getMessage());
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
                
                // Obtener tipo de broche/botón
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
                
                // Imágenes del proceso
                $imagenesProceso = $proc->imagenes ? $proc->imagenes->map(fn($img) => $img->url)->toArray() : [];
                
                //  Obtener nombre del tipo de proceso (desde la relación cargada)
                $nombreProceso = 'Proceso';
                if ($proc->tipoProceso && $proc->tipoProceso->nombre) {
                    $nombreProceso = $proc->tipoProceso->nombre;
                }
                
                $proc_item = [
                    'nombre_proceso' => $nombreProceso,
                    'tipo_proceso' => $nombreProceso,
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
                    
                    $imagenesTelaFormato = $fotosTelaDB->map(function($foto) {
                        $ruta = str_replace('\\', '/', $foto->ruta_webp ?? $foto->ruta_original);
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
                        return $ruta;
                    })->toArray();
                    
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
                        // Detectar tipo de talla (letra o número)
                        $tipo = null;
                        $primerasTallas = array_keys($tallasCant);
                        if (!empty($primerasTallas)) {
                            $primeraTalla = $primerasTallas[0];
                            // Si es letra (S, M, L, XL, etc.)
                            if (strlen($primeraTalla) <= 3 && !is_numeric($primeraTalla)) {
                                $tipo = 'letra';
                            }
                            // Si es número (34, 36, 38, etc.)
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
                $obsVariaciones['obs_reflectivo'] = $prenda->tiene_reflectivo ? 'Sí' : '';
                
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
            
            \Log::info('[RECIBOS] Variaciones extraídas para prenda ' . $prendaIndex, ['obsVariaciones' => $obsVariaciones, 'tiene_variantes' => !empty($prenda->variantes)]);

            // Construir prenda para recibos
            // Determinar origen desde de_bodega: 1 = bodega, 0 = confección
            $origen = ($prenda->de_bodega == 1) ? 'bodega' : 'confección';
            
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
            \Log::debug('[RECIBOS-REPO] Intentando cargar EPP con relación epps()');
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
                    'epp_codigo' => $pedidoEpp->epp->codigo ?? '',
                    'epp_categoria' => $pedidoEpp->epp->categoria->nombre ?? '',
                    'cantidad' => $pedidoEpp->cantidad ?? 0,
                    'observaciones' => $pedidoEpp->observaciones ?? '',
                    'imagenes' => [],
                    'imagen' => null,
                ];
                
                // Obtener imágenes del PedidoEpp desde la tabla pedido_epp_imagenes
                try {
                    $imagenesData = \DB::table('pedido_epp_imagenes')
                        ->where('pedido_epp_id', $pedidoEpp->id)
                        ->orderBy('orden', 'asc')
                        ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                    
                    if ($imagenesData->count() > 0) {
                        $imagenes = $imagenesData->pluck('ruta_web')->filter()->toArray();
                        $eppFormato['imagenes'] = $imagenes;
                        $eppFormato['imagen'] = $imagenes[0] ?? null;
                        
                        \Log::info('[RECIBOS-REPO] Imágenes encontradas para EPP:', [
                            'pedido_epp_id' => $pedidoEpp->id,
                            'cantidad' => count($imagenes),
                            'imagenes' => $imagenes
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
            }
            \Log::info(' [RECIBOS-REPO] EPP cargados exitosamente', ['count' => count($datos['epps'])]);
        } catch (\Exception $e) {
            \Log::error('[RECIBOS-REPO] Error cargando EPP: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        \Log::info(' [RECIBOS-REPO] Datos retornados', [
            'prendas_count' => count($datos['prendas'] ?? []),
            'epps_count' => count($datos['epps'] ?? []),
            'primera_prenda_keys' => count($datos['prendas'] ?? []) > 0 ? array_keys($datos['prendas'][0]) : []
        ]);

        return $datos;
    }
}
