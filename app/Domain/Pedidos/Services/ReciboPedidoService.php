<?php

namespace App\Domain\Pedidos\Services;

use App\Constants\SQLPedidosConstants;
use App\Models\PedidoProduccion;

/**
 * Servicio para generar datos de recibos de pedidos
 * Responsabilidad: Formatear datos para recibos dinámicos
 */
class ReciboPedidoService
{
    /**
     * Obtener datos para los recibos dinámicos
     * Formato específico para ReceiptManager en receipt-dynamic.blade.php
     */
    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array
    {
        $pedido = $this->obtenerPedidoConRelaciones($pedidoId);
        
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
            $datos['prendas'][] = $this->procesarPrendaParaRecibo($prenda, $prendaIndex);
        }

        // Procesar EPPs para recibos
        $datos['epps'] = $this->procesarEppsParaRecibo($pedido);

        return $datos;
    }

    /**
     * Obtener pedido con relaciones necesarias para recibos
     */
    private function obtenerPedidoConRelaciones(int $pedidoId): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'prendas.variantes.tipoManga',
            'prendas.variantes.tipoBrocheBoton',
            'prendas.procesos.tipoProceso',
            'prendas.procesos.imagenes',
            'epps.epp.categoria'
        ])->find($pedidoId);
    }

    /**
     * Procesar una prenda para recibos
     */
    private function procesarPrendaParaRecibo($prenda, int $prendaIndex): array
    {
        $cantidadTotal = 0;
        $colores = [];
        $telas = [];
        $referencias = [];
        $especificaciones = [];
        $imagenesPrenda = [];
        $imagenesTela = [];

        // Obtener imágenes de prenda
        $imagenesPrenda = $this->obtenerImagenesPrenda($prenda->id);

        // Obtener tela, color, referencia e imágenes desde prenda_pedido_colores_telas
        $datosColorTela = $this->obtenerColorTelaDatos($prenda->id);
        
        if ($datosColorTela) {
            $colores = $datosColorTela['colores'];
            $telas = $datosColorTela['telas'];
            $referencias = $datosColorTela['referencias'];
            $imagenesTela = $datosColorTela['imagenesTela'];
        }

        // Procesar variantes
        foreach ($prenda->variantes as $variante) {
            $especificaciones = $this->procesarVarianteParaRecibo($variante);
        }

        // Obtener tallas desde tabla relacional
        $tallasDatos = $this->obtenerTallasRelacionales($prenda->id);
        
        // Procesar procesos
        $procesos = $this->procesarProcesosParaRecibo($prenda, $datosColorTela['colorTelaId'] ?? null);

        // Preparar telas agregadas (estructura para edición)
        $telasAgregadas = $this->prepararTelasAgregadas($prenda->id);

        // Construir prenda para recibos
        $origen = ($prenda->de_bodega == 1) ? 'bodega' : 'confección';
        
        return [
            'prenda_index' => $prendaIndex,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion ?? '',
            'cantidad' => $cantidadTotal,
            'origen' => $origen,
            'de_bodega' => (bool)($prenda->de_bodega ?? false),
            'colores' => $colores,
            'telas' => $telas,
            'referencias' => $referencias,
            'especificaciones' => $especificaciones,
            'imagenes' => $imagenesPrenda,
            'imagenes_tela' => $imagenesTela,
            'tallas' => $tallasDatos,
            'procesos' => $procesos,
            'telas_agregadas' => $telasAgregadas,
            'observaciones' => $prenda->observaciones ?? '',
            'tiene_reflectivo' => (bool)($prenda->tiene_reflectivo ?? false),
            'tiene_bolsillos' => (bool)($prenda->tiene_bolsillos ?? false),
            'observaciones_variaciones' => $this->obtenerObservacionesVariaciones($prenda),
        ];
    }

    /**
     * Obtener imágenes de prenda desde la base de datos
     */
    private function obtenerImagenesPrenda(int $prendaId): array
    {
        try {
            $const = SQLPedidosConstants::FOTOS_PRENDA;
            $fotosPrenda = \DB::table($const['table'])
                ->where('prenda_pedido_id', $prendaId)
                ->where('deleted_at', null)
                ->orderBy('orden')
                ->select($const['select'])
                ->get();
            
            return $fotosPrenda->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp))->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtener datos de colores y telas
     */
    private function obtenerColorTelaDatos(int $prendaId): array
    {
        try {
            $const = SQLPedidosConstants::COLORES_TELAS_PRENDA;
            $colorTelaData = \DB::table($const['table'])
                ->join('colores_prenda', $const['joins']['colores_prenda'])
                ->join('telas_prenda', $const['joins']['telas_prenda'])
                ->select($const['select'])
                ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prendaId)
                ->first();
            
            if (!$colorTelaData) {
                return [
                    'colores' => [],
                    'telas' => [],
                    'referencias' => [],
                    'imagenesTela' => [],
                    'colorTelaId' => null
                ];
            }

            $colores = [];
            $telas = [];
            $referencias = [];

            if ($colorTelaData->color_nombre && !in_array($colorTelaData->color_nombre, $colores)) {
                $colores[] = $colorTelaData->color_nombre;
            }
            if ($colorTelaData->talla_nombre && !in_array($colorTelaData->tela_nombre, $telas)) {
                $telas[] = $colorTelaData->talla_nombre;
            }
            if ($colorTelaData->referencia && !in_array($colorTelaData->referencia, $referencias)) {
                $referencias[] = $colorTelaData->referencia;
            }
            
            // Obtener imágenes de tela
            $imagenesTela = $this->obtenerImagenesTela($colorTelaData->color_tela_id);

            return [
                'colores' => $colores,
                'telas' => $telas,
                'referencias' => $referencias,
                'imagenesTela' => $imagenesTela,
                'colorTelaId' => $colorTelaData->color_tela_id
            ];
        } catch (\Exception $e) {
            return [
                'colores' => [],
                'telas' => [],
                'referencias' => [],
                'imagenesTela' => [],
                'colorTelaId' => null
            ];
        }
    }

    /**
     * Obtener imágenes de tela
     */
    private function obtenerImagenesTela(?int $colorTelaId): array
    {
        if (!$colorTelaId) {
            return [];
        }

        try {
            $constFotos = SQLPedidosConstants::FOTOS_TELA;
            $fotos = \DB::table($constFotos['table'])
                ->where('prenda_pedido_colores_telas_id', $colorTelaId)
                ->where('deleted_at', null)
                ->orderBy('orden')
                ->select($constFotos['select'])
                ->get();
            
            return $fotos->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp))->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Procesar variante para recibos
     */
    private function procesarVarianteParaRecibo($variante): array
    {
        $spec = [
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
                $const = SQLPedidosConstants::NOMBRE_TIPO_MANGA;
                $manga = \DB::table($const['table'])
                    ->where('id', $variante->tipo_manga_id)
                    ->value($const['select'][0]);
                $spec['manga'] = $manga;
            } catch (\Exception $e) {
                // Error obteniendo tipo de manga
            }
        }
        
        // Obtener tipo de broche/botón
        if ($variante->tipo_broche_boton_id) {
            try {
                $const = SQLPedidosConstants::NOMBRE_TIPO_BROCHE;
                $broche = \DB::table($const['table'])
                    ->where('id', $variante->tipo_broche_boton_id)
                    ->value($const['select'][0]);
                $spec['broche'] = $broche;
            } catch (\Exception $e) {
                // Error obteniendo tipo de broche
            }
        }
        
        return $spec;
    }

    /**
     * Obtener tallas relacionales con colores
     */
    private function obtenerTallasRelacionales(int $prendaId): array
    {
        $tallasPorGenero = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => []
        ];
        
        // Obtener tallas desde prenda_pedido_talla_colores (flujo 2)
        $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaId)
            ->select(
                'ppt.genero',
                'ppt.talla',
                'pptc.color_nombre',
                'pptc.cantidad'
            )
            ->get();
            
        \Log::info('[RECIBO-SERVICE] Tallas colores encontrados', [
            'prendaId' => $prendaId,
            'cantidad' => $tallasColores->count(),
            'datos' => $tallasColores->toArray()
        ]);
            
        foreach ($tallasColores as $tallaColor) {
            $genero = strtoupper($tallaColor->genero);
            $talla = $tallaColor->talla;
            $color = $tallaColor->color_nombre;
            $cantidad = $tallaColor->cantidad;
            
            if (!isset($tallasPorGenero[$genero][$talla])) {
                $tallasPorGenero[$genero][$talla] = [];
            }
            
            $tallasPorGenero[$genero][$talla][] = [
                'cantidad' => $cantidad,
                'color' => $color
            ];
        }
        
        // Si no hay datos en flujo 2, intentar desde prenda_pedido_tallas (flujo 1)
        if (empty($tallasColores)) {
            \Log::info('[RECIBO-SERVICE] Usando flujo 1 - prenda_pedido_tallas');
            
            $tallasBasicas = \DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->select('genero', 'talla', 'cantidad')
                ->get();
                
            \Log::info('[RECIBO-SERVICE] Tallas básicas encontradas', [
                'prendaId' => $prendaId,
                'cantidad' => $tallasBasicas->count(),
                'datos' => $tallasBasicas->toArray()
            ]);
                
            foreach ($tallasBasicas as $talla) {
                $genero = strtoupper($talla->genero);
                $tallaPorGenero = $talla->talla;
                $cantidad = $talla->cantidad;
                
                $tallasPorGenero[$genero][$tallaPorGenero][] = [
                    'cantidad' => $cantidad,
                    'color' => 'Sin color'
                ];
            }
        }
        
        \Log::info('[RECIBO-SERVICE] Estructura final de tallas', [
            'prendaId' => $prendaId,
            'tallasPorGenero' => $tallasPorGenero
        ]);
        
        return $tallasPorGenero;
    }

    /**
     * Procesar procesos para recibos
     */
    private function procesarProcesosParaRecibo($prenda, ?int $colorTelaId): array
    {
        $procesos = [];
        
        foreach ($prenda->procesos as $proc) {
            $procTallas = [
                'dama' => [],
                'caballero' => [],
                'unisex' => [],
                'sobremedida' => []
            ];
            
            // Obtener tallas del proceso desde pedidos_procesos_prenda_tallas
            $procTallas = $this->obtenerTallasProceso($proc->id);
            
            // Obtener ubicaciones
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
            if ($proc->imagenes && $proc->imagenes->count() > 0) {
                $imagenesProceso = $proc->imagenes->map(function($img) {
                    $url = $img->ruta_webp ?? $img->url ?? $img->ruta_original ?? '';
                    return $this->normalizarRutaImagen($url);
                })->toArray();
            }
            
            // Obtener nombre del tipo de proceso
            $nombreProceso = $proc->tipoProceso->nombre ?? 'Proceso';
            
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
                'estado' => $proc->estado ?? 'Pendiente',
            ];
        }
        
        return $procesos;
    }

    /**
     * Preparar telas agregadas para edición
     */
    private function prepararTelasAgregadas(int $prendaId): array
    {
        $telasAgregadas = [];
        try {
            $const = SQLPedidosConstants::COLORES_TELAS_PRENDA;
            $colorTelaData = \DB::table($const['table'])
                ->join('colores_prenda', $const['joins']['colores_prenda'])
                ->join('telas_prenda', $const['joins']['telas_prenda'])
                ->select($const['select'])
                ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prendaId)
                ->first();
            
            if ($colorTelaData) {
                // Obtener imágenes de tela
                $constFotos = SQLPedidosConstants::FOTOS_TELA;
                $fotosTelaDB = \DB::table($constFotos['table'])
                    ->where('prenda_pedido_colores_telas_id', $colorTelaData->color_tela_id)
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select($constFotos['select'])
                    ->get();
                
                $imagenesTelaFormato = $fotosTelaDB->map(fn($foto) => $this->normalizarRutaImagen($foto->ruta_webp ?? $foto->ruta_original))->toArray();
                
                $telasAgregadas[] = [
                    'tela' => $colorTelaData->talla_nombre,
                    'color' => $colorTelaData->color_nombre,
                    'referencia' => $colorTelaData->referencia ?? '',
                    'imagenes' => $imagenesTelaFormato
                ];
            }
        } catch (\Exception $e) {
            // Error obteniendo datos de telas
        }
        
        return $telasAgregadas;
    }

    /**
     * Obtener observaciones de variaciones
     */
    private function obtenerObservacionesVariaciones($prenda): array
    {
        $obsVariaciones = [];
        
        if ($prenda->variantes && $prenda->variantes->count() > 0) {
            $primerVariante = $prenda->variantes->first();
            
            $obsVariaciones = [
                'manga_obs' => $primerVariante->manga_obs ?? '',
                'broche_obs' => $primerVariante->broche_boton_obs ?? '',
                'bolsillos_obs' => $primerVariante->bolsillos_obs ?? '',
                'reflectivo_obs' => $prenda->tiene_reflectivo ? 'Sí' : '',
            ];
            
            // Obtener nombre del tipo de manga
            if ($primerVariante->tipoManga && $primerVariante->tipoManga->nombre) {
                $obsVariaciones['tipo_manga'] = $primerVariante->tipoManga->nombre;
            }
            
            // Obtener nombre del tipo de broche/botón
            if ($primerVariante->tipo_broche_boton_id) {
                $const = SQLPedidosConstants::NOMBRE_TIPO_BROCHE;
                $tipoBroche = \DB::table($const['table'])
                    ->where('id', $primerVariante->tipo_broche_boton_id)
                    ->value($const['select'][0]);
                if ($tipoBroche) {
                    $obsVariaciones['tipo_broche'] = $tipoBroche;
                }
            }
        }
        
        return $obsVariaciones;
    }

    /**
     * Procesar EPPs para recibos
     */
    private function procesarEppsParaRecibo(PedidoProduccion $pedido): array
    {
        $epps = [];
        
        foreach ($pedido->epps as $pedidoEpp) {
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
            
            // Obtener imágenes del EPP
            $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);
            if (!empty($imagenes)) {
                $eppFormato['imagenes'] = $imagenes;
                $eppFormato['imagen'] = $imagenes[0] ?? null;
            }
            
            $epps[] = $eppFormato;
        }
        
        return $epps;
    }

    /**
     * Obtener imágenes de EPP
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
                return $imagenesData->pluck('ruta_web')->filter()->toArray();
            }
        } catch (\Exception $e) {
            \Log::error('[RECIBOS-REPO] Error obteniendo imágenes de EPP:', [
                'pedido_epp_id' => $pedidoEppId,
                'error' => $e->getMessage()
            ]);
        }
        
        return [];
    }

    /**
     * Normalizar ruta de imagen
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

    /**
     * Obtener tallas de un proceso desde pedidos_procesos_prenda_tallas
     */
    private function obtenerTallasProceso(int $procesoId): array
    {
        $tallas = [
            'dama' => [],
            'caballero' => [],
            'unisex' => [],
            'sobremedida' => []
        ];
        
        try {
            $tallasProceso = \DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $procesoId)
                ->get(['genero', 'talla', 'cantidad']);
                
            \Log::info('[RECIBO-SERVICE] Tallas del proceso', [
                'procesoId' => $procesoId,
                'cantidad' => $tallasProceso->count(),
                'datos' => $tallasProceso->toArray()
            ]);
            
            foreach ($tallasProceso as $talla) {
                $genero = strtolower($talla->genero);
                
                if (isset($tallas[$genero])) {
                    $tallas[$genero][$talla->talla] = $talla->cantidad;
                }
            }
            
            \Log::info('[RECIBO-SERVICE] Estructura final de tallas del proceso', [
                'procesoId' => $procesoId,
                'tallas' => $tallas
            ]);
            
        } catch (\Exception $e) {
            \Log::error('[RECIBO-SERVICE] Error obteniendo tallas del proceso', [
                'procesoId' => $procesoId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $tallas;
    }
}
