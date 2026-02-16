<?php

namespace App\Domain\Pedidos\Services;

use App\Constants\SQLPedidosConstants;
use App\Models\PedidoProduccion;

/**
 * Servicio para generar datos de facturas de pedidos
 * Responsabilidad: Formatear datos completos para facturas
 */
class FacturaPedidoService
{
    /**
     * Obtener datos completos de factura de un pedido
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
            $pedido = $this->obtenerPedidoConRelaciones($pedidoId);
            
            if (!$pedido) {
                \Log::warning('[FACTURA] Pedido no encontrado', ['pedido_id' => $pedidoId]);
                throw new \Exception('Pedido no encontrado');
            }

            $datos = $this->construirDatosBase($pedido);
            $datos['prendas'] = $this->procesarPrendasParaFactura($pedido);
            $datos['epps'] = $this->procesarEppsParaFactura($pedido);
            $datos['total_items'] = $this->calcularTotalItems($datos);

            return $datos;
        } catch (\Exception $e) {
            \Log::error('[FACTURA] Error en obtenerDatosFactura', ['pedido_id' => $pedidoId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener pedido con todas las relaciones necesarias para factura
     */
    private function obtenerPedidoConRelaciones(int $pedidoId): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'cotizacion.tipoCotizacion',
            'prendas.variantes.tipoManga',
            'prendas.variantes.tipoBrocheBoton',
            'prendas.fotos',
            'prendas.fotosTelas',
            'prendas.coloresTelas.color',
            'prendas.coloresTelas.tela',
            'prendas.coloresTelas.fotos',
            'prendas.tallas',
            'prendas.tallas.coloresAsignados',
            'prendas.procesos',
            'prendas.procesos.tipoProceso',
            'prendas.procesos.imagenes',
            'prendas.procesos.tallas',
            'epps.imagenes',
        ])->find($pedidoId);
    }

    /**
     * Construir datos base del pedido
     */
    private function construirDatosBase(PedidoProduccion $pedido): array
    {
        $fechaCreacion = $this->determinarFechaCreacion($pedido);

        return [
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
    }

    /**
     * Determinar fecha de creación del pedido
     */
    private function determinarFechaCreacion(PedidoProduccion $pedido): string
    {
        return $pedido->fecha_de_creacion_de_orden 
            ? (is_string($pedido->fecha_de_creacion_de_orden) 
                ? $pedido->fecha_de_creacion_de_orden 
                : $pedido->fecha_de_creacion_de_orden->format('d/m/Y'))
            : ($pedido->created_at 
                ? $pedido->created_at->format('d/m/Y')
                : date('d/m/Y'));
    }

    /**
     * Procesar todas las prendas para la factura
     */
    private function procesarPrendasParaFactura(PedidoProduccion $pedido): array
    {
        $prendas = [];
        
        foreach ($pedido->prendas as $prenda) {
            $prendas[] = $this->procesarPrendaIndividual($prenda, $pedido);
        }

        return $prendas;
    }

    /**
     * Procesar una prenda individual para factura
     */
    private function procesarPrendaIndividual($prenda, PedidoProduccion $pedido): array
    {
        $cantidadTotal = 0;
        $variantes_formateadas = [];
        $especificaciones = $this->obtenerEspecificacionesPrenda($prenda);

        // Procesar tallas
        if ($prenda->tallas && $prenda->tallas->count() > 0) {
            foreach ($prenda->tallas as $talla) {
                $cantidadTotal += $talla->cantidad ?? 0;
                $variantes_formateadas[] = $this->procesarTallaParaFactura($talla, $especificaciones);
            }
        }

        // Obtener datos de colores, telas e imágenes
        $datosTelas = $this->procesarColoresYTelas($prenda);
        
        // Obtener procesos
        $procesos = $this->procesarProcesosPrenda($prenda);

        // 🔴 NUEVO: Construir variantes (manga, broche, bolsillos) desde prenda.variantes
        $variantesArray = $this->construirVariantesArray($prenda);
        
        // 🔴 NUEVO: Obtener colores asignados por talla desde BD
        $tallaColores = $this->obtenerTallaColoresDesdeDB($prenda->id);

        // Construir prenda formateada
        return [
            'id' => $prenda->id,
            'prenda_pedido_id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'de_bodega' => (bool)($prenda->de_bodega ?? false),
            'imagen' => $this->obtenerImagenPrincipal($prenda),
            'imagen_tela' => $this->obtenerImagenTelaPrincipal($prenda),
            'imagenes' => $this->obtenerTodasImagenesPrenda($prenda),
            'imagenes_tela' => $this->obtenerTodasImagenesTela($prenda),
            'tela' => !empty($datosTelas['telas']) ? implode(', ', $datosTelas['telas']) : null,
            'color' => !empty($datosTelas['colores']) ? implode(', ', $datosTelas['colores']) : null,
            'ref' => !empty($datosTelas['referencias']) ? implode(', ', $datosTelas['referencias']) : null,
            'telas_array' => $datosTelas['telasArray'],
            'colores_array' => $datosTelas['colores'],
            'referencias_array' => $datosTelas['referencias'],
            'tallas' => $this->construirTallasPorGenero($prenda),
            'talla_colores' => $tallaColores,  // 🔴 NUEVO: Colores asignados por talla
            'variantes' => $variantesArray,  // 🔴 AHORA: Array de variantes (manga, broche, bolsillos)
            'procesos' => $procesos,
            'generosConTallas' => $this->construirGenerosConTallas($prenda),
        ];
    }

    /**
     * 🔴 NUEVO: Obtener colores asignados por talla desde BD
     */
    private function obtenerTallaColoresDesdeDB(int $prendaId): array
    {
        return \DB::table('prenda_pedido_talla_colores as ptc')
            ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
            ->where('pt.prenda_pedido_id', $prendaId)
            ->select([
                'ptc.id',
                'ptc.prenda_pedido_talla_id',
                'pt.genero',
                'pt.talla',
                'ptc.tela_id',
                'ptc.tela_nombre',
                'ptc.color_id',
                'ptc.color_nombre',
                'ptc.cantidad'
            ])
            ->get()
            ->toArray();
    }

    /**
     * Obtener especificaciones de la prenda (manga, broche, bolsillos)
     */
    private function obtenerEspecificacionesPrenda($prenda): array
    {
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
            
            // Obtener manga con nombre
            if ($primeraVariante->tipoManga) {
                $especificaciones['manga'] = $primeraVariante->tipoManga->nombre;
            }
            
            // Obtener broche/botón con nombre
            if ($primeraVariante->tipoBrocheBoton) {
                $especificaciones['broche'] = $primeraVariante->tipoBrocheBoton->nombre;
            }
            
            // Obtener observaciones
            $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
            $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
            $especificaciones['bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
            $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';
        }

        return $especificaciones;
    }

    /**
     * Procesar talla individual para factura
     */
    private function procesarTallaParaFactura($talla, array $especificaciones): array
    {
        // Obtener colores asignados a esta talla
        $coloresAsignados = [];
        
        if ($talla->coloresAsignados && $talla->coloresAsignados->count() > 0) {
            foreach ($talla->coloresAsignados as $colorAsignado) {
                $coloresAsignados[] = [
                    'tela_nombre' => $colorAsignado->tela_nombre ?? '',
                    'color_nombre' => $colorAsignado->color_nombre ?? '',
                    'cantidad' => $colorAsignado->cantidad ?? 0,
                    'tela_id' => $colorAsignado->tela_id,
                    'color_id' => $colorAsignado->color_id,
                ];
            }
        }
        
        return [
            'talla' => $talla->talla ?? '',
            'cantidad' => $talla->cantidad ?? 0,
            'manga' => $especificaciones['manga'],
            'manga_obs' => $especificaciones['manga_obs'],
            'broche' => $especificaciones['broche'],
            'broche_obs' => $especificaciones['broche_obs'],
            'bolsillos' => $especificaciones['bolsillos'],
            'bolsillos_obs' => $especificaciones['bolsillos_obs'],
            'colores_asignados' => $coloresAsignados,
        ];
    }

    /**
     * Procesar colores y telas de una prenda
     */
    private function procesarColoresYTelas($prenda): array
    {
        $colores = [];
        $telas = [];
        $referencias = [];
        $telasArray = [];
        
        if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
            foreach ($prenda->coloresTelas as $colorTela) {
                // Procesar color
                if ($colorTela->color) {
                    $colorNombre = $colorTela->color->nombre ?? null;
                    if ($colorNombre && !in_array($colorNombre, $colores)) {
                        $colores[] = $colorNombre;
                    }
                }
                
                // Procesar tela y referencia
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
                    $fotosColorTela = $this->procesarFotosColorTela($colorTela);
                    
                    // 🔴 NUEVO: Obtener color o mostrar "Sin color"
                    $colorNombre = $colorTela->color ? ($colorTela->color->nombre ?? null) : null;
                    $colorCodigo = $colorTela->color ? ($colorTela->color->codigo ?? null) : null;
                    
                    // Si no hay color, mostrar "Sin color" en lugar de null o "Sin Color"
                    if (!$colorNombre) {
                        $colorNombre = 'Sin color';
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
                        'color' => $colorNombre,
                        'color_nombre' => $colorNombre,
                        'color_codigo' => $colorCodigo,
                        'fotos' => $fotosColorTela,
                        'fotos_tela' => $fotosColorTela,
                        'imagenes' => $fotosColorTela,
                    ];
                    
                    // Evitar duplicados
                    if (!$this->existeTelaEnArray($telasArray, $colorTela)) {
                        $telasArray[] = $telaItem;
                    }
                }
            }
        }

        return compact('colores', 'telas', 'referencias', 'telasArray');
    }

    /**
     * Procesar fotos de una combinación color-tela
     */
    private function procesarFotosColorTela($colorTela): array
    {
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
        return $fotosColorTela;
    }

    /**
     * Verificar si ya existe una tela en el array
     */
    private function existeTelaEnArray(array $telasArray, $colorTela): bool
    {
        foreach ($telasArray as $item) {
            if ($item['tela_id'] == $colorTela->tela_id && $item['color_id'] == $colorTela->color_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener imagen principal de la prenda
     */
    private function obtenerImagenPrincipal($prenda): ?string
    {
        if ($prenda->fotos && $prenda->fotos->count() > 0) {
            $foto = $prenda->fotos->first();
            return $foto->url ?? $foto->ruta_webp ?? null;
        }
        return null;
    }

    /**
     * Obtener imagen de tela principal
     */
    private function obtenerImagenTelaPrincipal($prenda): ?array
    {
        if ($prenda->fotosTelas && $prenda->fotosTelas->count() > 0) {
            $foto = $prenda->fotosTelas->first();
            return [
                'id' => $foto->id,
                'url' => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original ?? $foto->url),
            ];
        }
        return null;
    }

    /**
     * Obtener todas las imágenes de la prenda
     */
    private function obtenerTodasImagenesPrenda($prenda): array
    {
        if ($prenda->fotos) {
            return $prenda->fotos->map(fn($f) => [
                'id' => $f->id,
                'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->url),
            ])->toArray();
        }
        return [];
    }

    /**
     * Obtener todas las imágenes de tela
     */
    private function obtenerTodasImagenesTela($prenda): array
    {
        if ($prenda->fotosTelas) {
            return $prenda->fotosTelas->map(fn($f) => [
                'id' => $f->id,
                'url' => $this->normalizarRutaImagen($f->ruta_webp ?? $f->ruta_original ?? $f->url),
            ])->toArray();
        }
        return [];
    }

    /**
     * Construir tallas agrupadas por género
     */
    private function construirTallasPorGenero($prenda): array
    {
        $tallas = [];
        
        if ($prenda->tallas && $prenda->tallas->count() > 0) {
            foreach ($prenda->tallas as $t) {
                $genero = $t->genero ?? 'GENERAL';
                if (!isset($tallas[$genero])) {
                    $tallas[$genero] = [];
                }
                $tallas[$genero][$t->talla] = (int)$t->cantidad;
            }
        }
        
        return $tallas;
    }

    /**
     * Construir estructura generosConTallas para el frontend
     */
    private function construirGenerosConTallas($prenda): array
    {
        $generosConTallas = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => [],
            'SOBREMEDIDA' => []
        ];
        
        if ($prenda->tallas && $prenda->tallas->count() > 0) {
            foreach ($prenda->tallas as $t) {
                if ($t->es_sobremedida) {
                    $generoParaSobremedida = strtoupper($t->genero ?? 'DAMA');
                    $generosConTallas['SOBREMEDIDA'][$generoParaSobremedida] = (int)$t->cantidad;
                } else {
                    $genero = $t->genero ?? 'GENERAL';
                    $generosConTallasClave = strtoupper($genero);
                    if (!isset($generosConTallas[$generosConTallasClave])) {
                        $generosConTallas[$generosConTallasClave] = [];
                    }
                    $generosConTallas[$generosConTallasClave][$t->talla] = (int)$t->cantidad;
                }
            }
        }
        
        return $generosConTallas;
    }

    /**
     * Procesar procesos de una prenda
     */
    private function procesarProcesosPrenda($prenda): array
    {
        $procesos = [];
        
        try {
            if ($prenda->procesos) {
                foreach ($prenda->procesos as $proc) {
                    $procesos[] = $this->procesarProcesoIndividual($proc);
                }
            }
        } catch (\Exception $e) {
            \Log::error('[FACTURA] Error procesos', ['error' => $e->getMessage()]);
        }
        
        return $procesos;
    }

    /**
     * Procesar un proceso individual
     */
    private function procesarProcesoIndividual($proc): array
    {
        // Construir tallas del proceso
        $procTallas = $this->construirTallasProceso($proc);
        
        // Obtener ubicaciones
        $ubicaciones = $this->obtenerUbicacionesProceso($proc);
        
        // Obtener imágenes del proceso
        $imagenesProceso = $this->obtenerImagenesProceso($proc);
        
        // Obtener nombre del tipo de proceso
        $nombreProceso = $proc->tipoProceso->nombre ?? 'Proceso';
        
        return [
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

    /**
     * Construir tallas de un proceso
     */
    private function construirTallasProceso($proc): array
    {
        $procTallas = [
            'dama' => [],
            'caballero' => [],
            'unisex' => [],
            'sobremedida' => []
        ];
        
        if ($proc->tallas && $proc->tallas->count() > 0) {
            foreach ($proc->tallas as $tallaProceso) {
                if ($tallaProceso->es_sobremedida) {
                    $genero = strtoupper($tallaProceso->genero ?? 'DAMA');
                    $procTallas['sobremedida'][$genero] = (int)$tallaProceso->cantidad;
                } else {
                    $genero = strtolower($tallaProceso->genero ?? 'dama');
                    $procTallas[$genero][$tallaProceso->talla] = (int)$tallaProceso->cantidad;
                }
            }
        }
        
        return $procTallas;
    }

    /**
     * Obtener ubicaciones de un proceso
     */
    private function obtenerUbicacionesProceso($proc): array
    {
        $ubicaciones = [];
        if ($proc->ubicaciones) {
            if (is_array($proc->ubicaciones)) {
                $ubicaciones = $proc->ubicaciones;
            } else if (is_string($proc->ubicaciones)) {
                $ubicaciones = json_decode($proc->ubicaciones, true) ?? [];
            }
        }
        return $ubicaciones;
    }

    /**
     * Obtener imágenes de un proceso
     */
    private function obtenerImagenesProceso($proc): array
    {
        $imagenesProceso = [];
        if ($proc->imagenes && $proc->imagenes->count() > 0) {
            $imagenesProceso = $proc->imagenes->map(function($img) {
                $url = $img->ruta_webp ?? $img->url ?? $img->ruta_original ?? '';
                return $this->normalizarRutaImagen($url);
            })->toArray();
        }
        return $imagenesProceso;
    }

    /**
     * Procesar EPPs para factura
     */
    private function procesarEppsParaFactura(PedidoProduccion $pedido): array
    {
        $epps = [];
        
        try {
            if ($pedido->epps) {
                foreach ($pedido->epps as $pedidoEpp) {
                    $epps[] = $this->procesarEppIndividual($pedidoEpp);
                }
            }
        } catch (\Exception $e) {
            \Log::error('[FACTURA] Error procesando EPPs', ['pedido_id' => $pedido->id, 'error' => $e->getMessage()]);
        }

        return $epps;
    }

    /**
     * Procesar un EPP individual
     */
    private function procesarEppIndividual($pedidoEpp): array
    {
        // Validar que el EPP tenga relación válida
        $epp = $pedidoEpp->epp;
        if (!$epp) {
            return [];
        }
        
        // Obtener la talla
        $talla = $this->obtenerTallaEpp($pedidoEpp);
        
        // Construir datos del EPP
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
        
        // Obtener imágenes del EPP
        $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);
        if (!empty($imagenes)) {
            $eppFormato['imagenes'] = $imagenes;
            $eppFormato['imagen'] = $imagenes[0] ?? null;
        }
        
        return $eppFormato;
    }

    /**
     * Obtener talla de EPP
     */
    private function obtenerTallaEpp($pedidoEpp): string
    {
        $talla = '';
        if ($pedidoEpp->tallas_medidas) {
            if (is_array($pedidoEpp->tallas_medidas)) {
                $talla = implode(', ', $pedidoEpp->tallas_medidas);
            } else if (is_string($pedidoEpp->tallas_medidas)) {
                $talla = $pedidoEpp->tallas_medidas;
            }
        }
        return $talla;
    }

    /**
     * Obtener imágenes de EPP desde la base de datos
     */
    private function obtenerImagenesEpp(int $pedidoEppId): array
    {
        try {
            $const = SQLPedidosConstants::IMAGENES_EPP;
            $imagenesData = \DB::table($const['table'])
                ->where('pedido_epp_id', $pedidoEppId)
                ->orderBy('orden', 'asc')
                ->get($const['select']);
            
            if ($imagenesData->count() > 0) {
                return $imagenesData->map(fn($img) => [
                    'ruta_webp' => $this->normalizarRutaImagen($img->ruta_web ?? $img->ruta_original),
                    'ruta_original' => $this->normalizarRutaImagen($img->ruta_original),
                    'ruta_web' => $this->normalizarRutaImagen($img->ruta_web ?? $img->ruta_original),
                    'principal' => $img->principal ?? false,
                    'orden' => $img->orden ?? 0,
                ])->toArray();
            }
        } catch (\Exception $e) {
            \Log::warning('[FACTURA] Error obteniendo imágenes de EPP', ['pedido_epp_id' => $pedidoEppId]);
        }
        
        return [];
    }

    /**
     * Calcular total de items
     */
    private function calcularTotalItems(array $datos): int
    {
        $total = 0;
        
        // Sumar cantidades de prendas
        foreach ($datos['prendas'] as $prenda) {
            foreach ($prenda['variantes'] as $variante) {
                $total += $variante['cantidad'] ?? 0;
            }
        }
        
        // Sumar cantidades de EPPs
        foreach ($datos['epps'] as $epp) {
            $total += $epp['cantidad'] ?? 0;
        }
        
        return $total;
    }

    /**
     * 🔴 NUEVO: Construir array de variantes (manga, broche, bolsillos) desde prenda.variantes
     * 
     * Retorna un array con la primera variante que contiene:
     * - tipo_manga (o manga)
     * - tipo_broche_boton (o broche)
     * - tiene_bolsillos (o bolsillos)
     * - Observaciones correspondientes
     */
    private function construirVariantesArray($prenda): array
    {
        if (!$prenda->variantes || $prenda->variantes->count() === 0) {
            return [];
        }

        $primeraVariante = $prenda->variantes->first();
        
        return [
            [
                'id' => $primeraVariante->id,
                'tipo_manga' => $primeraVariante->tipoManga?->nombre ?? null,
                'manga' => $primeraVariante->tipoManga?->nombre ?? null,
                'manga_obs' => $primeraVariante->manga_obs ?? '',
                'tipo_broche_boton' => $primeraVariante->tipoBrocheBoton?->nombre ?? null,
                'tipo_broche' => $primeraVariante->tipoBrocheBoton?->nombre ?? null,
                'broche' => $primeraVariante->tipoBrocheBoton?->nombre ?? null,
                'broche_boton_obs' => $primeraVariante->broche_boton_obs ?? '',
                'broche_obs' => $primeraVariante->broche_boton_obs ?? '',
                'tiene_bolsillos' => (bool)($primeraVariante->tiene_bolsillos ?? false),
                'bolsillos' => (bool)($primeraVariante->tiene_bolsillos ?? false),
                'bolsillos_obs' => $primeraVariante->bolsillos_obs ?? '',
                'obs_bolsillos' => $primeraVariante->bolsillos_obs ?? '',
            ]
        ];
    }

    /**
     * Normalizar ruta de imagen para asegurar que comience con /storage/
     */
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }
        
        // Si ya comienza con /storage/, devolver tal cual
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        
        // Si comienza con storage/, agregar / al inicio
        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        
        // Si no comienza ni con /storage/ ni con storage/, agregar /storage/ al inicio
        return '/storage/' . ltrim($ruta, '/');
    }
}
