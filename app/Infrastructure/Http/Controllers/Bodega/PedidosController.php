<?php

namespace App\Infrastructure\Http\Controllers\Bodega;

use App\Http\Controllers\Controller;
use App\Models\ReciboPrenda;
use App\Models\PedidoProduccion;
use App\Models\BodegaDetalleTalla;
use App\Models\PedidoRevisado;
use App\Models\PedidoOculto;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaRoleService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Application\Bodega\Services\BodegaAuditoriaService;
use App\Application\Bodega\Services\BodegaFiltroService;
use App\Application\Bodega\Services\BodegaDatosService;
use App\Application\Bodega\Services\BodegaNotificacionService;
use App\Application\Bodega\Services\BodegaUpdateService;
use App\Application\Bodega\Services\BodegaGuardadoService;
use App\Application\Bodega\Services\EppHomologacionService;
use App\Application\Bodega\Services\PedidoListadoService;
use App\Application\Bodega\Services\PedidoFiltroService;
use App\Application\Bodega\Services\PedidoEstadoService;
use App\Application\Bodega\Services\PedidoActualizacionService;
use App\Application\Bodega\Services\PedidoNotasService;
use App\Application\Bodega\Services\PedidoConsultasService;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use Illuminate\Support\Facades\Auth;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Application\Bodega\CQRS\CQRSManager;
use App\Application\Bodega\CQRS\Commands\EntregarPedidoCommand;
use App\Application\Bodega\CQRS\Commands\ActualizarEstadoPedidoCommand;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Application\Bodega\CQRS\Queries\ObtenerEstadisticasPedidosQuery;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\PatternFill;
use App\Infrastructure\Http\Traits\ValidateReadOnlyAccess;

