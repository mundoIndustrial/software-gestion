<?php

namespace App\Http\Controllers;

use App\Constants\AreaOptions;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Services\CacheCalculosService;
use App\Services\RegistroOrdenExtendedQueryService;
use App\Services\RegistroOrdenSearchExtendedService;
use App\Services\RegistroOrdenFilterExtendedService;
use App\Services\RegistroOrdenTransformService;
use App\Services\RegistroOrdenProcessService;
use App\Services\RegistroOrdenStatsService;
use App\Services\RegistroOrdenProcessesService;
use App\Services\RegistroOrdenEnumService;
use App\Models\Festivo;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;

/**
 * RegistroOrdenQueryController - Query/Search/Filter Layer
 * 
 * Responsabilidad única: Búsquedas, filtros y consultas de órdenes
 * Cumple: SRP
 * 
 * Métodos:
 * - index()           - Listar órdenes con paginación y filtros
 * - show()            - Obtener orden específica
 * - getNextPedido()   - Obtener siguiente número de pedido
 * - validatePedido()  - Validar número de pedido
 */
class RegistroOrdenQueryController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected $extendedQueryService;
    protected $extendedSearchService;
    protected $extendedFilterService;
    protected $transformService;
    protected $processService;
    protected $statsService;
    protected $processesService;
    protected $enumService;

    public function __construct(
        RegistroOrdenExtendedQueryService $extendedQueryService,
        RegistroOrdenSearchExtendedService $extendedSearchService,
        RegistroOrdenFilterExtendedService $extendedFilterService,
        RegistroOrdenTransformService $transformService,
        RegistroOrdenProcessService $processService,
        RegistroOrdenStatsService $statsService,
        RegistroOrdenProcessesService $processesService,
        RegistroOrdenEnumService $enumService
    )
    {
        $this->extendedQueryService = $extendedQueryService;
        $this->extendedSearchService = $extendedSearchService;
        $this->extendedFilterService = $extendedFilterService;
        $this->transformService = $transformService;
        $this->processService = $processService;
        $this->statsService = $statsService;
        $this->processesService = $processesService;
        $this->enumService = $enumService;
    }

    private function getEnumOptions($table, $column)
    {
        return $this->enumService->getEnumOptions($table, $column);
    }

    /**
     * Listar órdenes con paginación, búsqueda y filtros
     * GET /registros
     */
    public function index(Request $request)
    {
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->has('column')) {
            try {
                $values = $this->extendedQueryService->getUniqueValues($request->input('column'));
                return response()->json(['unique_values' => $values]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['error' => 'Invalid column'], 400);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching values: ' . $e->getMessage()], 500);
            }
        }

        $query = $this->extendedQueryService->buildBaseQuery();
        $query = $this->extendedQueryService->applyRoleFilters($query, auth()->user(), $request);
        $query = $this->extendedSearchService->applySearchFilter($query, $request->input('search'));

        // Extraer y aplicar filtros dinámicos
        $filterData = $this->extendedFilterService->extractFiltersFromRequest($request);
        $query = $this->extendedFilterService->applyFiltersToQuery($query, $filterData['filters']);
        $filterTotalDias = $filterData['totalDiasFilter'];

        $currentYear = now()->year;
        $nextYear = now()->addYear()->year;
        $festivos = array_merge(
            FestivosColombiaService::obtenerFestivos($currentYear),
            FestivosColombiaService::obtenerFestivos($nextYear)
        );
        
        \Log::info("Antes de verificar filtro - filterTotalDias: " . json_encode($filterTotalDias) . ", es null: " . ($filterTotalDias === null ? 'SI' : 'NO'));
        
        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            \Log::info("Iniciando filtrado por total_de_dias_ con valores: " . json_encode($filterTotalDias));
            $todasOrdenes = $query->get();
            \Log::info("Total órdenes obtenidas: " . $todasOrdenes->count());
            
            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);
            
            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
                $match = in_array((int)$totalDias, $filterTotalDias, true);
                
                // Log temporal para debug (eliminar después)
                if ((int)$orden->numero_pedido <= 3) {
                    \Log::info("Filtro total_dias - Pedido: {$orden->numero_pedido}, Total días: {$totalDias}, Filtros: " . json_encode($filterTotalDias) . ", Match: " . ($match ? 'SI' : 'NO'));
                }
                
                return $match;
            });
            
            // Paginar manualmente los resultados filtrados
            $currentPage = request()->get('page', 1);
            $perPage = 25;
            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            // Recalcular solo para las órdenes de la página actual (con caché inteligente)
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // OPTIMIZACIÓN: Paginación a 25 items
            $ordenes = $query->paginate(25);
            
            // DEBUG: Log de paginación
            \Log::info("=== PAGINACIÓN DEBUG ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Página actual: {$ordenes->currentPage()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            \Log::info("Por página: {$ordenes->perPage()}");
            \Log::info("Tiene búsqueda: " . ($request->has('search') ? 'SÍ' : 'NO'));
            \Log::info("Búsqueda: " . ($request->search ?? 'N/A'));
            \Log::info("HTML paginación: " . substr($ordenes->links()->toHtml(), 0, 200));

            // OPTIMIZACIÓN CRÍTICA: SOLO calcular para la página actual (25 items) con caché
            // No calcular para TODAS las 2257 órdenes - usa CacheCalculosService con TTL de 1 hora
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        }

        // Obtener areasMap solo para los items de esta página (OPTIMIZACIÓN)
        $numeroPedidosPagina = array_map(function($orden) {
            return $orden->numero_pedido;
        }, $ordenes->items());
        $areasMap = $this->processService->getLastProcessByOrderNumbers($numeroPedidosPagina);
        
        // Obtener encargados de "Creación Orden" para cada pedido
        $encargadosCreacionOrdenMap = $this->processService->getCreacionOrdenEncargados($numeroPedidosPagina);

        // Opciones de áreas disponibles (áreas de procesos)
        $areaOptions = AreaOptions::getArray();
        
        // FALLBACK: Si totalDiasCalculados está vacío o falta alguna orden, recalcular
        if (empty($totalDiasCalculados)) {
            \Log::warning("totalDiasCalculados vacío, recalculando...");
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // Verificar que todas las órdenes tengan un valor
            foreach ($ordenes->items() as $orden) {
                if (!isset($totalDiasCalculados[$orden->numero_pedido])) {
                    \Log::warning("Falta días para pedido {$orden->numero_pedido}, recalculando...");
                    $totalDiasCalculados[$orden->numero_pedido] = 
                        CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
                }
            }
        }

        if ($request->wantsJson()) {
            // Filtrar campos sensibles según el rol del usuario
            $ordenesFiltered = array_map(function($orden) use ($areasMap, $encargadosCreacionOrdenMap) {
                return $this->transformService->transformarOrden($orden, $areasMap, $encargadosCreacionOrdenMap);
            }, $ordenes->items());
            
            // Retornar string vacío para que paginationManager.js genere el HTML con los estilos correctos
            $paginationHtml = '';
            
            \Log::info("=== PAGINACIÓN ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            
            // Determinar contexto y rol para renderizado de botones
            $context = 'registros';
            $userRole = auth()->user() && auth()->user()->role ? auth()->user()->role->name : null;
            
            return response()->json([
                'orders' => $ordenesFiltered,
                'totalDiasCalculados' => $totalDiasCalculados,
                'areaOptions' => $areaOptions,
                'context' => $context,
                'userRole' => $userRole,
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'per_page' => $ordenes->perPage(),
                    'total' => $ordenes->total(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ],
                'pagination_html' => $paginationHtml
            ]);
        }

        $context = 'registros';
        $title = 'Registro de Órdenes';
        $icon = 'fa-clipboard-list';
        $fetchUrl = '/registros';
        $updateUrl = '/registros';
        $modalContext = 'orden';
        return view('orders.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'areasMap', 'encargadosCreacionOrdenMap', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
    }

    /**
     * Obtener orden específica
     * GET /registros/{pedido}
     */
    public function show($pedido)
    {
        // Buscar en PedidoProduccion por 'numero_pedido'
        $order = PedidoProduccion::with([
            'asesora', 
            'prendas',
            'prendas.fotos',
            'prendas.fotosLogo',
            'prendas.fotosTela',
            'cotizacion'
        ])->where('numero_pedido', $pedido)->firstOrFail();

        // Obtener estadísticas mediante servicio
        $stats = $this->statsService->getOrderStats($pedido);
        $order->total_cantidad = $stats['total_cantidad'];
        $order->total_entregado = $stats['total_entregado'];

        // Filtrar datos sensibles
        $orderArray = $order->toArray();
        
        // Verificar si es una cotización
        $esCotizacion = !empty($order->cotizacion_id);
        $orderArray['es_cotizacion'] = $esCotizacion;
        
        // Campos que se ocultan para todos
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];
        
        // Campos que se ocultan para no-asesores
        $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];
        
        // Agregar nombres en lugar de IDs
        if ($order->asesora) {
            $orderArray['asesor'] = $order->asesora->name ?? '';
            $orderArray['asesora'] = $order->asesora->name ?? '';
        } else {
            $orderArray['asesor'] = '';
            $orderArray['asesora'] = '';
        }
        
        // Para cliente, usar el campo 'cliente' directo (que es el nombre del cliente en la tabla)
        if (!empty($orderArray['cliente_id'])) {
            try {
                $cliente = \App\Models\Cliente::find($orderArray['cliente_id']);
                $orderArray['cliente_nombre'] = $cliente ? $cliente->nombre : ($orderArray['cliente'] ?? '');
            } catch (\Exception $e) {
                $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
            }
        } else {
            $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
        }
        
        // Asegurar que descripcion_prendas se calcula correctamente
        // Si no existe, armarla desde las prendas
        if (empty($order->descripcion_prendas)) {
            $prendas = $order->prendas ?? [];
            $descripcionPrendas = '';
            foreach ($prendas as $index => $prenda) {
                if ($index > 0) {
                    $descripcionPrendas .= "\n\n";
                }
                $descripcionPrendas .= "Prenda " . ($index + 1) . ": " . ($prenda->nombre_prenda ?? 'Sin nombre') . "\n";
                if ($prenda->descripcion) {
                    $descripcionPrendas .= "Descripción: " . $prenda->descripcion . "\n";
                }
                if ($prenda->cantidad_talla) {
                    $descripcionPrendas .= "Tallas: " . $prenda->cantidad_talla;
                }
            }
            $orderArray['descripcion_prendas'] = $descripcionPrendas ?: '';
        } else {
            $orderArray['descripcion_prendas'] = $order->descripcion_prendas;
        }
        
        // Obtener prendas formateadas para el modal
        \Log::info('🔍 [getOrderDetails] Obteniendo prendas para pedido', [
            'pedido' => $pedido,
            'es_cotizacion' => $esCotizacion,
        ]);
        
        try {
            // SIEMPRE cargar prendas con relaciones para generar descripción dinámica
            // (sin importar si es cotización o no)
            {
                // Cargar prendas CON relaciones necesarias para descripción dinámica
                $prendas = \App\Models\PrendaPedido::where('numero_pedido', $pedido)
                    ->with(['color', 'tela', 'tipoManga', 'tipoBroche'])
                    ->orderBy('id', 'asc')
                    ->get();

                // Formatear prendas con todos los datos necesarios
                $prendasFormato = [];
                foreach ($prendas as $index => $prenda) {
                    // Obtener datos de relaciones de forma segura
                    $colorNombre = null;
                    $telaNombre = null;
                    $telaReferencia = null;
                    $tipoMangaNombre = null;
                    $tipoBrocheNombre = null;
                    
                    try {
                        if ($prenda->color_id) {
                            $color = \App\Models\ColorPrenda::find($prenda->color_id);
                            $colorNombre = $color ? $color->nombre : null;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error obteniendo color', ['error' => $e->getMessage()]);
                    }
                    
                    try {
                        if ($prenda->tela_id) {
                            $tela = \App\Models\TelaPrenda::find($prenda->tela_id);
                            if ($tela) {
                                $telaNombre = $tela->nombre;
                                $telaReferencia = $tela->referencia;
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error obteniendo tela', ['error' => $e->getMessage()]);
                    }
                    
                    try {
                        if ($prenda->tipo_manga_id) {
                            $tipoManga = \App\Models\TipoManga::find($prenda->tipo_manga_id);
                            $tipoMangaNombre = $tipoManga ? $tipoManga->nombre : null;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error obteniendo manga', ['error' => $e->getMessage()]);
                    }
                    
                    try {
                        if ($prenda->tipo_broche_id) {
                            $tipoBroche = \App\Models\TipoBroche::find($prenda->tipo_broche_id);
                            $tipoBrocheNombre = $tipoBroche ? $tipoBroche->nombre : null;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error obteniendo broche', ['error' => $e->getMessage()]);
                    }
                    
                    $prendasFormato[] = [
                        'numero' => $index + 1,
                        'nombre' => $prenda->nombre_prenda ?? '-',
                        'descripcion' => $prenda->descripcion ?? '-',
                        'descripcion_variaciones' => $prenda->descripcion_variaciones ?? '',
                        'cantidad_talla' => $prenda->cantidad_talla ?? '-',
                        // Agregar datos de relaciones para generar descripción dinámica
                        'color' => $colorNombre,
                        'tela' => $telaNombre,
                        'tela_referencia' => $telaReferencia,
                        'tipo_manga' => $tipoMangaNombre,
                        'tipo_broche' => $tipoBrocheNombre,
                        'tiene_bolsillos' => $prenda->tiene_bolsillos ?? 0,
                        'tiene_reflectivo' => $prenda->tiene_reflectivo ?? 0,
                    ];
                }
                
                \Log::info('📋 [getOrderDetails] Prendas formateadas', [
                    'pedido' => $pedido,
                    'total_prendas' => count($prendasFormato),
                    'primera_prenda' => $prendasFormato[0] ?? null,
                ]);
                
                $orderArray['prendas'] = $prendasFormato;
            }
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo prendas: ' . $e->getMessage());
            $orderArray['prendas'] = [];
        }
        
        // Eliminar campos ocultos globales
        foreach ($camposOcultosGlobal as $campo) {
            unset($orderArray[$campo]);
        }
        
        // Eliminar campos sensibles para no-asesores
        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($orderArray[$campo]);
            }
        }
        
        return response()->json($orderArray);
    }

    /**
     * Obtener próximo número de pedido
     * GET /registros/get-next-pedido
     */
    public function getNextPedido()
    {
        // Este método será movido a RegistroOrdenController (CRUD)
        // Aquí solo como referencia
        throw new \BadMethodCallException('Use RegistroOrdenController::getNextPedido()');
    }

    /**
     * Validar número de pedido
     * POST /registros/validate-pedido
     */
    public function validatePedido(Request $request)
    {
        // Este método será movido a RegistroOrdenController (CRUD)
        // Aquí solo como referencia
        throw new \BadMethodCallException('Use RegistroOrdenController::validatePedido()');
    }

    /**
     * Obtener imágenes de una orden
     * GET /registros/{pedido}/images
     */
    public function getOrderImages($pedido)
    {
        try {
            $images = [];

            \Log::info('🖼️ [getOrderImages] Iniciando búsqueda de imágenes', [
                'pedido' => $pedido
            ]);

            // Obtener desde PedidoProduccion
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $pedido)->first();
            
            \Log::info('🖼️ [getOrderImages] Pedido encontrado', [
                'pedido_id' => $pedidoProduccion?->id,
                'cotizacion_id' => $pedidoProduccion?->cotizacion_id
            ]);

            // Helper para normalizar rutas a URL públicas
            $normalize = function ($ruta) {
                if (empty($ruta)) return null;
                if (str_starts_with($ruta, 'http')) {
                    return $ruta;
                }
                if (str_starts_with($ruta, '/storage/')) {
                    return $ruta;
                }
                return '/storage/' . ltrim($ruta, '/');
            };

            // 1) Incluir imágenes asociadas a la cotización (si existe)
            if ($pedidoProduccion && $pedidoProduccion->cotizacion_id) {
                $cotizacion = Cotizacion::find($pedidoProduccion->cotizacion_id);
                if ($cotizacion && $cotizacion->imagenes) {
                    $cotImages = is_array($cotizacion->imagenes) ? $cotizacion->imagenes : (json_decode($cotizacion->imagenes, true) ?? []);
                    foreach ($cotImages as $ci) {
                        // Soportar formatos: string URL ó objeto/array con campo 'url'
                        $raw = null;
                        if (is_string($ci)) {
                            $raw = $ci;
                        } elseif (is_array($ci) && isset($ci['url'])) {
                            $raw = $ci['url'];
                        } elseif (is_object($ci) && isset($ci->url)) {
                            $raw = $ci->url;
                        }

                        $url = $normalize($raw);
                        if ($url) {
                            $images[] = [
                                'url' => $url,
                                'type' => 'cotizacion'
                            ];
                        }
                    }
                }
            }

            // 2) Incluir imágenes guardadas por prenda en el pedido (AGRUPADAS POR PRENDA)
            try {
                $prendas = \DB::table('prendas_pedido')
                    ->where('numero_pedido', $pedido)
                    ->orderBy('id', 'asc')
                    ->get(['id', 'nombre_prenda']);

                \Log::info('🖼️ [getOrderImages] Prendas encontradas', [
                    'total_prendas' => $prendas->count()
                ]);

                $prendasConImagenes = [];
                
                foreach ($prendas as $index => $prenda) {
                    $imagenesPrend = [];
                    
                    // Fotos de prenda
                    $fotosPrenda = \DB::table('prenda_fotos_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden', 'asc')
                        ->get(['ruta_webp', 'ruta_original', 'ruta_miniatura', 'orden']);

                    foreach ($fotosPrenda as $fp) {
                        $ruta = $fp->ruta_webp ?? $fp->ruta_original ?? $fp->ruta_miniatura ?? null;
                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'prenda',
                                'orden' => $fp->orden
                            ];
                        }
                    }

                    // Fotos de tela
                    $fotosTela = \DB::table('prenda_fotos_tela_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden', 'asc')
                        ->get(['ruta_webp', 'ruta_original', 'ruta_miniatura', 'orden']);

                    foreach ($fotosTela as $ft) {
                        $ruta = $ft->ruta_webp ?? $ft->ruta_original ?? $ft->ruta_miniatura ?? null;
                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'tela',
                                'orden' => $ft->orden
                            ];
                        }
                    }

                    // Fotos de logo
                    $fotosLogo = \DB::table('prenda_fotos_logo_pedido')
                        ->where('prenda_pedido_id', $prenda->id)
                        ->orderBy('orden', 'asc')
                        ->get(['ruta_webp', 'ruta_original', 'ruta_miniatura', 'orden']);

                    foreach ($fotosLogo as $fl) {
                        $ruta = $fl->ruta_webp ?? $fl->ruta_original ?? $fl->ruta_miniatura ?? null;
                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'logo',
                                'orden' => $fl->orden
                            ];
                        }
                    }
                    
                    // Solo agregar prenda si tiene imágenes
                    if (!empty($imagenesPrend)) {
                        $prendasConImagenes[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda,
                            'imagenes' => $imagenesPrend
                        ];
                    }
                }
                
                \Log::info('🖼️ [getOrderImages] Prendas con imágenes', [
                    'total_prendas_con_imagenes' => count($prendasConImagenes)
                ]);
                
            } catch (\Exception $inner) {
                \Log::warning('Error al consultar tablas de fotos de prenda: ' . $inner->getMessage(), ['pedido' => $pedido]);
            }

            \Log::info('🖼️ [getOrderImages] Resultado final', [
                'total_prendas' => count($prendasConImagenes ?? []),
                'total_images_cotizacion' => count($images)
            ]);

            return response()->json([
                'success' => true,
                'prendas' => $prendasConImagenes ?? [],
                'images_cotizacion' => $images,
                'pedido' => $pedido
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener imágenes de orden: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener imágenes'
            ], 500);
        }
    }

    /**
     * Obtener descripción de prendas
     * GET /registros/{pedido}/descripcion-prendas
     */
    public function getDescripcionPrendas($pedido)
    {
        try {
            // Buscar la orden por número de pedido o por ID
            $orden = PedidoProduccion::where('numero_pedido', $pedido)
                ->orWhere('id', $pedido)
                ->first();

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            // Obtener la descripción de prendas del modelo
            // El campo descripcion_prendas contiene la descripción armada
            $descripcionPrendas = $orden->descripcion_prendas ?? '';

            return response()->json([
                'success' => true,
                'descripcion_prendas' => $descripcionPrendas,
                'numero_pedido' => $orden->numero_pedido,
                'orden_id' => $orden->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener descripción de prendas: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener descripción de prendas'
            ], 500);
        }
    }

    /**
     * Calcular días de una orden
     * GET /registros/{pedido}/calcular-dias
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        try {
            // Validar entrada
            if (!$numeroPedido) {
                return response()->json(['error' => 'Número de pedido requerido'], 400);
            }

            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$orden) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            // Calcular días usando el servicio
            $resultado = CacheCalculosService::getTotalDiasBatch([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return response()->json([
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'total_dias' => intval($diasCalculados),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * Calcular días de múltiples órdenes
     * POST /registros/calcular-dias-batch
     */
    public function calcularDiasBatchAPI(Request $request)
    {
        try {
            // Validar entrada
            $numeroPedidos = $request->input('numero_pedidos', []);
            if (empty($numeroPedidos)) {
                return response()->json(['error' => 'Lista de pedidos requerida'], 400);
            }

            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Obtener todas las órdenes
            $ordenes = PedidoProduccion::whereIn('numero_pedido', $numeroPedidos)->get();
            if ($ordenes->isEmpty()) {
                return response()->json(['error' => 'No se encontraron órdenes'], 404);
            }

            // Calcular días para todas
            $resultados = CacheCalculosService::getTotalDiasBatch($ordenes->toArray(), $festivos);

            // Formatear respuesta
            $dias = [];
            foreach ($numeroPedidos as $pedido) {
                $dias[$pedido] = intval($resultados[$pedido] ?? 0);
            }

            return response()->json([
                'success' => true,
                'dias' => $dias,
                'total' => count($dias),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasBatchAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }
}
