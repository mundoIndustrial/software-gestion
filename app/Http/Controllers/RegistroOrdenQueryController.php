<?php

namespace App\Http\Controllers;

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
        // Primero, intentar buscar en LogoPedido
        $logoPedido = \App\Models\LogoPedido::where('numero_pedido', $pedido)->first();
        
        if ($logoPedido) {
            // Es un LogoPedido, devolverlo con toda su información
            \Log::info('📦 [RegistroOrdenQueryController::show] Encontrado LogoPedido', [
                'numero_pedido' => $pedido,
                'pedido_id' => $logoPedido->pedido_id,
                'logo_cotizacion_id' => $logoPedido->logo_cotizacion_id,
            ]);
            
            $logoPedidoArray = $logoPedido->toArray();
            
            // PASO 1: Intentar completar desde PedidoProduccion
            if ($logoPedido->pedido_id) {
                try {
                    $pedidoProd = \App\Models\PedidoProduccion::with('asesora')->find($logoPedido->pedido_id);
                    
                    if ($pedidoProd) {
                        \Log::info('📦 Encontrado PedidoProduccion, completando datos', [
                            'pedido_id' => $logoPedido->pedido_id,
                            'cliente' => $pedidoProd->cliente,
                            'asesora' => $pedidoProd->asesora?->name,
                            'fecha' => $pedidoProd->fecha_de_creacion_de_orden
                        ]);
                        
                        // Completar desde el pedido de producción - SIEMPRE si viene vacío
                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $pedidoProd->cliente ?? '-';
                            \Log::info('✅ [PASO 1] Cliente completado desde PedidoProduccion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $asesoraName = $pedidoProd->asesora?->name ?? $pedidoProd->asesor?->name ?? '-';
                            $logoPedidoArray['asesora'] = $asesoraName;
                            \Log::info('✅ [PASO 1] Asesora completada desde PedidoProduccion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                            $logoPedidoArray['fecha_de_creacion_de_orden'] = $pedidoProd->fecha_de_creacion_de_orden;
                            \Log::info('✅ [PASO 1] Fecha completada desde PedidoProduccion', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $pedidoProd->descripcion_prendas) {
                            $logoPedidoArray['descripcion'] = $pedidoProd->descripcion_prendas;
                            \Log::info('✅ [PASO 1] Descripción completada desde PedidoProduccion');
                        }
                    } else {
                        \Log::warning('⚠️ [PASO 1] PedidoProduccion no encontrado', ['pedido_id' => $logoPedido->pedido_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('❌ [PASO 1] Error al buscar PedidoProduccion', ['error' => $e->getMessage()]);
                }
            }
            
            // PASO 2: Si aún falta info, intentar desde LogoCotizacion
            if ($logoPedido->logo_cotizacion_id && (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-')) {
                try {
                    $logoCot = \App\Models\LogoCotizacion::with('cotizacion')->find($logoPedido->logo_cotizacion_id);
                    
                    if ($logoCot && $logoCot->cotizacion) {
                        \Log::info('📦 Encontrado LogoCotizacion, completando datos', [
                            'cliente' => $logoCot->cotizacion->cliente,
                            'fecha' => $logoCot->cotizacion->fecha_de_creacion
                        ]);
                        
                        if (empty($logoPedidoArray['cliente']) || $logoPedidoArray['cliente'] === '-') {
                            $logoPedidoArray['cliente'] = $logoCot->cotizacion->cliente ?? '-';
                            \Log::info('✅ [PASO 2] Cliente completado desde LogoCotizacion', ['cliente' => $logoPedidoArray['cliente']]);
                        }
                        if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                            $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoCot->cotizacion->fecha_de_creacion;
                            \Log::info('✅ [PASO 2] Fecha completada desde LogoCotizacion', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
                        }
                        if (empty($logoPedidoArray['asesora']) || $logoPedidoArray['asesora'] === '-') {
                            $logoPedidoArray['asesora'] = $logoCot->cotizacion->asesor?->name ?? '-';
                            \Log::info('✅ [PASO 2] Asesora completada desde LogoCotizacion', ['asesora' => $logoPedidoArray['asesora']]);
                        }
                        if (empty($logoPedidoArray['descripcion']) && $logoCot->descripcion) {
                            $logoPedidoArray['descripcion'] = $logoCot->descripcion;
                            \Log::info('✅ [PASO 2] Descripción completada desde LogoCotizacion');
                        }
                    } else {
                        \Log::warning('⚠️ [PASO 2] LogoCotizacion no encontrado o sin cotización', ['logo_cotizacion_id' => $logoPedido->logo_cotizacion_id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('❌ [PASO 2] Error al buscar LogoCotizacion', ['error' => $e->getMessage()]);
                }
            }
            
            // PASO 3: Asegurar valores finales
            $logoPedidoArray['numero_pedido'] = $logoPedido->numero_pedido ?? $pedido;
            $logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
            $logoPedidoArray['asesora'] = $logoPedidoArray['asesora'] ?: '-';
            $logoPedidoArray['descripcion'] = $logoPedido->descripcion ?? '';
            
            // ✅ IMPORTANTE: Si no hay fecha_de_creacion_de_orden, usar created_at
            if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoPedido->created_at ?? now();
                \Log::info('✅ [PASO 3] Fecha asignada desde created_at', ['fecha' => $logoPedidoArray['fecha_de_creacion_de_orden']]);
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
            
            \Log::info('✅ [RegistroOrdenQueryController::show] LogoPedido finalizado COMPLETAMENTE', [
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

        // ✅ CARGAR prendas CON relaciones ANTES de toArray()
        // Hacemos un query directo para asegurar que las relaciones se cargan
        $prendasConRelaciones = \App\Models\PrendaPedido::where('numero_pedido', $pedido)
            ->with(['color', 'tela', 'tipoManga', 'tipoBroche', 'fotos', 'fotosLogo', 'fotosTela'])
            ->orderBy('id', 'asc')
            ->get();
        
        \Log::info('✅ [show] Prendas cargadas con relaciones', [
            'pedido' => $pedido,
            'total' => $prendasConRelaciones->count(),
            'primera_prenda' => $prendasConRelaciones->first() ? [
                'nombre' => $prendasConRelaciones->first()->nombre_prenda,
                'color_loaded' => $prendasConRelaciones->first()->relationLoaded('color'),
                'color_nombre' => $prendasConRelaciones->first()->color ? $prendasConRelaciones->first()->color->nombre : 'NULL',
            ] : 'N/A',
        ]);
        
        // Reemplazar prendas en la orden con las que tienen relaciones
        $order->setRelation('prendas', $prendasConRelaciones);

        // ✅ CONSTRUIR DESCRIPCIÓN MIENTRAS AÚN TENEMOS ACCESO A RELACIONES ELOQUENT
        $descripcionPrendas = $this->buildDescripcionConTallas($order);
        
        \Log::info('✅ [show] Descripción construida', [
            'longitud' => strlen($descripcionPrendas),
            'primeras_lineas' => substr($descripcionPrendas, 0, 150),
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
        \Log::info('🔍 [getOrderDetails] Obteniendo prendas para pedido', [
            'pedido' => $pedido,
            'es_cotizacion' => $esCotizacion,
        ]);
        
        try {
            // Usar prendas YA cargadas con relaciones
            {
                $prendas = $order->prendas;

                // Formatear prendas con todos los datos necesarios
                $prendasFormato = [];
                foreach ($prendas as $index => $prenda) {
                    // Obtener datos de relaciones de forma segura
                    $colorNombre = null;
                    $telaNombre = null;
                    $telaReferencia = null;
                    $tipoMangaNombre = null;
                    $tipoBrocheNombre = null;
                    
                    // ✅ Usar las relaciones ya cargadas
                    if ($prenda->color) {
                        $colorNombre = $prenda->color->nombre;
                    }
                    
                    if ($prenda->tela) {
                        $telaNombre = $prenda->tela->nombre;
                        $telaReferencia = $prenda->tela->referencia;
                    }
                    
                    if ($prenda->tipoManga) {
                        $tipoMangaNombre = $prenda->tipoManga->nombre;
                    }
                    
                    if ($prenda->tipoBroche) {
                        $tipoBrocheNombre = $prenda->tipoBroche->nombre;
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
     * Parámetro opcional: tipo=logo para obtener solo imágenes de logo
     */
    public function getOrderImages($pedido)
    {
        try {
            $tipo = request()->query('tipo'); // 'logo' o null
            $images = [];

            \Log::info('🖼️ [getOrderImages] Iniciando búsqueda de imágenes', [
                'pedido' => $pedido,
                'tipo' => $tipo
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

                    // ✅ SOLO incluir fotos de logo si tipo=logo (ya manejado arriba)
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
        
        // VERIFICAR SI ES COTIZACIÓN TIPO REFLECTIVO
        $esReflectivo = false;
        if ($order->cotizacion && $order->cotizacion->tipoCotizacion) {
            $esReflectivo = ($order->cotizacion->tipoCotizacion->codigo === 'RF');
        }
        
        // ✅ FALLBACK: Si descripción_prendas está vacía, generar dinámicamente desde las prendas
        if (empty($descripcionBase) && $order->prendas && $order->prendas->count() > 0) {
            \Log::info('🔄 [buildDescripcionConTallas] Generando descripción dinámicamente', [
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
            
            \Log::info('✅ [buildDescripcionConTallas] Descripción generada', [
                'longitud' => strlen($descripcionBase),
                'primeras_lineas' => substr($descripcionBase, 0, 200),
            ]);
        }
        
        if (!empty($descripcionBase) || ($esReflectivo && $order->prendas && $order->prendas->count() > 0)) {
            if ($esReflectivo) {
                // CASO REFLECTIVO: Usar descripción tal cual (ya contiene tallas y cantidad total)
                $descripcionConTallas = '';
                
                \Log::info('🔍 [REFLECTIVO] Construyendo descripción reflectivo', [
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
                        ]);
                        
                        if ($index > 0) {
                            $descripcionConTallas .= "\n\n";
                        }
                        
                        // Agregar descripción de la prenda (ya tiene tallas incluidas)
                        if (!empty($prenda->descripcion)) {
                            $descripcionConTallas .= $prenda->descripcion;
                        }
                        
                        // ✅ AGREGAR TALLAS SI NO ESTÁN EN LA DESCRIPCIÓN
                        if ($prenda->cantidad_talla) {
                            try {
                                $tallas = is_string($prenda->cantidad_talla) 
                                    ? json_decode($prenda->cantidad_talla, true) 
                                    : $prenda->cantidad_talla;
                                
                                \Log::info('    📊 Tallas decodificadas', [
                                    'is_array' => is_array($tallas),
                                    'count' => is_array($tallas) ? count($tallas) : 0,
                                    'tallas' => $tallas,
                                ]);
                                
                                if (is_array($tallas) && !empty($tallas)) {
                                    $tallasTexto = [];
                                    foreach ($tallas as $talla => $cantidad) {
                                        if ($cantidad > 0) {
                                            $tallasTexto[] = "$talla: $cantidad";
                                        }
                                    }
                                    if (!empty($tallasTexto)) {
                                        $descripcionConTallas .= "\nTalla: " . implode(', ', $tallasTexto);
                                        \Log::info('    ✅ Tallas agregadas: ' . implode(', ', $tallasTexto));
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('    ❌ Error decodificando tallas: ' . $e->getMessage());
                            }
                        } else {
                            \Log::info('    ⚠️ cantidad_talla está vacío');
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
            \Log::info('🎨 [getLogoImages] Iniciando búsqueda de imágenes de logo', [
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

            \Log::info('🎨 [getLogoImages] Logo pedido encontrado', [
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
                    
                    \Log::info('🎨 [getLogoImages] Prendas encontradas', [
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

            \Log::info('🎨 [getLogoImages] Resultado final', [
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
            \Log::error('❌ [getLogoImages] Error al obtener imágenes de logo: ' . $e->getMessage(), [
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
            // 🔍 Buscar LogoPedido por ID
            $logoPedido = LogoPedido::find($id);
            
            if (!$logoPedido) {
                return response()->json([
                    'error' => 'LogoPedido no encontrado',
                    'id' => $id
                ], 404);
            }

            $logoPedidoArray = $logoPedido->toArray();
            
            \Log::info('🔍 [API] showLogoPedidoById buscando ID: ' . $id, [
                'cliente' => $logoPedidoArray['cliente'] ?? null,
                'asesora' => $logoPedidoArray['asesora'] ?? null,
                'descripcion' => $logoPedidoArray['descripcion'] ?? null,
                'fecha_de_creacion_de_orden' => $logoPedidoArray['fecha_de_creacion_de_orden'] ?? null
            ]);

            // 📋 PASO 1: Completar desde PedidoProduccion si LogoPedido está incompleto
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
                        
                        \Log::info('✅ [PASO 1 API] Completados datos desde PedidoProduccion #' . $logoPedido->pedido_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning('⚠️ [PASO 1 API] Error al obtener PedidoProduccion: ' . $e->getMessage());
                }
            }

            // 📋 PASO 2: Completar desde LogoCotizacion si aún hay campos vacíos
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
                        
                        \Log::info('✅ [PASO 2 API] Completados datos desde LogoCotizacion #' . $logoPedido->logo_cotizacion_id);
                    }
                } catch (\Exception $e) {
                    \Log::warning('⚠️ [PASO 2 API] Error al obtener LogoCotizacion: ' . $e->getMessage());
                }
            }

            // 📋 PASO 3: Garantizar fecha_de_creacion_de_orden usando created_at
            if (empty($logoPedidoArray['fecha_de_creacion_de_orden'])) {
                $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoPedido->created_at;
                \Log::info('✅ [PASO 3 API] Usando created_at como fecha de creación');
            }

            // ✅ Responder con datos completos
            \Log::info('✅ [API] LogoPedido ID ' . $id . ' respondido correctamente', [
                'cliente' => $logoPedidoArray['cliente'],
                'asesora' => $logoPedidoArray['asesora'],
                'descripcion' => $logoPedidoArray['descripcion'],
                'fecha_de_creacion_de_orden' => $logoPedidoArray['fecha_de_creacion_de_orden'],
                'forma_de_pago' => $logoPedidoArray['forma_de_pago'],
                'encargado_orden' => $logoPedidoArray['encargado_orden']
            ]);
            
            return response()->json($logoPedidoArray);
            
        } catch (\Exception $e) {
            \Log::error('❌ [API] Error en showLogoPedidoById: ' . $e->getMessage(), [
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
}


