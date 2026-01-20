<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ObtenerPedidoDetalleService
{
    /**
     * Obtener un pedido con todos sus detalles y relaciones
     * 
     * @param int|string $pedidoIdentifier Número de pedido o ID
     * @return PedidoProduccion
     * @throws \Exception
     */
    public function obtener($pedidoIdentifier): PedidoProduccion
    {
        Log::info('📖 [DETALLE] Obteniendo detalles del pedido', [
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
        Log::info('✏️ [EDICION] Obteniendo datos para edición');

        $pedido = $this->obtenerCompleto($pedidoIdentifier);

        //  Transformar prendas a estructura del gestor
        $prendasTransformadas = $this->transformarPrendasParaEdicion($pedido->prendas);

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
                'Lavandería',
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
     *  NUEVO: Transformar prendas de Eloquent a estructura del gestor
     */
    private function transformarPrendasParaEdicion($prendas)
    {
        return $prendas->map(function ($prenda) {
            // Obtener cantidades por talla desde generosConTallas
            $generosConTallas = $this->construirGenerosConTallas($prenda);
            $variantes = $this->construirVariantes($prenda);
            $procesos = $this->construirProcesos($prenda);
            
            //  Extraer datos de variación de las variantes (tomar del primer variante)
            $primerVariante = $prenda->variantes && $prenda->variantes->count() > 0 
                ? $prenda->variantes->first() 
                : null;
            
            $tipo_manga = 'No aplica';
            $obs_manga = '';
            $tipo_broche = 'No aplica';
            $obs_broche = '';
            $tiene_bolsillos = false;
            $obs_bolsillos = '';
            $tiene_reflectivo = false;
            $obs_reflectivo = '';
            
            if ($primerVariante) {
                // Obtener nombre del tipo de manga si existe la relación
                if ($primerVariante->tipoManga) {
                    $tipo_manga = $primerVariante->tipoManga->nombre ?? 'No aplica';
                }
                $obs_manga = $primerVariante->manga_obs ?? '';
                
                // Obtener nombre del tipo de broche si existe la relación
                if ($primerVariante->tipoBrocheBoton) {
                    $tipo_broche = $primerVariante->tipoBrocheBoton->nombre ?? 'No aplica';
                }
                $obs_broche = $primerVariante->broche_boton_obs ?? '';
                
                // Bolsillos
                $tiene_bolsillos = (bool)($primerVariante->tiene_bolsillos ?? false);
                $obs_bolsillos = $primerVariante->bolsillos_obs ?? '';
                
                // Reflectivo (si existe en tabla, sino será false)
                $tiene_reflectivo = (bool)($primerVariante->tiene_reflectivo ?? false);
                $obs_reflectivo = $primerVariante->reflectivo_obs ?? '';
            }

            return [
                'nombre_prenda' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion,
                'genero' => [], // Se llenará desde generosConTallas
                'generosConTallas' => $generosConTallas, //  Dejar como objeto, Blade hace @json()
                'tipo_manga' => $tipo_manga,
                'obs_manga' => $obs_manga,
                'tipo_broche' => $tipo_broche,
                'obs_broche' => $obs_broche,
                'tiene_bolsillos' => $tiene_bolsillos,
                'obs_bolsillos' => $obs_bolsillos,
                'tiene_reflectivo' => $tiene_reflectivo,
                'obs_reflectivo' => $obs_reflectivo,
                'tallas' => $this->obtenerTallasDelPrenda($prenda),
                'cantidadesPorTalla' => $this->obtenerCantidadesPorTalla($prenda),
                'variantes' => $variantes, //  Dejar como objeto
                'telas' => $prenda->telas ?? [],
                'telasAgregadas' => $this->construirTelasAgregadas($prenda),
                'fotos' => $this->obtenerFotosPrenda($prenda),  //  Convertir con URLs
                'telaFotos' => $this->obtenerFotosTelas($prenda),
                'imagenes' => $this->obtenerFotosPrenda($prenda),  //  Convertir con URLs
                'origen' => $prenda->origen ?? 'bodega',
                'de_bodega' => (int)($prenda->de_bodega ?? 1),
                'procesos' => $procesos, //  Dejar como objeto
                'variaciones' => $variantes, //  Dejar como objeto
            ];
        })->toArray();
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
     * Obtener tallas únicas del prenda
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
                        // Acceder a través de relaciones cargadas
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
            $fotos = $prenda->fotos->toArray();
        }
        return $fotos;
    }

    /**
     * Construir procesos en estructura esperada
     */
    private function construirProcesos($prenda)
    {
        $procesos = [];
        
        if (isset($prenda->procesos) && is_iterable($prenda->procesos)) {
            foreach ($prenda->procesos as $proceso) {
                //  Obtener el SLUG del tipo de proceso (para usar como clave)
                $slugTipoProceso = 'proceso';  // default
                $nombreTipoProceso = 'Proceso';
                
                if ($proceso->tipoProceso) {
                    $slugTipoProceso = $proceso->tipoProceso->slug ?? $proceso->tipoProceso->tipo ?? 'proceso';
                    $nombreTipoProceso = $proceso->tipoProceso->nombre ?? $proceso->tipoProceso->tipo ?? 'Proceso';
                }
                
                //  Obtener imágenes transformadas a URLs
                $imagenes = [];
                if ($proceso->imagenes && $proceso->imagenes->count() > 0) {
                    $imagenes = $proceso->imagenes->map(function ($img) {
                        return [
                            'url' => $img->ruta_webp ?? $img->ruta_original,
                            'ruta_original' => $img->ruta_original,
                            'ruta_webp' => $img->ruta_webp,
                            'es_principal' => $img->es_principal ?? false,
                        ];
                    })->toArray();
                }
                
                //  Parsear ubicaciones (pueden ser array o JSON)
                $ubicaciones = [];
                if ($proceso->ubicaciones) {
                    if (is_string($proceso->ubicaciones)) {
                        $ubicaciones = json_decode($proceso->ubicaciones, true) ?? [];
                    } else {
                        $ubicaciones = (array)$proceso->ubicaciones;
                    }
                }
                
                //  Construir estructura de tallas
                $tallas = [];
                $tallasDama = is_string($proceso->tallas_dama) 
                    ? (json_decode($proceso->tallas_dama, true) ?? []) 
                    : ($proceso->tallas_dama ?? []);
                $tallasCalballero = is_string($proceso->tallas_caballero) 
                    ? (json_decode($proceso->tallas_caballero, true) ?? []) 
                    : ($proceso->tallas_caballero ?? []);
                
                if (!empty($tallasDama)) {
                    $tallas['dama'] = $tallasDama;
                }
                if (!empty($tallasCalballero)) {
                    $tallas['caballero'] = $tallasCalballero;
                }
                
                //  Usar el SLUG como clave para agrupar (reflectivo, bordado, estampado, dtf, sublimado)
                if (!isset($procesos[$slugTipoProceso])) {
                    $procesos[$slugTipoProceso] = [
                        'id' => $proceso->tipo_proceso_id,
                        'tipo' => $nombreTipoProceso,  //  Nombre del tipo de proceso
                        'slug' => $slugTipoProceso,    //  Slug del tipo de proceso
                        'nombre' => $nombreTipoProceso,  //  Nombre para compatibilidad
                        'datos' => [
                            'ubicaciones' => $ubicaciones,  //  Ubicaciones parseadas
                            'observaciones' => $proceso->observaciones ?? '',
                            'tallas' => $tallas,  //  Tallas parseadas
                            'imagenes' => $imagenes,  //  Imágenes con URLs
                        ]
                    ];
                }
            }
        }
        
        return $procesos;
    }

    /**
     * Construir tallas del proceso
     * 
     * Estructura del proceso cantidad_talla es igual a la prenda:
     * {genero: {talla: cantidad}}
     */
    private function construirTallasProceso($proceso)
    {
        $tallas = [];
        
        //  cantidad_talla viene como JSON string
        $cantidadTalla = $proceso->cantidad_talla ?? null;
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }
        
        // La estructura en BD ya es correcta: {genero: {talla: cantidad}}
        if ($cantidadTalla && is_array($cantidadTalla)) {
            $tallas = $cantidadTalla;
        }
        
        return $tallas;
    }

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
     * Obtener solo información básica
     */
    public function obtenerBasico($pedidoIdentifier): array
    {
        Log::info(' [BASICO] Obteniendo información básica');

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
     * Obtener el pedido (por número o ID)
     */
    private function obtenerPedido($pedidoIdentifier): PedidoProduccion
    {
        // Si es número (numérico > 100)
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
}