class PedidosController extends Controller
{
    use ValidateReadOnlyAccess;

    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private PedidoProduccionReadRepository $pedidoRepository,
        private BodegaPedidoService $bodegaPedidoService,
        private BodegaRoleService $roleService,
        private BodegaAuditoriaService $auditoriaService,
        private CQRSManager $cqrsManager,
        private BodegaDatosService $datosService,
        private BodegaNotificacionService $notificacionService,
        private BodegaUpdateService $updateService,
        private BodegaGuardadoService $guardadoService,
        private EppHomologacionService $eppHomologacionService,
        private PedidoListadoService $pedidoListadoService,
        private PedidoFiltroService $pedidoFiltroService,
        private PedidoEstadoService $pedidoEstadoService,
        private PedidoActualizacionService $pedidoActualizacionService,
        private PedidoNotasService $pedidoNotasService,
        private PedidoConsultasService $pedidoConsultasService,
    ) {}

    /**
     * Mostrar lista de pedidos para bodeguero
     */
    public function index(Request $request)
    {
        try {
            $datos = $this->bodegaPedidoService->obtenerPedidosPaginados($request);

            if ($datos['view_type'] === 'details') {
                $esReadOnly = $this->isReadOnly();

                $viewName = $esReadOnly ? 'bodega.pedidos-readonly' : 'bodega.pedidos';

                $response = view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            } else {
                // Vista de lista - El filtro de pedidos ocultos ya se aplica en el servicio
                $response = view('bodega.index-list', [
                    'pedidosPorPagina' => $datos['pedidos_por_pagina'] ?? [],
                    'totalPedidos' => $datos['total_pedidos'] ?? 0,
                    'paginaActual' => $datos['pagina_actual'] ?? 1,
                    'porPagina' => $datos['por_pagina'] ?? 20,
                    'search' => $request->query('search', ''),
                    'routeName' => 'gestion-bodega.pedidos',
                ]);
            }

            // 🚀 CACHE: Deshabilitar caché del navegador para asegurar datos frescos
            // Al volver atrás, el navegador DEBE consultar al servidor
            return response($response)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los pedidos: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener datos de una fila individual para actualización en tiempo real
     */
    public function renderFilaPedido(int $id): JsonResponse
    {
        try {
            $fila = $this->bodegaPedidoService->obtenerDatosParaFila($id);
            return response()->json([
                'success' => true,
                'fila' => $fila
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mostrar lista de pedidos anulados (estado del pedido principal = ANULADA)
     */
    public function anulados(Request $request)
    {
        try {
            $datos = $this->bodegaPedidoService->obtenerPedidosAnuladosPaginados($request);

            if ($datos['view_type'] === 'details') {
                $esReadOnly = $this->isReadOnly();

                $viewName = $esReadOnly ? 'bodega.pedidos-readonly' : 'bodega.pedidos';

                $response = view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            } else {
                // Vista de lista - El filtro de pedidos ocultos ya se aplica en el servicio
                $response = view('bodega.index-list', [
                    'pedidosPorPagina' => $datos['pedidos_por_pagina'] ?? [],
                    'totalPedidos' => $datos['total_pedidos'] ?? 0,
                    'paginaActual' => $datos['pagina_actual'] ?? 1,
                    'porPagina' => $datos['por_pagina'] ?? 20,
                    'search' => $request->query('search', ''),
                    'routeName' => 'gestion-bodega.pedidos-anulados',
                ]);
            }

            return response($response)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@anulados: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pedidos anulados: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar lista de pedidos entregados
     */
    public function entregados(Request $request)
    {
        try {
            // Usar el método específico para pedidos entregados
            $datos = $this->bodegaPedidoService->obtenerPedidosEntregadosPaginados($request);

            if ($datos['view_type'] === 'details') {
                $esReadOnly = $this->isReadOnly();

                $viewName = $esReadOnly ? 'bodega.pedidos-readonly' : 'bodega.pedidos';

                $response = view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            } else {
                // Vista de lista - El filtro de pedidos ocultos ya se aplica en el servicio
                $response = view('bodega.index-list', [
                    'pedidosPorPagina' => $datos['pedidos_por_pagina'] ?? [],
                    'totalPedidos' => $datos['total_pedidos'] ?? 0,
                    'paginaActual' => $datos['pagina_actual'] ?? 1,
                    'porPagina' => $datos['por_pagina'] ?? 20,
                    'search' => $request->query('search', ''),
                    'routeName' => 'gestion-bodega.pedidos-entregados',
                ]);
            }

            return response($response)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@entregados: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pedidos entregados: ' . $e->getMessage());
        }
    }

    /**
     * Método genérico para mostrar detalles de pedidos pendientes por área
     * @param Request $request
     * @param int $pedidoId ID del detalle en bodega_detalles_talla
     * @param string $area 'Costura' o 'EPP'
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    private function showPendientesPorArea(Request $request, $pedidoId, string $area): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        try {
            // Obtener el detalle específico para conseguir el numero_pedido
            $detalle = DB::table('bodega_detalles_talla')->find($pedidoId);
            
            if (!$detalle) {
                return back()->with('error', 'No se encontró el detalle del pedido.');
            }
            
            $numeroPedido = $detalle->numero_pedido;
            
            // Buscar el pedido principal en pedidos_produccion
            $pedidoProduccion = DB::table('pedidos_produccion')
                ->where('numero_pedido', $numeroPedido)
                ->first();
            
            if (!$pedidoProduccion) {
                return back()->with('error', 'No se encontró el pedido principal.');
            }
            
            \Log::info("Accediendo desde bodega_detalles_talla - {$area}", [
                'detalle_id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'pedido_produccion_id' => $pedidoProduccion->id,
                'prenda' => $detalle->prenda_nombre,
                'area' => $detalle->area
            ]);
            
            // Marcar pedido como visto usando el numero_pedido resuelto
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();
            
            // Obtener datos desde bodega_detalles_talla
            $datos = $this->datosService->obtenerDatosDesdeBodegaDetalles($numeroPedido);
            $debugDetalleItems = $datos['items'] ?? [];

            $debugResumenAntes = [
                'numero_pedido' => $numeroPedido,
                'detalle_id_origen' => (int) $pedidoId,
                'area_objetivo' => $area,
                'total_items_antes_filtro' => count($debugDetalleItems),
                'areas_detectadas' => array_values(array_unique(array_map(
                    fn ($item) => (string) ($item['area'] ?? 'NULL'),
                    $debugDetalleItems
                ))),
                'estados_bodega_detectados' => array_values(array_unique(array_map(
                    fn ($item) => (string) ($item['estado_bodega'] ?? 'NULL'),
                    $debugDetalleItems
                ))),
                'costura_estados_detectados' => array_values(array_unique(array_map(
                    fn ($item) => (string) ($item['costura_estado'] ?? 'NULL'),
                    $debugDetalleItems
                ))),
                'resumen_por_prenda_talla' => array_values(array_map(
                    function ($itemsPorGrupo, $key) {
                        $primero = $itemsPorGrupo[0] ?? [];
                        return [
                            'grupo' => $key,
                            'cantidad_filas' => count($itemsPorGrupo),
                            'prenda' => (string) ($primero['prenda_nombre'] ?? 'SIN_PRENDA'),
                            'tallas' => array_values(array_unique(array_map(
                                fn ($it) => (string) ($it['talla'] ?? 'SIN_TALLA'),
                                $itemsPorGrupo
                            ))),
                        ];
                    },
                    array_values(collect($debugDetalleItems)->groupBy(fn ($item) => ($item['prenda_nombre'] ?? 'SIN_PRENDA'))->toArray()),
                    array_keys(collect($debugDetalleItems)->groupBy(fn ($item) => ($item['prenda_nombre'] ?? 'SIN_PRENDA'))->toArray())
                )),
            ];
            \Log::debug('[Bodega][showPendientesPorArea] Resumen antes de filtro', $debugResumenAntes);
            
            // Filtrar para mostrar solo artículos del área con estado_bodega 'Pendiente'
            if (isset($datos['items']) && is_array($datos['items'])) {
                $datos['items'] = array_filter($datos['items'], function($item) use ($area) {
                    return ($item['area'] ?? '') === $area && ($item['estado_bodega'] ?? '') === 'Pendiente';
                });

                // Reindexar el array después del filtro
                $datos['items'] = array_values($datos['items']);

                // Para EPP: excluir items que apunten a un pedido_epp que esté eliminado
                if ($area === 'EPP') {
                    $pedidoEppIds = array_filter(array_map(fn($item) => $item['pedido_epp_id'] ?? null, $datos['items']));

                    if (!empty($pedidoEppIds)) {
                        // Obtener EPPs eliminados que fueron homologados (tienen versiones más nuevas)
                        $eppsEliminadosConVersiones = DB::table('pedido_epp')
                            ->whereIn('id', $pedidoEppIds)
                            ->whereNotNull('deleted_at')
                            ->get()
                            ->pluck('id')
                            ->toArray();

                        // Excluir items que apunten a EPPs eliminados
                        if (!empty($eppsEliminadosConVersiones)) {
                            $datos['items'] = array_filter($datos['items'], function($item) use ($eppsEliminadosConVersiones) {
                                return !in_array($item['pedido_epp_id'] ?? null, $eppsEliminadosConVersiones);
                            });
                            $datos['items'] = array_values($datos['items']);
                        }
                    }
                }

                // DEBUG: Items después del filtro de eliminados
                \Log::debug('[showPendientesPorArea] Items después de filtrar eliminados', [
                    'area' => $area,
                    'total_items' => count($datos['items']),
                    'items' => array_map(fn($item) => [
                        'prenda' => $item['prenda_nombre'] ?? null,
                        'pedido_epp_id' => $item['pedido_epp_id'] ?? null,
                        'estado_bodega' => $item['estado_bodega'] ?? null,
                    ], $datos['items']),
                ]);

                // Normalizar campos para la vista
                $datos['items'] = array_map(function($item) use ($pedidoProduccion) {
                    if (!isset($item['cantidad']) && isset($item['cantidad_total'])) {
                        $item['cantidad'] = $item['cantidad_total'];
                    }

                    if (empty($item['prenda_nombre']) && isset($item['descripcion']) && is_array($item['descripcion'])) {
                        $item['prenda_nombre'] = $item['descripcion']['nombre_prenda']
                            ?? $item['descripcion']['nombre']
                            ?? null;
                    }

                    // Si es un EPP, obtener el historial de homologaciones
                    if (($item['area'] ?? '') === 'EPP' && !empty($item['pedido_epp_id'] ?? null)) {
                        $pedidoEppIdActual = $item['pedido_epp_id'];

                        // Buscar en pedido_epp si este es una versión homologada
                        $pedidoEppRecord = \App\Models\PedidoEpp::withTrashed()
                            ->find($pedidoEppIdActual);

                        // Si es una versión homologada (tiene homologado_de), usar el original para obtener el historial
                        $pedidoEppIdParaHistorial = $pedidoEppRecord && $pedidoEppRecord->homologado_de
                            ? $pedidoEppRecord->homologado_de
                            : $pedidoEppIdActual;

                        // Obtener la cadena completa de homologaciones
                        $todaLaCadena = $this->obtenerCadenaEppCompleta($pedidoEppIdParaHistorial);
                        $historial = [];
                        foreach ($todaLaCadena as $pedidoEpp) {
                            $historial[] = [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'epp_id' => $pedidoEpp->epp_id,
                                'epp_nombre' => $pedidoEpp->epp->nombre_completo ?? 'EPP sin nombre',
                                'cantidad' => $pedidoEpp->cantidad,
                                'fecha_creacion' => $pedidoEpp->created_at?->format('Y-m-d H:i'),
                                'deleted_at' => $pedidoEpp->deleted_at?->format('Y-m-d H:i'),
                                'observaciones' => $pedidoEpp->observaciones ?? '',
                                'es_original' => $pedidoEpp->homologado_de === null,
                            ];
                        }

                        $item['tiene_historial'] = count($historial) > 1;
                        $item['historial_homologaciones'] = $historial;
                    } else {
                        $item['tiene_historial'] = false;
                        $item['historial_homologaciones'] = [];
                    }

                    return $item;
                }, $datos['items']);

                // En EPP, ocultar filas antiguas de homologación y dejar solo la versión vigente.
                // Así se muestra una sola fila con badge "(homologado)" + botón "Ver cambios".
                if ($area === 'EPP') {
                    // Primero: filtrar por items con historial
                    $datos['items'] = array_values(array_filter($datos['items'], function ($item) {
                        if (!($item['tiene_historial'] ?? false)) {
                            return true;
                        }

                        $historial = $item['historial_homologaciones'] ?? [];
                        if (count($historial) <= 1) {
                            return true;
                        }

                        $ultimo = end($historial);
                        $ultimoPedidoEppId = $ultimo['pedido_epp_id'] ?? null;
                        $ultimoNombre = trim((string) ($ultimo['epp_nombre'] ?? ''));
                        $pedidoEppIdItem = $item['pedido_epp_id'] ?? null;
                        $nombreItem = trim((string) ($item['prenda_nombre'] ?? ''));

                        // Priorizar match por ID; fallback por nombre para casos legacy.
                        if ($pedidoEppIdItem && $ultimoPedidoEppId) {
                            return (int) $pedidoEppIdItem === (int) $ultimoPedidoEppId;
                        }

                        return $ultimoNombre !== '' && mb_strtoupper($nombreItem) === mb_strtoupper($ultimoNombre);
                    }));

                    // Segundo: eliminar duplicados por prenda_nombre, manteniendo el más reciente (ID más alto)
                    $itemsPorPrenda = [];
                    foreach ($datos['items'] as $item) {
                        $nombre = trim((string) ($item['prenda_nombre'] ?? ''));
                        if (empty($nombre)) continue;

                        if (!isset($itemsPorPrenda[$nombre])) {
                            $itemsPorPrenda[$nombre] = $item;
                        } else {
                            // Si ya existe, mantener el que tiene el ID más alto (más reciente)
                            $idActual = (int) ($item['id'] ?? 0);
                            $idExistente = (int) ($itemsPorPrenda[$nombre]['id'] ?? 0);
                            if ($idActual > $idExistente) {
                                $itemsPorPrenda[$nombre] = $item;
                            }
                        }
                    }

                    $datos['items'] = array_values($itemsPorPrenda);
                }
                
                // Actualizar contadores si existen
                if (isset($datos['estadisticas'])) {
                    $estadisticaKey = $area === 'EPP' ? 'total_epp_pendientes' : 'total_costura_pendientes';
                    $datos['estadisticas'][$estadisticaKey] = count($datos['items']);
                }
            }

            $debugItemsFiltrados = $datos['items'] ?? [];
            $debugExcluidos = array_values(array_filter($debugDetalleItems, function ($item) use ($area) {
                return (($item['area'] ?? '') !== $area) || (($item['estado_bodega'] ?? '') !== 'Pendiente');
            }));

            $debugResumenDespues = [
                'numero_pedido' => $numeroPedido,
                'area_objetivo' => $area,
                'total_items_despues_filtro' => count($debugItemsFiltrados),
                'total_excluidos_por_filtro' => count($debugExcluidos),
                'excluidos' => array_map(function ($item) use ($area) {
                    return [
                        'id' => $item['id'] ?? null,
                        'prenda' => $item['prenda_nombre'] ?? null,
                        'talla' => $item['talla'] ?? null,
                        'area_real' => $item['area'] ?? null,
                        'estado_bodega_real' => $item['estado_bodega'] ?? null,
                        'motivo' => (($item['area'] ?? '') !== $area)
                            ? 'area_distinta'
                            : 'estado_bodega_distinto_de_Pendiente',
                    ];
                }, $debugExcluidos),
            ];
            \Log::debug('[Bodega][showPendientesPorArea] Resumen despues de filtro', $debugResumenDespues);

            $datos['debugCostura'] = [
                'resumen_antes' => $debugResumenAntes,
                'resumen_despues' => $debugResumenDespues,
            ];
            
            // Agregar información del filtro aplicado (solo para Costura)
            if ($area === 'Costura') {
                $datos['filtro_aplicado'] = [
                    'area' => 'Costura',
                    'estado' => 'Pendiente',
                    'descripcion' => 'Mostrando solo artículos de Costura con estado Pendiente'
                ];
            }
            
            // Verificar si el usuario es de solo lectura (usando Trait)
            $datos['esReadOnly'] = $this->isReadOnly();
            
            // Seleccionar vista según área
            $viewName = $area === 'Costura' ? 'bodega.pendiente-costura-show' : 'bodega.pendiente-epp-show';
            
            return view($viewName, $datos);
            
        } catch (\Exception $e) {
            \Log::error("Error en showPendientes{$area}: " . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los detalles del pedido: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles de un pedido específico (solo EPP Pendiente)
     */
    public function showPendienteEpp(Request $request, $pedidoId)
    {
        return $this->showPendientesPorArea($request, $pedidoId, 'EPP');
    }

    /**
     * Mostrar detalles de un pedido específico (solo Costura Pendiente)
     */
    public function showPendienteCostura(Request $request, $pedidoId)
    {
        return $this->showPendientesPorArea($request, $pedidoId, 'Costura');
    }

    /**
     * Mostrar detalles de un pedido específico
     */
    public function show(Request $request, $pedidoId)
    {
        try {
            // Activar performance tracking si se solicita vía header
            $enablePerf = $request->header('X-Perf-Debug') === 'true';
            $tracker = null;

            if ($enablePerf) {
                $tracker = new \App\Support\QueryPerformanceTracker();
                \DB::listen(function ($event) use ($tracker) {
                    $tracker->recordQuery($event);
                });
                $tracker->startPhase('obtenerDetallePedido');
            }

            // Buscar por ID, no por numero_pedido
            $pedidoProduccion = PedidoProduccion::findOrFail((int) $pedidoId);

            // Marcar pedido como visto por numero_pedido.
            PedidoProduccion::where('numero_pedido', $pedidoProduccion->numero_pedido)
                ->update(['viewed_at' => Carbon::now()]);

            $datos = $this->bodegaPedidoService->obtenerDetallePedido((int) $pedidoProduccion->numero_pedido, false, true);

            // DEBUG: Log de items de EPP encontrados
            $itemsEpp = array_filter($datos['items'] ?? [], fn($item) => ($item['area'] ?? '') === 'EPP');
            \Log::debug('[show] Items EPP encontrados', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedidoProduccion->numero_pedido,
                'total_items_epp' => count($itemsEpp),
                'items_epp' => array_map(fn($item) => [
                    'id' => $item['id'] ?? null,
                    'prenda_nombre' => $item['prenda_nombre'] ?? null,
                    'estado_bodega' => $item['estado_bodega'] ?? null,
                    'pedido_epp_id' => $item['pedido_epp_id'] ?? null,
                ], $itemsEpp),
            ]);

            if ($tracker) {
                $tracker->endPhase('obtenerDetallePedido');
                $tracker->startPhase('procesarDatos');
            }

            // Verificar si el usuario es de solo lectura
            $rolesDelUsuario = $this->getUserRoles();
            $esReadOnly = $this->isReadOnly();

            // Filtrar items según el rol del usuario
            if (in_array('EPP-Bodega', $rolesDelUsuario)) {
                if (isset($datos['items']) && is_array($datos['items'])) {
                    $datos['items'] = array_filter($datos['items'], function($item) {
                        return ($item['area'] ?? '') === 'EPP' && ($item['estado_bodega'] ?? '') === 'Pendiente';
                    });
                    $datos['items'] = array_values($datos['items']);
                }
            } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
                if (isset($datos['items']) && is_array($datos['items'])) {
                    $datos['items'] = array_filter($datos['items'], function($item) {
                        return ($item['area'] ?? '') === 'Costura' && ($item['estado_bodega'] ?? '') === 'Pendiente';
                    });
                    $datos['items'] = array_values($datos['items']);
                }
            }

            // Eliminar duplicados de EPP, manteniendo solo la versión más reciente por prenda
            if (isset($datos['items']) && is_array($datos['items'])) {
                $itemsPorPrenda = [];
                foreach ($datos['items'] as $item) {
                    if (($item['area'] ?? '') !== 'EPP') {
                        // Para no-EPP, mantener todos
                        $itemsPorPrenda[] = $item;
                        continue;
                    }

                    $nombre = trim((string) ($item['prenda_nombre'] ?? ''));
                    if (empty($nombre)) {
                        $itemsPorPrenda[] = $item;
                        continue;
                    }

                    $key = 'epp_' . $nombre;
                    if (!isset($itemsPorPrenda[$key])) {
                        $itemsPorPrenda[$key] = $item;
                    } else {
                        // Si ya existe, mantener el que tiene el ID más alto (más reciente)
                        $idActual = (int) ($item['id'] ?? 0);
                        $idExistente = (int) ($itemsPorPrenda[$key]['id'] ?? 0);
                        if ($idActual > $idExistente) {
                            $itemsPorPrenda[$key] = $item;
                        }
                    }
                }
                $datos['items'] = array_values($itemsPorPrenda);
            }

            $datos['esReadOnly'] = $esReadOnly;

            if ($tracker) {
                $tracker->endPhase('procesarDatos');
                $tracker->logFull();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $datos,
                ]);
            }

            return view('bodega.show', $datos);

        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@show: ' . $e->getMessage());

            if ($request->boolean('inline') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los detalles del pedido: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Error al cargar los detalles del pedido: ' . $e->getMessage());
        }
    }

    /**
     * Desmarcar pedido como no visto
     */
    public function desmarcar(Request $request, $pedidoId): JsonResponse
    {
        $result = $this->updateService->desmarcar($pedidoId);
        
        $statusCode = $result['success'] ? 200 : ($result['message'] === 'No tienes permisos para realizar esta acción.' ? 403 : 400);
        
        return response()->json($result, $statusCode);
    }

    public function marcarVisto(Request $request, $pedidoId): JsonResponse
    {
        $visto = $request->input('visto', false);
        $result = $this->updateService->marcarVisto($pedidoId, $visto);
        
        $statusCode = $result['success'] ? 200 : ($result['message'] === 'No tienes permisos para realizar esta acción.' ? 403 : 400);
        
        return response()->json($result, $statusCode);
    }

    /**
     * Marcar pedido como revisado
     */
    public function revisar(Request $request, $pedidoId): JsonResponse
    {
        $revisado = $request->input('revisado', false);
        $result = $this->pedidoEstadoService->marcarComoRevisado($pedidoId, $revisado);
        $statusCode = $this->pedidoEstadoService->obtenerStatusCode($result);

        return response()->json($result, $statusCode);
    }

    /**
     * Ocultar pedido para el usuario actual
     */
    public function ocultarPedido(Request $request, $pedidoId): JsonResponse
    {
        $result = $this->pedidoEstadoService->ocultarPedido($pedidoId);
        $statusCode = $this->pedidoEstadoService->obtenerStatusCode($result);

        return response()->json($result, $statusCode);
    }

    /**
     * Deshacer ocultamiento de pedido (mostrar nuevamente)
     */
    public function deshacerOcultarPedido(Request $request, $pedidoId): JsonResponse
    {
        $result = $this->pedidoEstadoService->deshacerOcultarPedido($pedidoId);
        $statusCode = $this->pedidoEstadoService->obtenerStatusCode($result);

        return response()->json($result, $statusCode);
    }

    /**
     * Ver lista de pedidos ocultos por el usuario actual
     */
    public function ocultosIndex(Request $request)
    {
        $resultado = $this->pedidoEstadoService->obtenerPedidosOcultos($request);

        if (!$resultado['success']) {
            return back()->with('error', 'Error al cargar los pedidos ocultos: ' . $resultado['message']);
        }

        $viewData = $resultado;
        unset($viewData['success']);

        return view('bodega.pedidos-ocultos', $viewData);
    }

    /**
     * Marcar detalle de bodega como visto
     */
    public function marcarVistoDetalle(Request $request, $detalleId): JsonResponse
    {
        $visto = $request->input('visto', false);
        $result = $this->updateService->marcarVistoDetalle($detalleId, $visto);
        
        $statusCode = $result['success'] ? 200 : ($result['message'] === 'No tienes permisos para realizar esta acción.' ? 403 : 400);
        
        return response()->json($result, $statusCode);
    }

    /**
     * Método genérico para obtener pedidos pendientes por área
     * @param Request $request
     * @param string $area 'Costura' o 'EPP'
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function pendienteEpp(Request $request)
    {
        $datos = $this->pedidoListadoService->obtenerPendientesPorArea($request, 'EPP');
        $viewName = $datos['viewName'] ?? 'bodega.pendiente-epp';
        unset($datos['viewName']);

        return view($viewName, $datos);
    }

    /**
     * Mostrar lista simplificada de EPP pendientes para supervisor
     * Tabla con columnas: fecha, asesor, cliente, cantidad, producto, fecha de entrega a despacho
     */
    public function pendientesEppList(Request $request)
    {
        try {
            // Obtener EPP pendientes sin agrupar - una fila por cada registro
            $query = BodegaDetalleTalla::where('bodega_detalles_talla.area', 'EPP')
                ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
                ->where('bodega_detalles_talla.pedido_epp_id', '!=', null)
                ->join('pedido_epp as pe', 'bodega_detalles_talla.pedido_epp_id', '=', 'pe.id')
                ->whereNull('pe.deleted_at')
                ->whereNotNull('bodega_detalles_talla.numero_pedido')
                ->where('bodega_detalles_talla.numero_pedido', '!=', '')
                ->leftJoin('pedidos_produccion', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->select(
                    'bodega_detalles_talla.*',
                    'pedidos_produccion.created_at as pedido_created_at'
                )
                ->orderBy('pedidos_produccion.created_at', 'desc')
                ->orderBy('bodega_detalles_talla.numero_pedido', 'desc')
                ->orderBy('bodega_detalles_talla.id', 'desc');

            // Aplicar búsqueda si existe
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('bodega_detalles_talla.numero_pedido', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.empresa', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.asesor', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.prenda_nombre', 'LIKE', "%{$search}%");
                });
            }

            $epp_pendientes = $query->paginate(20);

            \Log::info('Pendientes EPP obtenidos: ' . $epp_pendientes->total());

            return view('bodega.pendientes-epp-list', [
                'epp_pendientes' => $epp_pendientes,
                'total' => $epp_pendientes->total(),
                'search' => $request->query('search', ''),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en pendientesEppList: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pendientes de EPP: ' . $e->getMessage());
        }
    }

    /**
     * Exportar EPP pendientes a Excel
     */
    public function exportarPendientesEpp(Request $request)
    {
        try {
            // Obtener datos sin paginar
            $query = BodegaDetalleTalla::where('bodega_detalles_talla.area', 'EPP')
                ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
                ->where('bodega_detalles_talla.pedido_epp_id', '!=', null)
                ->join('pedido_epp as pe', 'bodega_detalles_talla.pedido_epp_id', '=', 'pe.id')
                ->whereNull('pe.deleted_at')
                ->whereNotNull('bodega_detalles_talla.numero_pedido')
                ->where('bodega_detalles_talla.numero_pedido', '!=', '')
                ->leftJoin('pedidos_produccion', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->select(
                    'bodega_detalles_talla.*',
                    'pedidos_produccion.created_at as pedido_created_at'
                )
                ->orderBy('pedidos_produccion.created_at', 'desc')
                ->orderBy('bodega_detalles_talla.numero_pedido', 'desc')
                ->orderBy('bodega_detalles_talla.id', 'desc');

            // Aplicar búsqueda si existe
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('bodega_detalles_talla.numero_pedido', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.empresa', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.asesor', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.prenda_nombre', 'LIKE', "%{$search}%");
                });
            }

            $epp_pendientes = $query->get();

            // Crear nuevo Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar encabezados
            $headers = ['Fecha Pedido', 'Asesor', 'Cliente', 'Nº Pedido', 'Producto', 'Cantidad', 'Fecha Entrega a Despacho'];
            
            // Estilos para encabezados
            $headerFill = new PatternFill(
                PatternFill::FILL_SOLID,
                '1F2937',
                '1F2937'
            );
            $headerFont = new Font(['bold' => true, 'color' => ['rgb' => 'FFFFFF']]);

            // Agregar encabezados
            foreach ($headers as $col => $header) {
                $cell = $sheet->getCellByColumnAndRow($col + 1, 1);
                $cell->setValue($header);
                $cell->getStyle()->setFont($headerFont);
                $cell->getStyle()->setFill($headerFill);
                $cell->getStyle()->setAlignment(new Alignment(Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_CENTER));
            }

            // Agregar datos
            $row = 2;
            foreach ($epp_pendientes as $item) {
                $fechaPedido = $item->pedido_created_at ? \Carbon\Carbon::parse($item->pedido_created_at)->format('d/m/Y') : '—';
                $fechaEntrega = $item->fecha_entrega ? \Carbon\Carbon::parse($item->fecha_entrega)->format('d/m/Y') : '—';

                $sheet->setCellValue('A' . $row, $fechaPedido);
                $sheet->setCellValue('B' . $row, $item->asesor ?? '—');
                $sheet->setCellValue('C' . $row, $item->empresa ?? '—');
                $sheet->setCellValue('D' . $row, $item->numero_pedido);
                $sheet->setCellValue('E' . $row, $item->prenda_nombre ?? '—');
                $sheet->setCellValue('F' . $row, $item->cantidad ?? 0);
                $sheet->setCellValue('G' . $row, $fechaEntrega);

                // Alineaciones
                $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $row++;
            }

            // Ajustar ancho de columnas
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(30);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(20);

            // Crear writer y descarga
            $writer = new Xlsx($spreadsheet);
            $filename = 'Pendientes-EPP-' . date('Y-m-d-H-i-s') . '.xlsx';

            headers_sent() ?: header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            headers_sent() ?: header('Content-Disposition: attachment;filename="' . $filename . '"');
            headers_sent() ?: header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            \Log::error('Error en exportarPendientesEpp: ' . $e->getMessage());
            return back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * Marcar pedido como entregado usando CQRS
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            // Crear Command con CQRS
            $command = new EntregarPedidoCommand(
                $id,
                $request->input('observaciones'),
                auth()->id()
            );

            // Ejecutar Command usando CQRS Manager
            $resultado = $this->cqrsManager->execute($command);

            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('Error en entregar (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como entregado: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener estadísticas de pedidos usando CQRS
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            // Crear Query con CQRS
            $query = new ObtenerEstadisticasPedidosQuery(
                $request->input('areas'), // ['Costura', 'EPP', etc.]
                $request->input('estados'), // ['ENTREGADO', 'EN EJECUCIÓN', etc.]
                $request->input('fecha_desde') ? new \DateTime($request->input('fecha_desde')) : null,
                $request->input('fecha_hasta') ? new \DateTime($request->input('fecha_hasta')) : null
            );

            // Ejecutar Query usando CQRS Manager
            $resultado = $this->cqrsManager->ask($query);

            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('Error en estadisticas (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estado de pedido usando CQRS
     */
    public function actualizarEstado(Request $request, $id): JsonResponse
    {
        try {
            // Validar y crear Command con CQRS
            $validated = $request->validate([
                'estado' => 'required|string',
                'motivo' => 'nullable|string|max:500',
            ]);

            $nuevoEstado = EstadoPedido::desdeString($validated['estado']);
            
            $command = new ActualizarEstadoPedidoCommand(
                $id,
                $nuevoEstado,
                $validated['motivo'] ?? null,
                auth()->id()
            );

            // Ejecutar Command usando CQRS Manager
            $resultado = $this->cqrsManager->execute($command);

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarEstado (CQRS): ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar cache de queries CQRS
     */
    public function limpiarCache(Request $request): JsonResponse
    {
        try {
            $queryId = $request->input('query_id');
            
            if ($queryId) {
                $this->cqrsManager->clearQueryCacheFor($queryId);
                $message = "Cache limpiado para query: {$queryId}";
            } else {
                $this->cqrsManager->clearQueryCache();
                $message = "Todo el cache de queries ha sido limpiado";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'limpiado_en' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en limpiarCache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del sistema CQRS
     */
    public function cqrsStats(): JsonResponse
    {
        try {
            $stats = $this->cqrsManager->getStats();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'generado_en' => now()->toDateTimeString(),
                'version' => '1.0.0'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en cqrsStats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas CQRS: ' . $e->getMessage()
            ], 500);
        }
    }
    public function actualizarObservaciones(Request $request): JsonResponse
    {
        return $this->pedidoActualizacionService->actualizarObservaciones($request);
    }

    /**
     * Actualizar fecha de entrega
     */
    public function actualizarFecha(Request $request): JsonResponse
    {
        return $this->pedidoActualizacionService->actualizarFecha($request);
    }

    /**
     * Obtener datos de factura para modal - Usa la misma vista que Despacho
     */
    public function obtenerDatosFacturaJSON($id)
    {
        return $this->pedidoConsultasService->obtenerDatosFactura($id);
    }

    /**
     * Obtener datos de factura
     */
    public function obtenerDatosFactura($id)
    {
        return $this->pedidoConsultasService->obtenerDatosFactura($id);
    }

    /**
     * Determinar estado del pedido
     */


    /**
     * Guardar detalles de bodega por talla
     */
    public function guardarDetallesTalla(Request $request): JsonResponse
    {
        \Log::info('[GUARDAR DETALLES TALLA] Método llamado', [
            'numero_pedido' => $request->get('numero_pedido'),
            'talla' => $request->get('talla'),
            'prenda_id' => $request->get('prenda_id'),
            'pedido_epp_id' => $request->get('pedido_epp_id'),
        ]);
        
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
                'prenda_nombre' => 'nullable|string',
                'prenda_id' => 'nullable|integer',
                'pedido_epp_id' => 'nullable|integer',
                'asesor' => 'nullable|string',
                'empresa' => 'nullable|string',
                'cantidad' => 'nullable|integer',
                'pendientes' => 'nullable|integer',
                'observaciones_bodega' => 'nullable|string',
                'fecha_entrega' => 'nullable|date',
                'fecha_pedido' => 'nullable|date',
                'estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado,Homologar',
                'costura_estado' => 'nullable|string|in:Pendiente,Entregado,Anulado,Homologar',
                'epp_estado' => 'nullable|string|in:Pendiente,Entregado,Anulado,Homologar',
                'area' => 'nullable|string|in:Costura,EPP,Otro',
                'last_updated_at' => 'nullable|string',
            ]);

            $resultado = $this->guardadoService->guardarDetalles($validated);
            
            return response()->json($resultado);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarDetallesTalla: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
    /**
     * Guardar todos los detalles de un pedido
     */
    public function guardarPedidoCompleto(Request $request): JsonResponse
    {
        \Log::info('[GUARDAR PEDIDO COMPLETO] Método llamado', [
            'numero_pedido' => $request->get('numero_pedido'),
            'detalles_count' => count($request->get('detalles', []))
        ]);
        
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'detalles' => 'required|array',
                'detalles.*.talla' => 'nullable|string',
                'detalles.*.talla_color_id' => 'nullable|integer',
                'detalles.*.asesor' => 'nullable|string',
                'detalles.*.empresa' => 'nullable|string',
                'detalles.*.cantidad' => 'nullable|integer',
                'detalles.*.prenda_nombre' => 'nullable|string',
                'detalles.*.prenda_id' => 'nullable|integer',
                'detalles.*.pedido_epp_id' => 'nullable|integer',
                'detalles.*.pendientes' => 'nullable|integer',
                'detalles.*.observaciones_bodega' => 'nullable|string',
                'detalles.*.fecha_pedido' => 'nullable|date',
                'detalles.*.fecha_entrega_bodega' => 'nullable|date',
                'detalles.*.area' => 'nullable|string|in:Costura,EPP,Otro',
                'detalles.*.estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado,Homologar',
            ]);

            $pedido = PedidoProduccion::where('numero_pedido', $validated['numero_pedido'])->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $camposAuditar = ['asesor', 'empresa', 'cantidad', 'prenda_nombre', 'pendientes', 'observaciones_bodega', 'fecha_entrega_bodega', 'area', 'estado_bodega'];
            
            $resultado = $this->guardadoService->guardarMultiplesDetalles(
                $pedido,
                $validated['detalles'],
                $camposAuditar
            );
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en guardarPedidoCompleto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una fila individual de bodega_detalles_talla (crear o actualizar)
     */
    public function guardarFilaCompleta(Request $request, string $numeroPedido): JsonResponse
    {
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            $validated = $request->validate([
                'row_hash' => 'required|string',
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'genero' => 'nullable|string',
                'talla_color_id' => 'nullable|integer',
                'prenda_id' => 'nullable|integer',
                'pedido_epp_id' => 'nullable|integer',
                'recibo_prenda_id' => 'nullable|integer',
                'pedido_produccion_id' => 'nullable|integer',
                'asesor' => 'nullable|string',
                'empresa' => 'nullable|string',
                'cantidad' => 'nullable|integer',
                'prenda_nombre' => 'nullable|string',
                'pendientes' => 'nullable|integer',
                'fecha_pedido' => 'nullable|date',
                'fecha_entrega' => 'nullable|date',
                'fecha_entrega_bodega' => 'nullable|date',
                'area' => 'nullable|string|in:Costura,EPP,Otro',
                'estado_bodega' => 'nullable|string|in:Pendiente,Entregado,Anulado,Homologar',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->guardadoService->guardarFilaCompleta(
                $numeroPedido,
                $validated,
                $request
            );

            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('[GUARDAR FILA] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar una nota de bodega
     */
    public function guardarNota(Request $request): JsonResponse
    {
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        return $this->pedidoNotasService->guardarNota($request);
    }

    /**
     * Obtener historial de notas para un pedido y talla
     */
    public function obtenerNotas(Request $request, $numero_pedido = null, $talla = null): JsonResponse
    {
        return $this->pedidoNotasService->obtenerNotas($request, $numero_pedido, $talla);
    }

    /**
     * Actualizar una nota existente
     */
    public function actualizarNota(Request $request, $notaId): JsonResponse
    {
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        return $this->pedidoNotasService->actualizarNota($request, $notaId);
    }

    /**
     * Eliminar una nota
     */
    public function eliminarNota(Request $request, $notaId): JsonResponse
    {
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        return $this->pedidoNotasService->eliminarNota($request, $notaId);
    }

    /**
     * Obtener datos para filtros dinámicos
     */
    public function obtenerDatosFiltro(Request $request, string $tipo): JsonResponse
    {
        return $this->pedidoFiltroService->obtenerDatosFiltro($request, $tipo);
    }





    /**
     * Actualizar fecha de entrega a despacho
     */
    public function actualizarFechaEntregaDespacho(Request $request, $id): JsonResponse
    {
        return $this->pedidoActualizacionService->actualizarFechaEntregaDespacho($request, $id);
    }

    /**
     * Obtener notificaciones para la campana de bodega
     */
    public function getNotifications(): JsonResponse
    {
        $data = $this->notificacionService->obtenerNotificaciones();
        
        return response()->json([
            'success' => $data['success'] ?? false,
            'notificaciones' => $data['notificaciones'] ?? [],
            'novedades' => $data['novedades'] ?? [],
            'totalPendientes' => $data['totalPendientes'] ?? 0,
            'totalOrdenesNoVistas' => $data['totalOrdenesNoVistas'] ?? 0,
            'totalNovedades' => $data['totalNovedades'] ?? 0,
            'totalNovedadesNoVistas' => $data['totalNovedadesNoVistas'] ?? 0,
            'totalGeneral' => $data['totalGeneral'] ?? 0,
        ]);
    }

    /**
     * DEBUG: Verificar estado de autenticación
     */
    public function debugNotifications(): JsonResponse
    {
        $user = Auth::user();
        
        $allNews = \DB::table('news')->get();
        $typesInNews = \DB::table('news')->distinct()->pluck('event_type');
        $recentNews = \DB::table('news')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();
        
        return response()->json([
            'authenticated' => !!$user,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_name' => $user?->name,
            'time' => now()->toIso8601String(),
            'session_id' => session()->getId(),
            'message' => $user ? 'Usuario autenticado correctamente' : 'NO AUTENTICADO - Auth::user() es null',
            'news_debug' => [
                'total_news_records' => $allNews->count(),
                'event_types_in_db' => $typesInNews->toArray(),
                'recent_news_3months' => $recentNews->count(),
                'sample_news' => $recentNews->take(5)->toArray(),
            ]
        ]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        $success = $this->notificacionService->marcarTodoComoLeido();
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Todas marcadas como leídas' : 'Error al marcar notificaciones'
        ]);
    }

    /**
     * Toggle visto de una novedad (News)
     */
    public function toggleNewsVisto($newsId): JsonResponse
    {
        $success = $this->notificacionService->toggleNewsVisto($newsId);
        
        return response()->json([
            'success' => $success,
            'visto' => $success ? true : false
        ]);
    }

    /**
     * Obtener datos de homologación de un EPP
     */
    public function obtenerDatosHomologacion($eppId): JsonResponse
    {
        return $this->pedidoConsultasService->obtenerDatosHomologacion($eppId);
    }

    /**
     * Toggle visto de un pedido
     */
    public function togglePedidoVisto($pedidoId): JsonResponse
    {
        $success = $this->notificacionService->togglePedidoVisto($pedidoId);

        return response()->json([
            'success' => $success,
            'visto' => $success ? true : false
        ]);
    }

    /**
     * Obtener la cadena completa de EPPs homologados
     */
    private function obtenerCadenaEppCompleta(int $pedidoEppIdOriginal): \Illuminate\Support\Collection
    {
        $cadena = collect();
        $eppsVisitados = collect();
        $eppIdActual = $pedidoEppIdOriginal;
        $intentos = 0;
        $maxIntentos = 30;

        while ($eppIdActual !== null && $intentos < $maxIntentos) {
            $intentos++;

            if ($eppsVisitados->contains($eppIdActual)) {
                \Log::warning('[obtenerCadenaEppCompleta] Ciclo detectado', [
                    'pedido_epp_id' => $eppIdActual,
                    'epps_visitados' => $eppsVisitados->toArray(),
                ]);
                break;
            }

            $eppsVisitados->push($eppIdActual);

            // Cargar este EPP
            $epp = \App\Models\PedidoEpp::withTrashed()
                ->with('epp')
                ->find($eppIdActual);

            if (!$epp) {
                break;
            }

            $cadena->push($epp);

            // Buscar el siguiente en la cadena (homologado_de = eppIdActual)
            $siguiente = \App\Models\PedidoEpp::where('homologado_de', $eppIdActual)
                ->withTrashed()
                ->first();

            $eppIdActual = $siguiente?->id;
        }

        return $cadena;
    }

}
