<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Constants\AreaOptions;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Models\LogoPedido;
use App\Models\LogoCotizacion;
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
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
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
        
        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            $todasOrdenes = $query->get();
            
            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);
            
            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
                $match = in_array((int)$totalDias, $filterTotalDias, true);
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
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // Verificar que todas las órdenes tengan un valor
            foreach ($ordenes->items() as $orden) {
                if (!isset($totalDiasCalculados[$orden->numero_pedido])) {
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
        // Verificar si la tabla LogoPedido existe antes de consultarla
        try {
            if (!\Schema::hasTable('logo_pedidos')) {
                $logoPedido = null;
            } else {
                // Primero, intentar buscar en LogoPedido
                $logoPedido = \App\Models\LogoPedido::where('numero_pedido', $pedido)->first();
            }
        } catch (\Exception $e) {
            $logoPedido = null;
        }
        
        if ($logoPedido) {
            // Es un LogoPedido, devolverlo con toda su información
            $logoPedidoArray = $logoPedido->toArray();
            
            // PASO 1: Intentar completar desde PedidoProduccion
            if ($logoPedido->pedido_id) {
                try {
                    $pedidoProd = \App\Models\PedidoProduccion::with('asesora')->find($logoPedido->pedido_id);
                    
                    if ($pedidoProd) {
                        \Log::info(' Encontrado PedidoProduccion, completando datos', [
                            'pedido_id' => $logoPedido->pedido_id,
                            'cliente' => $pedidoProd->cliente,
                            'asesora' => $pedidoProd->asesora?->name,
                            'fecha' => $pedidoProd->fecha_de_creacion_de_orden
                        ]);
                        
                        // Completar desde el pedido de producción - SIEMPRE si viene vacío
                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $pedidoProd->cliente ?? '-';
                            \Log::info(' [PASO 1] Cliente completado desde PedidoProduccion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $asesoraName = $pedidoProd->asesora?->name ?? $pedidoProd->asesor?->name ?? '-';
                            $logoPedidoArray['asesora'] = $asesoraName;
                            \Log::info(' [PASO 1] Asesora completada desde PedidoProduccion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                            $logoPedidoArray['fecha_de_creacion_de_orden'] = $pedidoProd->fecha_de_creacion_de_orden;
                            \Log::info(' [PASO 1] Fecha completada desde PedidoProduccion', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $pedidoProd->descripcion_prendas) {
                            $logoPedidoArray['descripcion'] = $pedidoProd->descripcion_prendas;
                            \Log::info(' [PASO 1] Descripción completada desde PedidoProduccion');
                        }
                    } else {
                        \Log::warning(' [PASO 1] PedidoProduccion no encontrado', ['pedido_id' => $logoPedido->pedido_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error(' [PASO 1] Error al buscar PedidoProduccion', ['error' => $e->getMessage()]);
                }
            }
            
            // PASO 2: Si aún falta info, intentar desde LogoCotizacion
            if ($logoPedido->logo_cotizacion_id && (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-')) {
                try {
                    $logoCot = \App\Models\LogoCotizacion::with('cotizacion')->find($logoPedido->logo_cotizacion_id);
                    
                    if ($logoCot && $logoCot->cotizacion) {
                        \Log::info(' Encontrado LogoCotizacion, completando datos', [
                            'cliente' => $logoCot->cotizacion->cliente,
                            'fecha' => $logoCot->cotizacion->fecha_de_creacion
                        ]);
                        
                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $logoCot->cotizacion->cliente ?? '-';
                            \Log::info(' [PASO 2] Cliente completado desde LogoCotizacion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                            $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoCot->cotizacion->fecha_de_creacion;
                            \Log::info(' [PASO 2] Fecha completada desde LogoCotizacion', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $logoPedidoArray['asesora'] = $logoCot->cotizacion->asesor?->name ?? '-';
                            \Log::info(' [PASO 2] Asesora completada desde LogoCotizacion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $logoCot->descripcion) {
                            $logoPedidoArray['descripcion'] = $logoCot->descripcion;
                            \Log::info(' [PASO 2] Descripción completada desde LogoCotizacion');
                        }
                    } else {
                        \Log::warning(' [PASO 2] LogoCotizacion no encontrado o sin cotización', ['logo_cotizacion_id' => $logoPedido->logo_cotizacion_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error(' [PASO 2] Error al buscar LogoCotizacion', ['error' => $e->getMessage()]);
                }
            }
            
            // PASO 3: Asegurar valores finales
            $logoPedidoArray['numero_pedido'] = $logoPedido->numero_pedido ?? $pedido;
            $logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
            $logoPedidoArray['asesora'] = $logoPedidoArray['asesora'] ?: '-';
            $logoPedidoArray['descripcion'] = $logoPedido->descripcion ?? '';
            
            //  IMPORTANTE: Si no hay fecha_de_creacion_de_orden, usar created_at
            if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoPedido->created_at ?? now();
                \Log::info(' [PASO 3] Fecha asignada desde created_at', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
            }
            
            $logoPedidoArray['encargado_orden'] = $logoPedido->encargado_orden ?? '-';
            $logoPedidoArray['forma_de_pago'] = $logoPedido->forma_de_pago ?? '-';
            $logoPedidoArray['observaciones'] = $logoPedido->observaciones ?? '';
            $logoPedidoArray['estado'] = $logoPedido->estado ?? '-';
            $logoPedidoArray['area'] = $logoPedido->area ?? '-';
            $logoPedidoArray['tecnicas'] = $logoPedido->tecnicas ?? [];
            $logoPedidoArray['ubicaciones'] = $logoPedido->ubicaciones ?? [];
            $logoPedidoArray['prendas'] = $logoPedido->prendas ?? [];
            
            // Campos de identificación
            $logoPedidoArray['es_cotizacion'] = false;
            $logoPedidoArray['es_logo_pedido'] = true;
            
            \Log::info(' [RegistroOrdenQueryController::show] LogoPedido finalizado COMPLETAMENTE', [
                'numero_pedido' => $logoPedidoArray['numero_pedido'],
                'cliente' => $logoPedidoArray['cliente'],
                'asesora' => $logoPedidoArray['asesora'],
                'descripcion' => $logoPedidoArray['descripcion'],
                'fecha_de_creacion_de_orden' => $logoPedidoArray['fecha_de_creacion_de_orden'],
                'forma_de_pago' => $logoPedidoArray['forma_de_pago'],
                'encargado_orden' => $logoPedidoArray['encargado_orden'],
            ]);
            
            return response()->json($logoPedidoArray);
        }
        
        // Si no es LogoPedido, buscar en PedidoProduccion
        $order = PedidoProduccion::with([
            'asesora',
            'cotizacion.tipoCotizacion'
        ])->where('numero_pedido', $pedido)->firstOrFail();

        // Obtener estadísticas mediante servicio
        $stats = $this->statsService->getOrderStats($pedido);
        $order->total_cantidad = $stats['total_cantidad'];
        $order->total_entregado = $stats['total_entregado'];

        //  CARGAR prendas CON relaciones ANTES de toArray()
        // Hacemos un query directo para asegurar que las relaciones se cargan
        $prendasConRelaciones = \App\Models\PrendaPedido::where('pedido_produccion_id', $order->id)
            ->with([
                'fotos', 
                'tallas', 
                'procesos.tipoProceso', 
                'procesos.imagenes'
            ])
            ->orderBy('id', 'asc')
            ->get();
        
        \Log::info(' [show] Prendas cargadas con relaciones', [
            'pedido' => $pedido,
            'total' => $prendasConRelaciones->count(),
            'primera_prenda' => $prendasConRelaciones->first() ? [
                'nombre' => $prendasConRelaciones->first()->nombre_prenda,
                'fotos_loaded' => $prendasConRelaciones->first()->relationLoaded('fotos'),
                'tallas_loaded' => $prendasConRelaciones->first()->relationLoaded('tallas'),
                'variantes_loaded' => $prendasConRelaciones->first()->relationLoaded('variantes'),
                'variantes_count' => $prendasConRelaciones->first()->variantes ? $prendasConRelaciones->first()->variantes->count() : 0,
                'procesos_loaded' => $prendasConRelaciones->first()->relationLoaded('procesos')
            ] : 'N/A',
        ]);
        
        // Reemplazar prendas en la orden con las que tienen relaciones
        $order->setRelation('prendas', $prendasConRelaciones);

        //  CONSTRUIR DESCRIPCIÓN MIENTRAS AÚN TENEMOS ACCESO A RELACIONES ELOQUENT
        $descripcionPrendas = $this->buildDescripcionConTallas($order);
        
        \Log::info(' [show] Descripción construida', [
            'longitud' => strlen($descripcionPrendas),
            'primeras_200_caracteres' => substr($descripcionPrendas, 0, 200),
            'contiene_font_size_15' => strpos($descripcionPrendas, 'font-size: 15px') !== false,
            'contiene_important' => strpos($descripcionPrendas, '!important') !== false,
            'HTML_completo' => $descripcionPrendas,
        ]);

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
        
        // Agregar la descripción ya construida
        $orderArray['descripcion_prendas'] = $descripcionPrendas;
        
        // Obtener prendas formateadas para el modal
        \Log::info(' [getOrderDetails] Obteniendo prendas para pedido', [
            'pedido' => $pedido,
            'es_cotizacion' => $esCotizacion,
        ]);
        
        try {
            // Usar prendas YA cargadas con relaciones
            {
                $prendas = $order->prendas;

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

                // Formatear prendas con todos los datos necesarios
                $prendasFormato = [];
                foreach ($prendas as $index => $prenda) {
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

                    //  NUEVO: Normalizar fotos de prenda (WebP)
                    $fotosNormalizadas = [];
                    if ($prenda->fotos) {
                        foreach ($prenda->fotos as $foto) {
                            $ruta = $foto->ruta_webp ?? $foto->ruta_original;
                            $fotosNormalizadas[] = $normalize($ruta);
                        }
                    }
                    
                    //  NUEVO: Normalizar fotos de tela (WebP) - usando consulta directa
                    $telaFotosNormalizadas = [];
                    try {
                        $fotosTelaDB = \DB::table('prenda_fotos_tela_pedido')
                            ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
                            ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prenda->id)
                            ->orderBy('prenda_fotos_tela_pedido.orden', 'asc')
                            ->get(['prenda_fotos_tela_pedido.ruta_webp', 'prenda_fotos_tela_pedido.ruta_original']);
                        
                        foreach ($fotosTelaDB as $fotoTela) {
                            $ruta = $fotoTela->ruta_webp ?? $fotoTela->ruta_original;
                            $telaFotosNormalizadas[] = $normalize($ruta);
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error cargando fotos de tela para prenda ' . $prenda->id . ': ' . $e->getMessage());
                    }
                    
                    $prendasFormato[] = [
                        'id' => $prenda->id,
                        'prenda_pedido_id' => $prenda->id,
                        'numero' => $index + 1,
                        'nombre' => $prenda->nombre_prenda ?? '-',
                        'nombre_prenda' => $prenda->nombre_prenda ?? '-',
                        'descripcion' => $prenda->descripcion ?? '-',
                        'tallas' => $prenda->tallas ? $prenda->tallas->map(function($t) { 
                            return "{$t->talla}:{$t->cantidad}"; 
                        })->implode(', ') : '-',
                        // Los datos de color, tela, etc. ahora vienen de relaciones separadas
                        'fotos' => $fotosNormalizadas,
                        'tela_fotos' => $telaFotosNormalizadas,
                    ];
                }
                
                \Log::info(' [getOrderDetails] Prendas formateadas', [
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
     * Parámetro opcional: tipo=logo para obtener solo imágenes de logo
     */
    public function getOrderImages($pedido)
    {
        try {
            $tipo = request()->query('tipo'); // 'logo' o null
            $images = [];

            \Log::info(' [getOrderImages] Iniciando búsqueda de imágenes', [
                'pedido' => $pedido,
                'tipo' => $tipo
            ]);

            // Obtener desde PedidoProduccion
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $pedido)->first();
            
            \Log::info(' [getOrderImages] Pedido encontrado', [
                'pedido_id' => $pedidoProduccion?->id,
                'cotizacion_id' => $pedidoProduccion?->cotizacion_id
            ]);

            // Helper para normalizar rutas a URL públicas
            $normalize = function ($ruta) {
                if (empty($ruta)) return null;
                
                // Si ya es una URL completa, devolverla tal cual
                if (str_starts_with($ruta, 'http')) {
                    return $ruta;
                }
                
                // Si ya comienza con /storage/, devolverla tal cual (ya está correcta)
                if (str_starts_with($ruta, '/storage/')) {
                    return $ruta;
                }
                
                // Si comienza con storage/ (sin /), agregar / al inicio
                if (str_starts_with($ruta, 'storage/')) {
                    return '/' . $ruta;
                }
                
                // Si es una ruta relativa (ej: pedidos/2695/prendas/...), agregar /storage/
                return '/storage/' . ltrim($ruta, '/');
            };

            // Si el tipo es 'logo', devolver solo imágenes de logo desde logo_pedido_imagenes
            if ($tipo === 'logo') {
                return $this->getLogoImages($pedido, $normalize);
            }

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

                \Log::info(' [getOrderImages] Prendas encontradas', [
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

                    \Log::info('[getOrderImages] Fotos de prenda encontradas', [
                        'prenda_id' => $prenda->id,
                        'cantidad' => $fotosPrenda->count()
                    ]);

                    foreach ($fotosPrenda as $fp) {
                        $ruta = $fp->ruta_webp ?? $fp->ruta_original ?? $fp->ruta_miniatura ?? null;
                        \Log::info('[getOrderImages] Foto de prenda - Datos en BD', [
                            'ruta_webp' => $fp->ruta_webp,
                            'ruta_original' => $fp->ruta_original,
                            'ruta_miniatura' => $fp->ruta_miniatura,
                            'ruta_seleccionada' => $ruta
                        ]);
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

                    \Log::info('[getOrderImages] Fotos de tela encontradas', [
                        'prenda_id' => $prenda->id,
                        'cantidad' => $fotosTela->count()
                    ]);

                    foreach ($fotosTela as $ft) {
                        $ruta = $ft->ruta_webp ?? $ft->ruta_original ?? $ft->ruta_miniatura ?? null;
                        \Log::info('[getOrderImages] Foto de tela - Datos en BD', [
                            'ruta_webp' => $ft->ruta_webp,
                            'ruta_original' => $ft->ruta_original,
                            'ruta_miniatura' => $ft->ruta_miniatura,
                            'ruta_seleccionada' => $ruta
                        ]);
                        $url = $normalize($ruta);
                        if ($url) {
                            $imagenesPrend[] = [
                                'url' => $url,
                                'type' => 'tela',
                                'orden' => $ft->orden
                            ];
                        }
                    }

                    //  SOLO incluir fotos de logo si tipo=logo (ya manejado arriba)
                    // Para el modal de costura, NO incluir fotos de logo
                    // Las fotos de logo se obtienen con tipo=logo en getLogoImages()
                    
                    // Solo agregar prenda si tiene imágenes
                    if (!empty($imagenesPrend)) {
                        $prendasConImagenes[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda,
                            'imagenes' => $imagenesPrend
                        ];
                    }
                }
                
                \Log::info(' [getOrderImages] Prendas con imágenes', [
                    'total_prendas_con_imagenes' => count($prendasConImagenes)
                ]);
                
            } catch (\Exception $inner) {
                \Log::warning('Error al consultar tablas de fotos de prenda: ' . $inner->getMessage(), ['pedido' => $pedido]);
            }

            \Log::info(' [getOrderImages] Resultado final', [
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

    /**
     * Calcular fecha estimada de entrega
     * POST /api/registros/{id}/calcular-fecha-estimada
     */
    public function calcularFechaEstimada(Request $request, $id)
    {
        try {
            // Validar entrada
            $validated = $request->validate([
                'dia_de_entrega' => 'required|integer|min:1'
            ]);

            // Obtener la orden
            $orden = PedidoProduccion::findOrFail($id);

            if (!$orden->fecha_de_creacion_de_orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no tiene fecha de creación'
                ], 400);
            }

            // Asignar temporalmente el día de entrega para calcular
            $orden->dia_de_entrega = $validated['dia_de_entrega'];
            
            // Calcular la fecha estimada
            $fechaEstimada = $orden->calcularFechaEstimada();

            if (!$fechaEstimada) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo calcular la fecha estimada'
                ], 400);
            }

            \Log::info("Fecha estimada calculada para pedido {$orden->numero_pedido}", [
                'dias' => $validated['dia_de_entrega'],
                'fecha_estimada' => $fechaEstimada->format('d/m/Y'),
                'fecha_creacion' => $orden->fecha_de_creacion_de_orden->format('d/m/Y')
            ]);

            return response()->json([
                'success' => true,
                'fecha_estimada' => $fechaEstimada->format('d/m/Y'),
                'fecha_estimada_iso' => $fechaEstimada->toIso8601String(),
                'dias' => $validated['dia_de_entrega'],
                'fecha_creacion' => $orden->fecha_de_creacion_de_orden->format('d/m/Y')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en calcularFechaEstimada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular la fecha estimada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construir descripción con tallas por prenda (lógica del blade de asesores)
     * Maneja dos casos: REFLECTIVO y NORMAL
     * 
     * @param PedidoProduccion $order
     * @return string
     */
    private function buildDescripcionConTallas($order)
    {
        $descripcionConTallas = '';
        $descripcionBase = $order->descripcion_prendas ?? '';
        
        \Log::info(' [buildDescripcionConTallas] Descripción recibida:', [
            'pedido' => $order->numero_pedido,
            'longitud' => strlen($descripcionBase),
            'comienza_con' => substr($descripcionBase, 0, 100),
            'es_html' => strpos($descripcionBase, '<span') !== false,
            'contiene_important' => strpos($descripcionBase, '!important') !== false,
            'HTML_completo' => $descripcionBase,
        ]);
        
        //  SI YA TIENE SPANS HTML (descripción generada por Helper), DEVOLVERLA TAL CUAL
        if (strpos($descripcionBase, '<span') !== false) {
            \Log::info(' [buildDescripcionConTallas] Descripción HTML detectada, devolviendo tal cual');
            return $descripcionBase;
        }
        
        // VERIFICAR SI ES COTIZACIÓN TIPO REFLECTIVO
        $esReflectivo = false;
        if ($order->cotizacion && $order->cotizacion->tipoCotizacion) {
            $esReflectivo = ($order->cotizacion->tipoCotizacion->codigo === 'RF');
        }
        
        //  FALLBACK: Si descripción_prendas está vacía, generar dinámicamente desde las prendas
        if (empty($descripcionBase) && $order->prendas && $order->prendas->count() > 0) {
            \Log::info(' [buildDescripcionConTallas] Generando descripción dinámicamente', [
                'pedido' => $order->numero_pedido,
                'total_prendas' => $order->prendas->count(),
            ]);
            
            $descripciones = $order->prendas->map(function($prenda, $index) {
                \Log::info('  🧥 [Prenda ' . ($index + 1) . '] Datos disponibles:', [
                    'nombre' => $prenda->nombre_prenda,
                    'color_id' => $prenda->color_id,
                    'color_loaded' => $prenda->relationLoaded('color'),
                    'color_nombre' => $prenda->color ? $prenda->color->nombre : 'NULL',
                    'tela_id' => $prenda->tela_id,
                    'tela_loaded' => $prenda->relationLoaded('tela'),
                    'tela_nombre' => $prenda->tela ? $prenda->tela->nombre : 'NULL',
                    'cantidad_talla' => is_array($prenda->cantidad_talla) ? count($prenda->cantidad_talla) . ' tallas' : 'NULL',
                    'descripcion_variaciones_length' => strlen($prenda->descripcion_variaciones ?? ''),
                ]);
                return $prenda->generarDescripcionDetallada($index + 1);
            })->toArray();
            
            $descripcionBase = implode("\n\n", $descripciones);
            
            \Log::info(' [buildDescripcionConTallas] Descripción generada', [
                'longitud' => strlen($descripcionBase),
                'primeras_lineas' => substr($descripcionBase, 0, 200),
            ]);
        }
        
        if (!empty($descripcionBase) || ($esReflectivo && $order->prendas && $order->prendas->count() > 0)) {
            if ($esReflectivo) {
                // CASO REFLECTIVO: Usar descripción tal cual (ya contiene tallas y cantidad total)
                $descripcionConTallas = '';
                
                \Log::info(' [REFLECTIVO] Construyendo descripción reflectivo', [
                    'pedido' => $order->numero_pedido,
                    'esReflectivo' => $esReflectivo,
                    'total_prendas' => $order->prendas ? $order->prendas->count() : 0,
                ]);
                
                if ($order->prendas && $order->prendas->count() > 0) {
                    foreach ($order->prendas as $index => $prenda) {
                        \Log::info('  🧥 PRENDA ' . ($index + 1), [
                            'nombre' => $prenda->nombre_prenda,
                            'descripcion_length' => strlen($prenda->descripcion ?? ''),
                            'cantidad_talla' => $prenda->cantidad_talla,
                            'tiene_reflectivo' => $prenda->reflectivo ? 'SI' : 'NO',
                        ]);
                        
                        if ($index > 0) {
                            $descripcionConTallas .= "\n\n";
                        }
                        
                        //  NUEVO: Si tiene registro en prendas_reflectivo, usar esa información
                        if ($prenda->reflectivo) {
                            $reflectivo = $prenda->reflectivo;
                            
                            // Nombre del producto
                            if (!empty($reflectivo->nombre_producto)) {
                                $descripcionConTallas .= "PRENDA REFLECTIVO: " . strtoupper($reflectivo->nombre_producto);
                            } else {
                                $descripcionConTallas .= "PRENDA REFLECTIVO: " . $prenda->nombre_prenda;
                            }
                            
                            // Descripción
                            if (!empty($reflectivo->descripcion)) {
                                $descripcionConTallas .= "\n" . $reflectivo->descripcion;
                            }
                            
                            // Géneros
                            if ($reflectivo->generos && is_array($reflectivo->generos)) {
                                $generosStr = implode(', ', array_map('ucfirst', $reflectivo->generos));
                                $descripcionConTallas .= "\nGENEROS: " . $generosStr;
                            }
                            
                            // Tallas por género (estructura: {genero: {talla: cantidad}})
                            if ($reflectivo->cantidad_talla && is_array($reflectivo->cantidad_talla)) {
                                foreach ($reflectivo->cantidad_talla as $genero => $tallas) {
                                    if (is_array($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTALLAS " . strtoupper($genero) . ": " . implode(', ', $tallasTexto);
                                        }
                                    }
                                }
                            }
                            
                            // Ubicaciones
                            if ($reflectivo->ubicaciones && is_array($reflectivo->ubicaciones)) {
                                foreach ($reflectivo->ubicaciones as $ubicacion) {
                                    $ubicDesc = "UBICACION: " . ($ubicacion['nombre'] ?? '');
                                    if (!empty($ubicacion['observaciones'])) {
                                        $ubicDesc .= " - " . $ubicacion['observaciones'];
                                    }
                                    $descripcionConTallas .= "\n" . $ubicDesc;
                                }
                            }
                            
                            // Observaciones generales
                            if (!empty($reflectivo->observaciones_generales)) {
                                $descripcionConTallas .= "\nOBSERVACIONES: " . $reflectivo->observaciones_generales;
                            }
                            
                            \Log::info(' Información de REFLECTIVO agregada a descripción', [
                                'nombre_producto' => $reflectivo->nombre_producto,
                                'generos' => $reflectivo->generos,
                                'ubicaciones_count' => count($reflectivo->ubicaciones ?? []),
                            ]);
                        } else {
                            // CASO ANTIGUO: Sin reflectivo, usar descripción normal
                            if (!empty($prenda->descripcion)) {
                                $descripcionConTallas .= $prenda->descripcion;
                            }
                            
                            //  AGREGAR TALLAS SI NO ESTÁN EN LA DESCRIPCIÓN
                            if ($prenda->cantidad_talla) {
                                try {
                                    $tallas = is_string($prenda->cantidad_talla) 
                                        ? json_decode($prenda->cantidad_talla, true) 
                                        : $prenda->cantidad_talla;
                                    
                                    if (is_array($tallas) && !empty($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('     Error decodificando tallas: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            } else {
                // CASO NORMAL: Parsear por "PRENDA X:"
                if (strpos($descripcionBase, 'PRENDA ') !== false) {
                    $prendas = explode('PRENDA ', $descripcionBase);
                    $prendasCount = 0;
                    
                    foreach ($prendas as $index => $prendaBlock) {
                        if ($index === 0 && empty(trim($prendaBlock))) {
                            continue;
                        }
                        
                        $prendaBlock = trim($prendaBlock);
                        if (empty($prendaBlock)) {
                            continue;
                        }
                        
                        preg_match('/^(\d+):/', $prendaBlock, $matches);
                        $numPrenda = isset($matches[1]) ? intval($matches[1]) : ($prendasCount + 1);
                        
                        $descripcionConTallas .= "PRENDA " . $prendaBlock;
                        
                        if ($order->prendas && $order->prendas->count() > 0) {
                            $prendaActual = $order->prendas->where('numero_prenda', $numPrenda)->first();
                            
                            if (!$prendaActual && $prendasCount < $order->prendas->count()) {
                                $prendaActual = $order->prendas[$prendasCount];
                            }
                            
                            if ($prendaActual && $prendaActual->cantidad_talla) {
                                try {
                                    $tallas = is_string($prendaActual->cantidad_talla) 
                                        ? json_decode($prendaActual->cantidad_talla, true) 
                                        : $prendaActual->cantidad_talla;
                                    
                                    if (is_array($tallas) && !empty($tallas)) {
                                        $tallasTexto = [];
                                        foreach ($tallas as $talla => $cantidad) {
                                            if ($cantidad > 0) {
                                                $tallasTexto[] = "$talla: $cantidad";
                                            }
                                        }
                                        if (!empty($tallasTexto)) {
                                            $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // Continuar sin tallas
                                }
                            }
                        }
                        
                        $prendasCount++;
                        if ($prendasCount < count($prendas)) {
                            $descripcionConTallas .= "\n\n";
                        }
                    }
                } else {
                    // Descripción sin formato PRENDA
                    $descripcionConTallas = $descripcionBase;
                    
                    if ($order->prendas && $order->prendas->count() > 0) {
                        $prendaActual = $order->prendas->first();
                        
                        if ($prendaActual && $prendaActual->cantidad_talla) {
                            try {
                                $tallas = is_string($prendaActual->cantidad_talla) 
                                    ? json_decode($prendaActual->cantidad_talla, true) 
                                    : $prendaActual->cantidad_talla;
                                
                                if (is_array($tallas) && !empty($tallas)) {
                                    $tallasTexto = [];
                                    foreach ($tallas as $talla => $cantidad) {
                                        if ($cantidad > 0) {
                                            $tallasTexto[] = "$talla: $cantidad";
                                        }
                                    }
                                    if (!empty($tallasTexto)) {
                                        $descripcionConTallas .= "\n\nTallas: " . implode(', ', $tallasTexto);
                                    }
                                }
                            } catch (\Exception $e) {
                                // Continuar sin tallas
                            }
                        }
                    }
                }
            }
        }
        
        if (empty($descripcionConTallas)) {
            $descripcionConTallas = $descripcionBase;
        }
        
        return $descripcionConTallas;
    }

    /**
     * Obtener imágenes de logo desde prenda_fotos_logo_pedido
     * Busca las imágenes asociadas a las prendas del pedido
     */
    private function getLogoImages($pedido, $normalize)
    {
        try {
            \Log::info(' [getLogoImages] Iniciando búsqueda de imágenes de logo', [
                'numero_pedido' => $pedido
            ]);

            // Normalizar el número de pedido (agregar # si no lo tiene)
            $pedidoConHash = str_starts_with($pedido, '#') ? $pedido : '#' . $pedido;
            $pedidoSinHash = ltrim($pedido, '#');

            // Buscar logo_pedido por numero_pedido (con o sin #) o por ID
            $logoPedido = \DB::table('logo_pedidos')
                ->where(function($query) use ($pedidoConHash, $pedidoSinHash, $pedido) {
                    $query->where('numero_pedido', $pedidoConHash)
                          ->orWhere('numero_pedido', $pedidoSinHash)
                          ->orWhere('id', $pedido);
                })
                ->first(['id', 'numero_pedido', 'pedido_id', 'cliente', 'asesora', 'forma_de_pago']);

            \Log::info(' [getLogoImages] Logo pedido encontrado', [
                'logo_pedido_id' => $logoPedido?->id,
                'pedido_id' => $logoPedido?->pedido_id,
                'numero_pedido' => $logoPedido?->numero_pedido
            ]);

            $logos = [];
            
            if ($logoPedido && $logoPedido->pedido_id) {
                // Obtener el numero_pedido del pedido_produccion
                $pedidoProduccion = \DB::table('pedidos_produccion')
                    ->where('id', $logoPedido->pedido_id)
                    ->first(['numero_pedido']);
                
                if ($pedidoProduccion) {
                    // Obtener prendas del pedido
                    $prendas = \DB::table('prendas_pedido')
                        ->where('numero_pedido', $pedidoProduccion->numero_pedido)
                        ->get(['id', 'nombre_prenda']);
                    
                    \Log::info(' [getLogoImages] Prendas encontradas', [
                        'total' => $prendas->count()
                    ]);
                    
                    // Obtener imágenes de cada prenda
                    foreach ($prendas as $prenda) {
                        $imagenes = \DB::table('prenda_fotos_logo_pedido')
                            ->where('prenda_pedido_id', $prenda->id)
                            ->orderBy('orden', 'asc')
                            ->get(['ruta_original', 'ruta_webp', 'ubicacion', 'orden', 'ancho', 'alto']);
                        
                        if ($imagenes->count() > 0) {
                            $imagenesFormateadas = [];
                            foreach ($imagenes as $img) {
                                // Priorizar ruta_webp, luego ruta_original
                                $ruta = $img->ruta_webp ?? $img->ruta_original;
                                $url = $normalize($ruta);
                                
                                if ($url) {
                                    $imagenesFormateadas[] = [
                                        'url' => $url,
                                        'nombre' => basename($ruta),
                                        'orden' => $img->orden,
                                        'ancho' => $img->ancho,
                                        'alto' => $img->alto
                                    ];
                                }
                            }
                            
                            if (!empty($imagenesFormateadas)) {
                                $logos[] = [
                                    'id' => $prenda->id,
                                    'titulo' => $prenda->nombre_prenda,
                                    'ubicacion' => $imagenes->first()->ubicacion ?? 'General',
                                    'imagenes' => $imagenesFormateadas
                                ];
                            }
                        }
                    }
                }
            }

            \Log::info(' [getLogoImages] Resultado final', [
                'total_logos' => count($logos),
                'total_imagenes' => collect($logos)->sum(fn($l) => count($l['imagenes'] ?? []))
            ]);

            return response()->json([
                'success' => true,
                'logos' => $logos,
                'pedido' => $pedido,
                'tipo' => 'logo'
            ]);

        } catch (\Exception $e) {
            \Log::error(' [getLogoImages] Error al obtener imágenes de logo: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener imágenes de logo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener LogoPedido por ID con fallback a relacionados
     * @route GET /api/logo-pedidos/{id}
     */
    public function showLogoPedidoById($id)
    {
        try {
            //  Buscar LogoPedido por ID
            $logoPedido = LogoPedido::find($id);
            
            if (!$logoPedido) {
                return response()->json([
                    'error' => 'LogoPedido no encontrado',
                    'id' => $id
                ], 404);
            }

            $logoPedidoArray = $logoPedido->toArray();
            
            \Log::info(' [API] showLogoPedidoById buscando ID: ' . $id, [
                'cliente' => $logoPedidoArray['cliente'] ?? null,
                'asesora' => $logoPedidoArray['asesora'] ?? null,
                'descripcion' => $logoPedidoArray['descripcion'] ?? null,
                'fecha_de_creacion_de_orden' => $logoPedidoArray['fecha_de_creacion_de_orden'] ?? null
            ]);

            //  PASO 1: Completar desde PedidoProduccion si LogoPedido está incompleto
            if ($logoPedido->pedido_id && empty($logoPedidoArray['cliente'])) {
                try {
                    $pedidoProduccion = PedidoProduccion::find($logoPedido->pedido_id);
                    if ($pedidoProduccion) {
                        if (empty($logoPedidoArray['cliente'])) {
                            $logoPedidoArray['cliente'] = $pedidoProduccion->cliente;
                        }
                        if (empty($logoPedidoArray['asesora']) && $pedidoProduccion->asesora) {
                            $logoPedidoArray['asesora'] = $pedidoProduccion->asesora->nombre ?? $pedidoProduccion->asesora->name;
                        }
                        if (empty($logoPedidoArray['descripcion'])) {
                            $logoPedidoArray['descripcion'] = $pedidoProduccion->descripcion;
                        }
                        if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                            $logoPedidoArray['fecha_de_creacion_de_orden'] = $pedidoProduccion->fecha_de_creacion_de_orden;
                        }
                        
                        \Log::info(' [PASO 1 API] Completados datos desde PedidoProduccion #' . $logoPedido->pedido_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning(' [PASO 1 API] Error al obtener PedidoProduccion: ' . $e->getMessage());
                }
            }

            //  PASO 2: Completar desde LogoCotizacion si aún hay campos vacíos
            if ($logoPedido->logo_cotizacion_id && empty($logoPedidoArray['descripcion'])) {
                try {
                    $logoCotizacion = LogoCotizacion::find($logoPedido->logo_cotizacion_id);
                    if ($logoCotizacion) {
                        if (empty($logoPedidoArray['descripcion'])) {
                            $logoPedidoArray['descripcion'] = $logoCotizacion->descripcion;
                        }
                        if (empty($logoPedidoArray['tecnicas'])) {
                            $logoPedidoArray['tecnicas'] = $logoCotizacion->tecnicas;
                        }
                        if (empty($logoPedidoArray['observaciones_tecnicas'])) {
                            $logoPedidoArray['observaciones_tecnicas'] = $logoCotizacion->observaciones_tecnicas;
                        }
                        if (empty($logoPedidoArray['ubicaciones'])) {
                            $logoPedidoArray['ubicaciones'] = $logoCotizacion->ubicaciones;
                        }
                        
                        \Log::info(' [PASO 2 API] Completados datos desde LogoCotizacion #' . $logoPedido->logo_cotizacion_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning(' [PASO 2 API] Error al obtener LogoCotizacion: ' . $e->getMessage());
                }
            }

            //  PASO 3: Garantizar fecha_de_creacion_de_orden usando created_at
            if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoPedido->created_at;
                \Log::info(' [PASO 3 API] Usando created_at como fecha de creación');
            }

            //  Responder con datos completos
            \Log::info(' [API] LogoPedido ID ' . $id . ' respondido correctamente', [
                'cliente' => $logoPedidoArray['cliente'],
                'asesora' => $logoPedidoArray['asesora'],
                'descripcion' => $logoPedidoArray['descripcion'],
                'fecha_de_creacion_de_orden' => $logoPedidoArray['fecha_de_creacion_de_orden'],
                'forma_de_pago' => $logoPedidoArray['forma_de_pago'],
                'encargado_orden' => $logoPedidoArray['encargado_orden']
            ]);
            
            return response()->json($logoPedidoArray);
            
        } catch (\Exception $e) {
            \Log::error(' [API] Error en showLogoPedidoById: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener LogoPedido por ID',
                'id' => $id,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /registros/{pedido}/recibos-datos
     * 
     * Obtener datos completos del pedido para el sistema de recibos
     * Delega a PedidoController.obtenerDetalleCompleto() que ya funciona correctamente
     * Compatible con el módulo PedidosRecibosModule
     */
    public function getRecibosDatos($pedido)
    {
        try {
            \Log::info(' [getRecibosDatos] Delegando a PedidoController.obtenerDetalleCompleto', [
                'pedido_numero' => $pedido
            ]);
            
            // Manejo especial para 'sin-numero'
            if ($pedido === 'sin-numero') {
                // Buscar pedidos que no tienen numero_pedido (es nulo o vacío)
                $pedidoModel = \App\Models\PedidoProduccion::whereNull('numero_pedido')
                    ->orWhere('numero_pedido', '')
                    ->orderBy('id', 'desc')
                    ->first();
                
                \Log::info(' [getRecibosDatos] Buscando pedido sin número', [
                    'encontrado' => $pedidoModel ? $pedidoModel->id : null
                ]);
            } else {
                // Si es numérico, buscar primero por ID (más eficiente)
                if (is_numeric($pedido)) {
                    $pedidoModel = \App\Models\PedidoProduccion::where('id', $pedido)->first();
                    \Log::info(' [getRecibosDatos] Buscando por ID numérico', [
                        'id' => $pedido,
                        'encontrado' => $pedidoModel ? true : false
                    ]);
                }
                
                // Si no encuentra por ID, intentar por numero_pedido
                if (!$pedidoModel) {
                    $pedidoModel = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
                    \Log::info(' [getRecibosDatos] Buscando por numero_pedido', [
                        'numero_pedido' => $pedido,
                        'encontrado' => $pedidoModel ? true : false
                    ]);
                }
            }
            
            if (!$pedidoModel) {
                return response()->json([
                    'error' => 'Pedido no encontrado',
                    'pedido' => $pedido
                ], 404);
            }
            
            $pedidoId = $pedidoModel->id;
            
            \Log::info(' [getRecibosDatos] Pedido encontrado, usando ID', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId
            ]);
            
            // Resolver PedidoQueryController correctamente desde el contenedor
            $pedidoController = app()->make(\App\Infrastructure\Http\Controllers\PedidoQueryController::class);
            
            // Detectar si viene de insumos para evitar filtro de de_bodega
            $esInsumos = request()->headers->get('referer') && str_contains(request()->headers->get('referer'), 'insumos/materiales');
            
            //  CRÍTICO: Activar filtrado específico en /registros (bordado, estampado, dtf, sublimado requieren aprobación)
            // PERO si viene de insumos, usar false para obtener todas las prendas sin filtro de de_bodega
            $filtrarProcesosPendientes = $esInsumos ? false : true;
            
            \Log::info(' [getRecibosDatos] Configuración de filtro', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId,
                'es_insumos' => $esInsumos,
                'filtrar_procesos_pendientes' => $filtrarProcesosPendientes
            ]);
            
            $response = $pedidoController->obtenerDetalleCompleto($pedidoId, $filtrarProcesosPendientes);
            $responseData = $response->getData(true);
            
            // Extraer datos de la estructura de respuesta
            $datos = $responseData['data'] ?? $responseData;

            try {
                $fechaCreacionOrden = $pedidoModel->fecha_de_creacion_de_orden ?? $pedidoModel->created_at ?? null;
                if ($fechaCreacionOrden) {
                    $datos['fecha_de_creacion_de_orden'] = $fechaCreacionOrden instanceof \DateTimeInterface
                        ? $fechaCreacionOrden->format('Y-m-d H:i:s')
                        : (string) $fechaCreacionOrden;
                }
            } catch (\Exception $e) {
                // silencioso
            }
            
            \Log::info(' [getRecibosDatos] ESTRUCTURA COMPLETA DE RESPUESTA', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId,
                'datos_completos' => $datos,
                'cliente' => $datos['cliente'] ?? 'N/A',
                'numero_pedido_en_datos' => $datos['numero_pedido'] ?? 'NO ENCONTRADO',
                'numero_en_datos' => $datos['numero'] ?? 'NO ENCONTRADO',
                'total_prendas' => isset($datos['prendas']) ? count($datos['prendas']) : 0
            ]);
            
            \Log::info(' [getRecibosDatos] Datos obtenidos de PedidoController', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId,
                'cliente' => $datos['cliente'] ?? 'N/A',
                'total_prendas' => isset($datos['prendas']) ? count($datos['prendas']) : 0
            ]);
            
            // PedidoController ya enriquece los datos correctamente
            // Solo retornar la respuesta
            return response()->json($datos);
            
        } catch (\Exception $e) {
            \Log::error(' [getRecibosDatos] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener datos de recibos',
                'pedido' => $pedido,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener el consecutivo de costura para un pedido
     */
    public function getConsecutivoCostura($pedido)
    {
        try {
            $prendaId = request()->query('prenda_id');

            $pedidoModel = null;
            if (is_numeric($pedido)) {
                $pedidoModel = \App\Models\PedidoProduccion::where('id', $pedido)->first();
            }
            if (!$pedidoModel) {
                $pedidoModel = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
            }
            $numeroPedido = $pedidoModel ? (int) $pedidoModel->numero_pedido : (int) $pedido;
            
            \Log::info(' [getConsecutivoCostura] Obteniendo consecutivo de costura para pedido', [
                'pedido' => $pedido,
                'prenda_id' => $prendaId
            ]);
            
            // Buscar el consecutivo de costura para el pedido, filtrando por prenda si se proporcionó
            $query = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedido)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1);
            
            if ($prendaId) {
                $query->where('prenda_id', $prendaId);
            }

            $registro = $query->orderByDesc('id')->first();
            $consecutivo = $registro->consecutivo_actual ?? null;
            $area = $registro->area ?? null;

            // Encargado real desde procesos_prenda (último proceso registrado)
            $encargado = null;
            $fechaInicioProceso = null;
            $fechaFinProceso = null;
            $procesoId = null;
            if ($consecutivo) {
                $procesoQuery = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                    ->whereNull('deleted_at')
                    ->where(function ($q) use ($prendaId, $consecutivo) {
                        if ($prendaId) {
                            $q->where('prenda_pedido_id', $prendaId);
                        }
                        $q->orWhere('numero_recibo', (int) $consecutivo);
                    })
                    ->orderByDesc('created_at');

                $ultimoProceso = $procesoQuery->first();
                if ($ultimoProceso) {
                    $procesoId = $ultimoProceso->id;
                    $encargado = $ultimoProceso->encargado;
                    $fechaInicioProceso = $ultimoProceso->fecha_inicio;
                    $fechaFinProceso = $ultimoProceso->fecha_fin;
                }
            }
            
            // Obtener la fecha de creación del pedido
            $fechaCreacion = \DB::table('pedidos_produccion')
                ->where('id', $pedido)
                ->value('fecha_de_creacion_de_orden');
            
            if ($consecutivo || $fechaCreacion) {
                \Log::info(' [getConsecutivoCostura] Datos encontrados', [
                    'pedido' => $pedido, 
                    'prenda_id' => $prendaId,
                    'consecutivo' => $consecutivo,
                    'area' => $area,
                    'encargado' => $encargado,
                    'fecha_inicio' => $fechaInicioProceso,
                    'fecha_fin' => $fechaFinProceso,
                    'fecha_creacion' => $fechaCreacion
                ]);
                
                return response()->json([
                    'success' => true,
                    'consecutivo' => $consecutivo,
                    'area' => $area,
                    'encargado' => $encargado,
                    'proceso_id' => $procesoId,
                    'fecha_inicio' => $fechaInicioProceso,
                    'fecha_fin' => $fechaFinProceso,
                    'fecha_creacion' => $fechaCreacion
                ]);
            } else {
                \Log::warning(' [getConsecutivoCostura] No se encontraron datos para el pedido', [
                    'pedido' => $pedido,
                    'prenda_id' => $prendaId
                ]);
                
                return response()->json([
                    'success' => false,
                    'consecutivo' => null,
                    'area' => null,
                    'encargado' => null,
                    'proceso_id' => null,
                    'fecha_inicio' => null,
                    'fecha_fin' => null,
                    'fecha_creacion' => null,
                    'message' => 'No se encontraron datos para este pedido'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error(' [getrecibosCostura] Error al obtener datos del pedido', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'consecutivo' => null,
                'area' => null,
                'encargado' => null,
                'proceso_id' => null,
                'fecha_inicio' => null,
                'fecha_fin' => null,
                'fecha_creacion' => null,
                'message' => 'Error al obtener datos del pedido'
            ], 500);
        }
    }

    /**
     * Obtener seguimiento por prenda para un pedido
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function getSeguimientoPorPrenda($pedido)
    {
        try {
            \Log::info('[getSeguimientoPorPrenda] Iniciando consulta', [
                'pedido_numero' => $pedido
            ]);
            
            // Buscar primero por ID (más eficiente), luego por numero_pedido
            $pedidoModel = null;
            if (is_numeric($pedido)) {
                $pedidoModel = \App\Models\PedidoProduccion::where('id', $pedido)->first();
            }
            if (!$pedidoModel) {
                $pedidoModel = \App\Models\PedidoProduccion::where('numero_pedido', $pedido)->first();
            }
            
            if (!$pedidoModel) {
                return response()->json([
                    'error' => 'Pedido no encontrado',
                    'pedido' => $pedido
                ], 404);
            }
            
            $pedidoId = $pedidoModel->id;
            
            \Log::info('[getSeguimientoPorPrenda] Pedido encontrado', [
                'numero_pedido' => $pedido,
                'pedido_id' => $pedidoId
            ]);
            
            // Obtener prendas del pedido
            $prendas = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedidoId)
                ->with(['variantes', 'procesos.tipoProceso', 'tallas'])
                ->get();
            
            $prendasConSeguimiento = [];
            
            foreach ($prendas as $prenda) {
                // Obtener todos los consecutivos de recibos para esta prenda (todos los tipos)
                $consecutivos = \App\Models\ConsecutivosRecibosPedidos::where('prenda_id', $prenda->id)
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('activo', 1)
                    ->get();

                // Derivar numero_recibo COSTURA (consecutivo_actual) para poder vincular procesos_prenda
                // cuando prenda_pedido_id viene NULL en procesos_prenda.
                $numeroReciboCostura = null;
                $reciboCosturaId = null;
                foreach ($consecutivos as $c) {
                    if (($c->tipo_recibo ?? null) === 'COSTURA' && !empty($c->consecutivo_actual)) {
                        $numeroReciboCostura = (int) $c->consecutivo_actual;
                        $reciboCosturaId = $c->id ?? null;
                        break;
                    }
                }

                // Obtener fechas de completado por área desde prenda_recibo_completado (si existe)
                $completadosPorArea = [];
                if (!empty($reciboCosturaId)) {
                    try {
                        $rowsCompletado = \DB::table('prenda_recibo_completado')
                            ->select(['area', 'fecha_completado'])
                            ->where('id_recibo', $reciboCosturaId)
                            ->get();

                        foreach ($rowsCompletado as $row) {
                            $key = strtolower(trim((string) ($row->area ?? '')));
                            if ($key === '') {
                                continue;
                            }
                            $completadosPorArea[$key] = $row->fecha_completado;
                        }
                    } catch (\Exception $e) {
                        // silencioso
                    }
                }
                
                // Obtener procesos de seguimiento por área
                $procesosSeguimientoOriginal = \App\Models\ProcesoPrenda::where('numero_pedido', $pedidoModel->numero_pedido)
                    ->whereNull('deleted_at')  // Excluir procesos eliminados (soft delete)
                    ->where(function ($q) use ($prenda, $numeroReciboCostura) {
                        $q->where('prenda_pedido_id', $prenda->id);
                        if ($numeroReciboCostura) {
                            $q->orWhere('numero_recibo', $numeroReciboCostura);
                        }
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Calcular fechas de inicio/fin reales según el cambio de área
                // inicio = created_at del proceso; fin = created_at del siguiente proceso
                $procesosSeguimientoCalculados = [];
                if ($procesosSeguimientoOriginal && $procesosSeguimientoOriginal->count() > 0) {
                    $total = $procesosSeguimientoOriginal->count();
                    for ($i = 0; $i < $total; $i++) {
                        $actual = $procesosSeguimientoOriginal[$i];
                        $siguiente = ($i + 1 < $total) ? $procesosSeguimientoOriginal[$i + 1] : null;

                        $clone = clone $actual;
                        $clone->fecha_inicio = $actual->created_at;
                        $clone->fecha_fin = $siguiente ? $siguiente->created_at : null;
                        $procesosSeguimientoCalculados[] = $clone;
                    }
                }
                $procesosSeguimiento = collect($procesosSeguimientoCalculados);
                
                // Agrupar consecutivos por tipo de recibo
                $seguimientosPorTipo = [];
                $tiposReciboProcesos = [];
                
                foreach ($consecutivos as $consecutivo) {
                    $seguimientosPorTipo[$consecutivo->tipo_recibo] = [
                        'consecutivo_actual' => $consecutivo->consecutivo_actual,
                        'consecutivo_inicial' => $consecutivo->consecutivo_inicial,
                        'notas' => $consecutivo->notas,
                    ];
                    
                    // Solo agregar los tipos de recibo que son procesos (excluir COSTURA y COSTURA-BODEGA)
                    $tiposProcesoValidos = ['ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO'];
                    if (in_array($consecutivo->tipo_recibo, $tiposProcesoValidos)) {
                        $tiposReciboProcesos[] = [
                            'nombre' => $consecutivo->tipo_recibo,
                            'estado' => $consecutivo->estado ?? 'PENDIENTE'
                        ];
                    }
                }
                
                // Agrupar procesos por área
                $seguimientosPorArea = [];
                foreach ($procesosSeguimiento as $proceso) {
                    $areaKey = strtolower(trim((string) $proceso->proceso));
                    $fechaCompletadoArea = $completadosPorArea[$areaKey] ?? null;
                    
                    // Calcular días hábiles transcurridos
                    $diasHabilesTranscurridos = null;
                    $proximaActualizacion = null;
                    
                    if ($proceso->fecha_inicio) {
                        // Si el proceso está completado, calcular desde inicio hasta fecha completada
                        // Si está en progreso, calcular desde inicio hasta hoy
                        $fechaFin = $fechaCompletadoArea ? $fechaCompletadoArea : now();
                        
                        try {
                            $diasHabilesTranscurridos = \App\Services\CalculadorDiasService::calcularDiasHabiles(
                                $proceso->fecha_inicio,
                                $fechaFin
                            );
                            
                            // Próxima actualización será a medianoche (hora del servidor)
                            // Dado que el cálculo depende del "hoy", refrescar a las 00:00
                            $proximaActualizacion = \Carbon\Carbon::now()
                                ->addDay()
                                ->startOfDay()
                                ->toIso8601String();
                        } catch (\Exception $e) {
                            \Log::warning('[getSeguimientoPorPrenda] Error calculando días hábiles:', [
                                'error' => $e->getMessage(),
                                'fecha_inicio' => $proceso->fecha_inicio
                            ]);
                        }
                    }
                    
                    $seguimientosPorArea[$proceso->proceso] = [
                        'id' => $proceso->id,
                        'proceso_prenda_id' => $proceso->prenda_pedido_id,
                        'area' => $proceso->proceso,
                        'estado' => $proceso->estado_proceso,
                        'fecha_inicio' => $proceso->fecha_inicio,
                        'fecha_fin' => $proceso->fecha_fin,
                        'fecha_completado' => $fechaCompletadoArea,
                        'encargado' => $proceso->encargado,
                        'fecha_de_asignacion_encargado' => $proceso->fecha_de_asignacion_encargado,
                        'observaciones' => $proceso->observaciones,
                        'codigo_referencia' => $proceso->codigo_referencia,
                        'dias_duracion' => $proceso->dias_duracion,
                        'esta_activo' => $proceso->estado_proceso === 'Pendiente',
                        'dias_habiles_transcurridos' => $diasHabilesTranscurridos,
                        'proximaActualizacion' => $proximaActualizacion,
                        // === Pre-computados para el frontend (Phase 12 - Backend First) ===
                        'metadata' => self::resolveAreaMetadata($proceso->proceso),
                        'duraciones' => self::calcularDuracionesArea(
                            $proceso->proceso,
                            $proceso->fecha_inicio,
                            $proceso->fecha_de_asignacion_encargado,
                            $fechaCompletadoArea,
                            $proceso->fecha_fin,
                            $proceso->estado_proceso
                        ),
                        'fechas_formateadas' => [
                            'fecha_llegada' => $proceso->fecha_inicio ? \Carbon\Carbon::parse($proceso->fecha_inicio)->format('d/m/Y') : null,
                            'fecha_asignacion' => $proceso->fecha_de_asignacion_encargado ? \Carbon\Carbon::parse($proceso->fecha_de_asignacion_encargado)->format('d/m/Y') : null,
                            'fecha_fin' => $fechaCompletadoArea 
                                ? \Carbon\Carbon::parse($fechaCompletadoArea)->format('d/m/Y') 
                                : ($proceso->fecha_fin ? \Carbon\Carbon::parse($proceso->fecha_fin)->format('d/m/Y') : null),
                        ],
                    ];
                }
                
                // Obtener el área, encargado y número de recibo del proceso más reciente
                $ultimoProcesoArea = '-';
                $ultimoProcesoEncargado = null;
                $ultimoProcesoId = null;
                $ultimoProcesoEstado = null;
                $ultimoProcesoFechaInicio = null;
                $ultimoProcesoFechaFin = null;
                $ultimoProcesoObservaciones = null;
                $ultimoProcesoCodigoReferencia = null;
                $ultimoProcesoDiasDuracion = null;
                $ultimoReciboNumero = '-';
                if ($procesosSeguimientoOriginal && $procesosSeguimientoOriginal->count() > 0) {
                    // El último proceso real es el más reciente por created_at
                    $ultimoProcesoReal = $procesosSeguimientoOriginal->sortByDesc('created_at')->first();
                    if ($ultimoProcesoReal) {
                        $ultimoProcesoArea = $ultimoProcesoReal->proceso;
                        $ultimoProcesoEncargado = $ultimoProcesoReal->encargado;
                        $ultimoProcesoId = $ultimoProcesoReal->id;
                        $ultimoProcesoEstado = $ultimoProcesoReal->estado_proceso;
                        $ultimoProcesoObservaciones = $ultimoProcesoReal->observaciones;
                        $ultimoProcesoCodigoReferencia = $ultimoProcesoReal->codigo_referencia;
                        $ultimoProcesoDiasDuracion = $ultimoProcesoReal->dias_duracion;

                        // Fechas calculadas: inicio = created_at del proceso; fin = created_at del siguiente proceso
                        $procesosOrdenadosAsc = $procesosSeguimientoOriginal->sortBy('created_at')->values();
                        $idxUltimo = $procesosOrdenadosAsc->search(function ($p) use ($ultimoProcesoReal) {
                            return $p->id === $ultimoProcesoReal->id;
                        });
                        $ultimoProcesoFechaInicio = $ultimoProcesoReal->created_at;
                        $ultimoProcesoFechaFin = null;
                        if (is_int($idxUltimo) && ($idxUltimo + 1) < $procesosOrdenadosAsc->count()) {
                            $ultimoProcesoFechaFin = $procesosOrdenadosAsc[$idxUltimo + 1]->created_at;
                        }
                        
                        // Obtener el número de recibo más reciente para esta prenda
                        \Log::info('[getSeguimientoPorPrenda] Buscando consecutivos para prenda', [
                            'prenda_id' => $prenda->id,
                            'pedido_id' => $pedidoId
                        ]);
                        
                        $consecutivosQuery = \App\Models\ConsecutivosRecibosPedidos::where('prenda_id', $prenda->id)
                            ->where('pedido_produccion_id', $pedidoId)
                            ->where('tipo_recibo', 'COSTURA')
                            ->where('activo', 1);
                        
                        $consecutivosCount = $consecutivosQuery->count();
                        \Log::info('[getSeguimientoPorPrenda] Consecutivos encontrados', [
                            'prenda_id' => $prenda->id,
                            'pedido_id' => $pedidoId,
                            'total_encontrados' => $consecutivosCount
                        ]);
                        
                        $ultimoRecibo = $consecutivosQuery->orderBy('created_at', 'desc')->first();
                        
                        if ($ultimoRecibo) {
                            $ultimoReciboNumero = $ultimoRecibo->consecutivo_actual;
                            \Log::info('[getSeguimientoPorPrenda] Último recibo encontrado', [
                                'prenda_id' => $prenda->id,
                                'consecutivo_actual' => $ultimoRecibo->consecutivo_actual,
                                'tipo_recibo' => $ultimoRecibo->tipo_recibo
                            ]);
                        } else {
                            \Log::warning('[getSeguimientoPorPrenda] No se encontró último recibo', [
                                'prenda_id' => $prenda->id,
                                'pedido_id' => $pedidoId
                            ]);
                        }
                    }
                }

                // Construir array de cantidades por talla
                $cantidadTalla = [];
                foreach ($prenda->tallas as $talla) {
                    $cantidadTalla[$talla->talla] = $talla->cantidad;
                }

                // Construir array de procesos para el frontend
                $procesosArray = [];
                foreach ($prenda->procesos as $proceso) {
                    $procesosArray[] = [
                        'id' => $proceso->id,
                        'tipo_proceso_id' => $proceso->tipo_proceso_id,
                        'tipo_proceso' => $proceso->tipoProceso ? [
                            'id' => $proceso->tipoProceso->id,
                            'nombre' => $proceso->tipoProceso->nombre,
                            'slug' => $proceso->tipoProceso->slug,
                            'color' => $proceso->tipoProceso->color,
                            'icono' => $proceso->tipoProceso->icono,
                        ] : null,
                        'estado' => $proceso->estado,
                        'observaciones' => $proceso->observaciones,
                        'ubicaciones' => $proceso->ubicaciones,
                    ];
                }

                // Obtener ancho y metraje para esta prenda específica
                $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoModel->id)
                    ->where('prenda_pedido_id', $prenda->id)
                    ->first();
                
                $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedidoModel->id)
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                
                $anchoMetrajeData = null;
                if ($anchoGeneral || $metrajesPorColor->isNotEmpty()) {
                    $anchoMetrajeData = [
                        'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                        'metrajes_por_color' => []
                    ];
                    
                    foreach ($metrajesPorColor as $metraje) {
                        $anchoMetrajeData['metrajes_por_color'][] = [
                            'color' => $metraje->color,
                            'metraje' => $metraje->metraje
                        ];
                    }
                }

                $prendasConSeguimiento[] = [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'cantidad' => $prenda->cantidad_total,
                    'cantidad_talla' => $cantidadTalla,
                    'de_bodega' => $prenda->de_bodega,
                    'seguimientos' => $seguimientosPorTipo,
                    'seguimientos_por_area' => self::inyectarAreaInsumos(
                        $seguimientosPorArea,
                        $consecutivos,
                        $pedidoModel
                    ),
                    'datos_activacion_recibo' => self::calcularDatosActivacionRecibo(
                        $consecutivos,
                        $pedidoModel
                    ),
                    'procesos' => $procesosArray,
                    'tipos_recibo_procesos' => $tiposReciboProcesos,
                    'consecutivos' => $consecutivos->toArray(),
                    'total_procesos' => $prenda->procesos->count(),
                    'total_variantes' => $prenda->variantes->count(),
                    'ultimo_proceso_area' => $ultimoProcesoArea,
                    'ultimo_proceso_encargado' => $ultimoProcesoEncargado,
                    'ultimo_proceso_id' => $ultimoProcesoId,
                    'ultimo_proceso_estado' => $ultimoProcesoEstado,
                    'ultimo_proceso_fecha_inicio' => $ultimoProcesoFechaInicio,
                    'ultimo_proceso_fecha_fin' => $ultimoProcesoFechaFin,
                    'ultimo_proceso_observaciones' => $ultimoProcesoObservaciones,
                    'ultimo_proceso_codigo_referencia' => $ultimoProcesoCodigoReferencia,
                    'ultimo_proceso_dias_duracion' => $ultimoProcesoDiasDuracion,
                    'ultimo_recibo_numero' => $ultimoReciboNumero, // Número de recibo más reciente
                    'ancho_metraje' => $anchoMetrajeData,
                    // === Pre-computados Phase 12b: lógica de negocio en backend ===
                    'area_actual' => self::resolveAreaActualPrenda($ultimoProcesoArea, $prenda, $pedidoModel),
                    'recibo_display' => self::resolveReciboDisplay($consecutivos, $ultimoReciboNumero),
                ];
            }
            
            \Log::info('[getSeguimientoPorPrenda] Datos obtenidos', [
                'numero_pedido' => $pedido,
                'total_prendas' => count($prendasConSeguimiento)
            ]);
            
            return response()->json([
                'pedido' => [
                    'id' => $pedidoModel->id,
                    'numero_pedido' => $pedidoModel->numero_pedido,
                    'cliente' => $pedidoModel->cliente,
                    'fecha_de_creacion_de_orden' => $pedidoModel->fecha_de_creacion_de_orden ?? $pedidoModel->created_at,
                    // === Pre-computado Phase 12b: recibo principal del pedido ===
                    'recibo_principal' => self::resolveReciboPrincipal($prendasConSeguimiento),
                ],
                'prendas' => $prendasConSeguimiento,
                // === Configuración centralizada de áreas (Phase 12b) ===
                'areas_config' => [
                    'areas_que_requieren_encargado' => ['corte', 'costura', 'control de calidad'],
                    'areas_con_selector_dinamico' => ['corte', 'costura'],
                ],
            ]);
            
        } catch (\Exception $e) {
            \Log::error('[getSeguimientoPorPrenda] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener seguimiento por prenda',
                'pedido' => $pedido,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ================================================================
    // Métodos privados: Pre-computar datos para el frontend (Phase 12)
    // Mueve lógica de negocio que estaba en JS al backend.
    // ================================================================

    /**
     * Resolver metadata de un área (qué campos mostrar/ocultar)
     */
    private static function resolveAreaMetadata(string $area): array
    {
        $areaLower = strtolower(trim($area));
        $isInsumos = $areaLower === 'insumos';
        $isCorte = str_contains($areaLower, 'corte');
        $isCostura = str_contains($areaLower, 'costura');
        $isControlCalidad = str_contains($areaLower, 'control') && str_contains($areaLower, 'calidad');
        $needsEncargado = $isCorte || $isCostura || $isControlCalidad;

        return [
            'isInsumos' => $isInsumos,
            'isCorte' => $isCorte,
            'isCostura' => $isCostura,
            'isControlCalidad' => $isControlCalidad,
            'needsEncargado' => $needsEncargado,
            'shouldHideEncargado' => $isInsumos || !$needsEncargado,
        ];
    }

    /**
     * Calcular todas las duraciones de un área pre-computadas
     */
    private static function calcularDuracionesArea(
        string $area,
        $fechaInicio,
        $fechaAsignacion,
        $fechaCompletado,
        $fechaFin,
        string $estado
    ): array {
        $metadata = self::resolveAreaMetadata($area);
        $calculador = \App\Services\CalculadorDiasService::class;

        // Fecha fin real según tipo de área
        if ($metadata['isInsumos']) {
            $fechaFinReal = $fechaFin;
        } elseif ($metadata['needsEncargado']) {
            $fechaFinReal = $fechaCompletado;
        } else {
            $fechaFinReal = $fechaFin;
        }

        // 1. Duración de asignación (solo para áreas con encargado)
        $duracionAsignacion = null;
        $duracionAsignacionDias = null;
        if ($metadata['needsEncargado'] && $fechaInicio && $fechaAsignacion) {
            try {
                $ini = \Carbon\Carbon::parse($fechaInicio);
                $asg = \Carbon\Carbon::parse($fechaAsignacion);
                $diffMs = max(0, $asg->diffInMilliseconds($ini));
                $duracionAsignacion = self::formatDurationHuman($diffMs);
                $duracionAsignacionDias = $calculador::calcularDiasHabiles($fechaInicio, $fechaAsignacion);
            } catch (\Exception $e) {
                // silencioso
            }
        }

        // 2. Duración en área (días hábiles)
        $duracionEnArea = null;
        if ($metadata['needsEncargado']) {
            $inicioCalculo = $fechaAsignacion ?: $fechaInicio;
            $finCalculo = $fechaFinReal ?: now();
            if ($inicioCalculo) {
                $duracionEnArea = $calculador::calcularDiasHabiles($inicioCalculo, $finCalculo);
            }
        } else {
            if ($fechaInicio) {
                $finCalculo = $fechaFinReal ?: now();
                $duracionEnArea = $calculador::calcularDiasHabiles($fechaInicio, $finCalculo);
            }
        }

        // 3. Total días
        $totalDias = null;
        if ($metadata['needsEncargado'] && !$fechaFinReal) {
            // Sumar ambas duraciones
            $totalDias = ($duracionAsignacionDias ?? 0) + ($duracionEnArea ?? 0);
        } elseif ($fechaInicio) {
            $inicioCalculo = ($metadata['needsEncargado'] && $fechaAsignacion) ? $fechaAsignacion : $fechaInicio;
            $finCalculo = $fechaFinReal ?: now();
            $totalDias = $calculador::calcularDiasHabiles($inicioCalculo, $finCalculo);
        }

        // 4. Estado display
        $hasFechaCompletado = !$metadata['isInsumos'] && !empty($fechaCompletado);
        $estadoDisplay = $metadata['isInsumos']
            ? ($estado ?: 'Pendiente')
            : ($hasFechaCompletado ? 'Completado' : 'Pendiente');
        $estaActivoDisplay = $metadata['isInsumos']
            ? ($estado === 'Pendiente' || $estado === 'Llegó a insumos')
            : !$hasFechaCompletado;

        return [
            'duracion_asignacion' => $duracionAsignacion,
            'duracion_asignacion_dias' => $duracionAsignacionDias,
            'duracion_en_area' => $duracionEnArea !== null ? $calculador::formatearDias($duracionEnArea) : null,
            'duracion_en_area_dias' => $duracionEnArea,
            'total_dias' => $totalDias !== null ? $calculador::formatearDias($totalDias) : null,
            'total_dias_numero' => $totalDias,
            'estado_display' => $estadoDisplay,
            'esta_activo_display' => $estaActivoDisplay,
        ];
    }

    /**
     * Formatear duración en milisegundos a texto legible
     */
    private static function formatDurationHuman(int $diffMs): string
    {
        $minutes = intdiv($diffMs, 60000);
        $hours = intdiv($diffMs, 3600000);
        $days = intdiv($diffMs, 86400000);

        if ($days >= 1) return "{$days} " . ($days === 1 ? 'Día' : 'Días');
        if ($hours >= 1) return "{$hours}h";
        if ($minutes >= 1) return "{$minutes}min";
        return '< 1min';
    }

    /**
     * Inyectar área virtual "Insumos" si no existe y hay recibo activado
     */
    private static function inyectarAreaInsumos(array $seguimientosPorArea, $consecutivos, $pedidoModel): array
    {
        // Verificar si ya existe Insumos
        $hasInsumos = false;
        foreach (array_keys($seguimientosPorArea) as $k) {
            if (strtolower(trim($k)) === 'insumos') {
                $hasInsumos = true;
                break;
            }
        }

        // Buscar recibo COSTURA activo
        $reciboCostura = null;
        foreach ($consecutivos as $c) {
            $tipo = strtoupper(trim($c->tipo_recibo ?? ''));
            $activo = ($c->activo ?? 0) == 1;
            if ($tipo === 'COSTURA' && $activo) {
                $reciboCostura = $c;
                break;
            }
        }

        $reciboCreatedAt = $reciboCostura->created_at ?? null;

        if ($hasInsumos || !$reciboCreatedAt) {
            // Ordenar: Insumos primero si existe
            return self::ordenarAreas($seguimientosPorArea);
        }

        // Encontrar fecha de envío a producción (primer proceso / corte)
        $fechaEnvioProduccion = null;
        $areaCorteKey = null;
        foreach (array_keys($seguimientosPorArea) as $k) {
            if (str_contains(strtolower($k), 'corte')) {
                $areaCorteKey = $k;
                break;
            }
        }

        if ($areaCorteKey) {
            $fechaEnvioProduccion = $seguimientosPorArea[$areaCorteKey]['fecha_inicio'] ?? null;
        } else {
            // Fallback: fecha_inicio más temprana
            $bestDate = null;
            foreach ($seguimientosPorArea as $data) {
                $fi = $data['fecha_inicio'] ?? null;
                if (!$fi) continue;
                try {
                    $d = \Carbon\Carbon::parse($fi);
                    if (!$bestDate || $d->lt($bestDate)) {
                        $bestDate = $d;
                        $fechaEnvioProduccion = $fi;
                    }
                } catch (\Exception $e) {
                    // silencioso
                }
            }
        }

        $yaEnviado = !empty($fechaEnvioProduccion);

        // Calcular duración Insumos
        $duracionInsumos = null;
        $duracionInsumosTexto = null;
        if ($fechaEnvioProduccion) {
            try {
                $duracionInsumos = \App\Services\CalculadorDiasService::calcularDiasHabiles($reciboCreatedAt, $fechaEnvioProduccion);
                $duracionInsumosTexto = \App\Services\CalculadorDiasService::formatearDias($duracionInsumos);
            } catch (\Exception $e) {
                // silencioso
            }
        }

        // Crear área virtual Insumos
        $insumosArea = [
            'id' => null,
            'area' => 'Insumos',
            'estado' => $yaEnviado ? 'Enviado a producción' : 'Llegó a insumos',
            'encargado' => '-',
            'fecha_inicio' => $reciboCreatedAt,
            'fecha_fin' => $fechaEnvioProduccion,
            'fecha_completado' => null,
            'fecha_de_asignacion_encargado' => null,
            'dias_duracion' => null,
            'esta_activo' => !$yaEnviado,
            'can_edit' => false,
            'hide_encargado' => true,
            'es_virtual' => true,
            'metadata' => self::resolveAreaMetadata('Insumos'),
            'duraciones' => [
                'duracion_asignacion' => null,
                'duracion_asignacion_dias' => null,
                'duracion_en_area' => $duracionInsumosTexto,
                'duracion_en_area_dias' => $duracionInsumos,
                'total_dias' => $duracionInsumosTexto,
                'total_dias_numero' => $duracionInsumos,
                'estado_display' => $yaEnviado ? 'Enviado a producción' : 'Llegó a insumos',
                'esta_activo_display' => !$yaEnviado,
            ],
            'fechas_formateadas' => [
                'fecha_llegada' => $reciboCreatedAt ? \Carbon\Carbon::parse($reciboCreatedAt)->format('d/m/Y') : null,
                'fecha_asignacion' => null,
                'fecha_fin' => $fechaEnvioProduccion ? \Carbon\Carbon::parse($fechaEnvioProduccion)->format('d/m/Y') : null,
            ],
        ];

        // Insertar Insumos al inicio
        $result = ['Insumos' => $insumosArea];
        foreach ($seguimientosPorArea as $k => $v) {
            $result[$k] = $v;
        }

        return $result;
    }

    /**
     * Ordenar áreas: Insumos siempre primero
     */
    private static function ordenarAreas(array $seguimientosPorArea): array
    {
        $result = [];
        foreach ($seguimientosPorArea as $k => $v) {
            if (strtolower(trim($k)) === 'insumos') {
                $result = [$k => $v] + $result;
            } else {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * Calcular datos de activación del recibo para la sección header del timeline
     */
    private static function calcularDatosActivacionRecibo($consecutivos, $pedidoModel): array
    {
        // Buscar recibo COSTURA activo
        $reciboCostura = null;
        foreach ($consecutivos as $c) {
            $tipo = strtoupper(trim($c->tipo_recibo ?? ''));
            $activo = ($c->activo ?? 0) == 1;
            if ($tipo === 'COSTURA' && $activo) {
                $reciboCostura = $c;
                break;
            }
        }

        if (!$reciboCostura) {
            // Fallback: cualquier recibo activo
            foreach ($consecutivos as $c) {
                if (($c->activo ?? 0) == 1) {
                    $reciboCostura = $c;
                    break;
                }
            }
        }

        $reciboCreatedAt = $reciboCostura->created_at ?? null;
        $fechaCreacionOrden = $pedidoModel->fecha_de_creacion_de_orden ?? $pedidoModel->created_at ?? null;

        // Calcular tiempo transcurrido
        $tiempoTranscurrido = null;
        $diasHabilesActivacion = null;
        if ($fechaCreacionOrden && $reciboCreatedAt) {
            try {
                $ini = \Carbon\Carbon::parse($fechaCreacionOrden);
                $fin = \Carbon\Carbon::parse($reciboCreatedAt);
                $diffMs = max(0, $fin->diffInMilliseconds($ini));
                $tiempoTranscurrido = self::formatDurationHuman($diffMs);
                $diasHabilesActivacion = \App\Services\CalculadorDiasService::calcularDiasHabiles($fechaCreacionOrden, $reciboCreatedAt);
            } catch (\Exception $e) {
                // silencioso
            }
        }

        return [
            'fecha_creacion_orden' => $fechaCreacionOrden,
            'fecha_creacion_orden_formateada' => $fechaCreacionOrden 
                ? \Carbon\Carbon::parse($fechaCreacionOrden)->format('d/m/Y H:i') 
                : null,
            'fecha_activacion_recibo' => $reciboCreatedAt,
            'fecha_activacion_recibo_formateada' => $reciboCreatedAt 
                ? \Carbon\Carbon::parse($reciboCreatedAt)->format('d/m/Y H:i') 
                : null,
            'tiempo_transcurrido' => $tiempoTranscurrido,
            'dias_habiles_activacion' => $diasHabilesActivacion,
            'tiempo_transcurrido_completo' => $tiempoTranscurrido && $diasHabilesActivacion !== null
                ? "{$tiempoTranscurrido} ({$diasHabilesActivacion} días hábiles)"
                : $tiempoTranscurrido,
        ];
    }

    /**
     * Resolver el área actual de una prenda (regla de negocio centralizada)
     * Prioridad: ultimo_proceso_area > prenda.area > pedido.area > '-'
     */
    private static function resolveAreaActualPrenda(string $ultimoProcesoArea, $prenda, $pedidoModel): string
    {
        if ($ultimoProcesoArea && $ultimoProcesoArea !== '-') {
            return $ultimoProcesoArea;
        }
        // No hay acceso a "prenda.area" como campo directo en el modelo,
        // pero se puede derivar del último proceso o del pedido
        $areaPedido = $pedidoModel->area ?? null;
        if ($areaPedido && trim($areaPedido) !== '') {
            return trim($areaPedido);
        }
        return '-';
    }

    /**
     * Resolver qué recibo mostrar para una prenda (regla de negocio centralizada)
     * Prioridad: COSTURA activo > cualquier activo > ultimo_recibo_numero
     */
    private static function resolveReciboDisplay($consecutivos, string $ultimoReciboNumero): string
    {
        $prioridades = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];
        
        // 1. Buscar recibo activo según prioridad
        foreach ($prioridades as $tipo) {
            foreach ($consecutivos as $c) {
                $tipoRecibo = strtoupper(trim($c->tipo_recibo ?? ''));
                $activo = ($c->activo ?? 0) == 1;
                if ($tipoRecibo === $tipo && $activo && !empty($c->consecutivo_actual)) {
                    return "{$tipoRecibo} #{$c->consecutivo_actual}";
                }
            }
        }

        // 2. Cualquier recibo activo
        foreach ($consecutivos as $c) {
            $activo = ($c->activo ?? 0) == 1;
            if ($activo && !empty($c->consecutivo_actual)) {
                $tipo = strtoupper(trim($c->tipo_recibo ?? 'RECIBO'));
                return "{$tipo} #{$c->consecutivo_actual}";
            }
        }

        // 3. Fallback
        if ($ultimoReciboNumero && $ultimoReciboNumero !== '-') {
            return "COSTURA #{$ultimoReciboNumero}";
        }

        return '-';
    }

    /**
     * Resolver el recibo principal del pedido completo
     * Busca en todas las prendas y devuelve el recibo de mayor prioridad
     */
    private static function resolveReciboPrincipal(array $prendasConSeguimiento): string
    {
        $prioridades = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];
        $mejorRecibo = null;
        $mejorPrioridad = PHP_INT_MAX;

        foreach ($prendasConSeguimiento as $prenda) {
            $consecutivos = $prenda['consecutivos'] ?? [];
            foreach ($consecutivos as $c) {
                $tipo = strtoupper(trim($c['tipo_recibo'] ?? ''));
                $activo = ($c['activo'] ?? 0) == 1;
                if (!$activo || empty($c['consecutivo_actual'])) continue;

                $idx = array_search($tipo, $prioridades);
                $prioridadActual = $idx !== false ? $idx : count($prioridades);

                if ($prioridadActual < $mejorPrioridad) {
                    $mejorPrioridad = $prioridadActual;
                    $mejorRecibo = "{$tipo} #{$c['consecutivo_actual']}";
                }
            }
        }

        return $mejorRecibo ?? '-';
    }

    /**
     * Obtener novedades de un pedido específico
     */
    public function getNovedades($id)
    {
        try {
            // Buscar por numero_pedido (no por id)
            $pedido = PedidoProduccion::where('numero_pedido', $id)->firstOrFail();
            
            \Log::info('[getNovedades] Pedido encontrado', [
                'numero_pedido' => $id,
                'pedido_id' => $pedido->id,
                'novedades_length' => strlen($pedido->novedades ?? '')
            ]);
            
            return response()->json([
                'novedades' => $pedido->novedades ?? ''
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('[getNovedades] Pedido no encontrado', [
                'numero_pedido' => $id
            ]);
            
            return response()->json([
                'error' => 'Pedido no encontrado',
                'message' => 'No se encontró un pedido con el número: ' . $id
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[getNovedades] Error: ' . $e->getMessage(), [
                'numero_pedido' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener novedades',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
