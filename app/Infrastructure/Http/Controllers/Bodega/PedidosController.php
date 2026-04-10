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
        private BodegaNotaService $notaService,
        private BodegaAuditoriaService $auditoriaService,
        private CQRSManager $cqrsManager,
        private BodegaFiltroService $filtroService,
        private BodegaDatosService $datosService,
        private BodegaNotificacionService $notificacionService,
        private BodegaUpdateService $updateService,
        private BodegaGuardadoService $guardadoService,
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
                
                return view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            }
            
            // Vista de lista - Filtrar pedidos ocultos por el usuario actual
            $userId = auth()->id();
            $pediodosOcultosIds = PedidoOculto::where('user_id', $userId)
                ->pluck('pedido_id')
                ->toArray();
            
            \Log::info('[PEDIDOS-FILTRO] Usuario ocultos', [
                'user_id' => $userId,
                'pedidos_ocultos_count' => count($pediodosOcultosIds),
                'pedidos_ocultos_ids' => $pediodosOcultosIds,
                'total_pedidos_antes' => count($datos['pedidos_por_pagina'] ?? [])
            ]);
            
            // Filtrar pedidos que no estén ocultos
            $pedidosPorPagina = array_filter($datos['pedidos_por_pagina'] ?? [], function($pedido) use ($pediodosOcultosIds) {
                return !in_array($pedido['id'], $pediodosOcultosIds);
            });
            
            \Log::info('[PEDIDOS-FILTRO] Después de filtrar', [
                'total_pedidos_despues' => count($pedidosPorPagina)
            ]);
            
            return view('bodega.index-list', [
                'pedidosPorPagina' => array_values($pedidosPorPagina), // Reindexa el array
                'totalPedidos' => count($pedidosPorPagina), // Actualizar total
                'paginaActual' => $datos['pagina_actual'] ?? 1,
                'porPagina' => $datos['por_pagina'] ?? 20,
                'search' => $request->query('search', ''),
                'routeName' => 'gestion-bodega.pedidos',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Error al cargar los pedidos: ' . $e->getMessage());
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

                return view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            }

            // Filtrar pedidos ocultos por el usuario actual
            $userId = auth()->id();
            $pediodosOcultosIds = PedidoOculto::where('user_id', $userId)
                ->pluck('pedido_id')
                ->toArray();
            
            $pedidosPorPagina = array_filter($datos['pedidos_por_pagina'] ?? [], function($pedido) use ($pediodosOcultosIds) {
                return !in_array($pedido['id'], $pediodosOcultosIds);
            });
            
            return view('bodega.index-list', [
                'pedidosPorPagina' => array_values($pedidosPorPagina),
                'totalPedidos' => count($pedidosPorPagina),
                'paginaActual' => $datos['pagina_actual'] ?? 1,
                'porPagina' => $datos['por_pagina'] ?? 20,
                'search' => $request->query('search', ''),
                'routeName' => 'gestion-bodega.pedidos-anulados',
            ]);
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

                return view($viewName, [
                    'pedidosAgrupados' => $datos['pedidos_agrupados'] ?? [],
                    'asesores' => $datos['asesores'] ?? [],
                    'paginacion' => $datos['pagination']['paginacion_obj'] ?? null,
                    'totalPedidos' => $datos['pagination']['total_pedidos'] ?? 0,
                    'datosBodega' => $datos['datos_bodega'] ?? collect(),
                    'notasBodega' => $datos['notas_bodega'] ?? collect(),
                    'esReadOnly' => $esReadOnly,
                ]);
            }

            // Filtrar pedidos ocultos por el usuario actual
            $userId = auth()->id();
            $pediodosOcultosIds = PedidoOculto::where('user_id', $userId)
                ->pluck('pedido_id')
                ->toArray();
            
            $pedidosPorPagina = array_filter($datos['pedidos_por_pagina'] ?? [], function($pedido) use ($pediodosOcultosIds) {
                return !in_array($pedido['id'], $pediodosOcultosIds);
            });
            
            return view('bodega.index-list', [
                'pedidosPorPagina' => array_values($pedidosPorPagina),
                'totalPedidos' => count($pedidosPorPagina),
                'paginaActual' => $datos['pagina_actual'] ?? 1,
                'porPagina' => $datos['por_pagina'] ?? 20,
                'search' => $request->query('search', ''),
                'routeName' => 'gestion-bodega.pedidos-entregados',
            ]);
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
            
            // Filtrar para mostrar solo artículos del área con estado_bodega 'Pendiente'
            if (isset($datos['items']) && is_array($datos['items'])) {
                $datos['items'] = array_filter($datos['items'], function($item) use ($area) {
                    return ($item['area'] ?? '') === $area && ($item['estado_bodega'] ?? '') === 'Pendiente';
                });
                
                // Reindexar el array después del filtro
                $datos['items'] = array_values($datos['items']);

                // Normalizar campos para la vista
                $datos['items'] = array_map(function($item) {
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
                        $pedidoEppId = $item['pedido_epp_id'];
                        
                        // PASO 1: Obtener el EPP actual
                        $eppActual = DB::table('pedido_epp')
                            ->select(['id', 'homologado_de'])
                            ->where('id', $pedidoEppId)
                            ->first();
                        
                        // PASO 2: Encontrar el EPP original en la cadena recursivamente
                        $eppIdOriginal = $pedidoEppId;
                        $intentos = 0;
                        $maxIntentos = 10;
                        
                        while ($intentos < $maxIntentos) {
                            $intentos++;
                            $eppPadre = DB::table('pedido_epp')
                                ->select(['id', 'homologado_de'])
                                ->where('id', $eppIdOriginal)
                                ->first();
                            
                            if (!$eppPadre || !$eppPadre->homologado_de) {
                                break; // Encontramos el original
                            }
                            
                            $eppIdOriginal = $eppPadre->homologado_de;
                        }
                        
                        // PASO 3: Obtener toda la cadena desde el original (recursivamente)
                        $historial = [];
                        $eppActualId = $eppIdOriginal;
                        $intentos = 0;
                        
                        while ($intentos < $maxIntentos) {
                            $intentos++;
                            
                            $epp = DB::table('pedido_epp')
                                ->leftJoin('epps', 'pedido_epp.epp_id', '=', 'epps.id')
                                ->where('pedido_epp.id', $eppActualId)
                                ->select([
                                    'pedido_epp.id as pedido_epp_id',
                                    'pedido_epp.epp_id',
                                    'epps.nombre_completo as epp_nombre',
                                    'pedido_epp.cantidad',
                                    DB::raw("DATE_FORMAT(pedido_epp.created_at, '%Y-%m-%d %H:%i') as fecha_creacion"),
                                    'pedido_epp.deleted_at',
                                    'pedido_epp.observaciones',
                                    'pedido_epp.homologado_de'
                                ])
                                ->first();
                            
                            if (!$epp) {
                                break;
                            }
                            
                            $historial[] = (array) $epp;
                            
                            // Buscar el siguiente en la cadena
                            $siguiente = DB::table('pedido_epp')
                                ->select(['id'])
                                ->where('homologado_de', $epp->pedido_epp_id)
                                ->first();
                            
                            if (!$siguiente) {
                                break;
                            }
                            
                            $eppActualId = $siguiente->id;
                        }
                        
                        // Procesar historial para que sea compatible con el JS
                        if (!empty($historial)) {
                            $historialeFormatted = array_map(function($h) {
                                return [
                                    'pedido_epp_id' => $h['pedido_epp_id'],
                                    'epp_id' => $h['epp_id'],
                                    'epp_nombre' => $h['epp_nombre'],
                                    'cantidad' => $h['cantidad'],
                                    'fecha_creacion' => $h['fecha_creacion'],
                                    'deleted_at' => $h['deleted_at'],
                                    'observaciones' => $h['observaciones'] ?? '-',
                                    'es_original' => $h['homologado_de'] === null,
                                ];
                            }, $historial);
                            
                            $item['tiene_historial'] = count($historialeFormatted) > 1;
                            $item['historial_homologaciones'] = $historialeFormatted;
                        } else {
                            $item['tiene_historial'] = false;
                            $item['historial_homologaciones'] = [];
                        }
                    } else {
                        $item['tiene_historial'] = false;
                        $item['historial_homologaciones'] = [];
                    }

                    return $item;
                }, $datos['items']);
                
                // Actualizar contadores si existen
                if (isset($datos['estadisticas'])) {
                    $estadisticaKey = $area === 'EPP' ? 'total_epp_pendientes' : 'total_costura_pendientes';
                    $datos['estadisticas'][$estadisticaKey] = count($datos['items']);
                }
            }
            
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
            // Modo estricto: esta ruta se resuelve SOLO por numero_pedido.
            $numeroPedido = (string) $pedidoId;
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Marcar pedido como visto por numero_pedido.
            PedidoProduccion::where('numero_pedido', $pedidoProduccion->numero_pedido)
                ->update(['viewed_at' => Carbon::now()]);
            
            $datos = $this->bodegaPedidoService->obtenerDetallePedido((int) $pedidoProduccion->numero_pedido, false, true);
            
            \Log::debug('[PedidosController@show] Datos obtenidos del servicio', [
                'items_count' => count($datos['items'] ?? []),
                'items_tipos' => array_unique(array_map(fn($item) => $item['tipo'] ?? 'unknown', $datos['items'] ?? [])),
                'items_areas' => array_unique(array_map(fn($item) => $item['area'] ?? 'null', $datos['items'] ?? [])),
                'rolesDelUsuario' => $this->getUserRoles(),
            ]);
            
            // Verificar si el usuario es de solo lectura
            $rolesDelUsuario = $this->getUserRoles();
            $esReadOnly = $this->isReadOnly();
            
            // Log antes de filtrar
            $itemsAntesDeFiltro = $datos['items'] ?? [];
            \Log::debug('[PedidosController@show] Items ANTES de filtro', [
                'count' => count($itemsAntesDeFiltro),
                'items' => array_map(fn($item) => [
                    'tipo' => $item['tipo'] ?? 'unknown',
                    'area' => $item['area'] ?? 'null',
                    'estado_bodega' => $item['estado_bodega'] ?? 'null'
                ], $itemsAntesDeFiltro)
            ]);
            
            // Filtrar items según el rol del usuario
            if (in_array('EPP-Bodega', $rolesDelUsuario)) {
                // EPP-Bodega: solo ver EPP pendientes
                if (isset($datos['items']) && is_array($datos['items'])) {
                    $datos['items'] = array_filter($datos['items'], function($item) {
                        return ($item['area'] ?? '') === 'EPP' && ($item['estado_bodega'] ?? '') === 'Pendiente';
                    });
                    $datos['items'] = array_values($datos['items']);
                }
            } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
                // Costura-Bodega: solo ver Costura pendientes
                if (isset($datos['items']) && is_array($datos['items'])) {
                    $datos['items'] = array_filter($datos['items'], function($item) {
                        return ($item['area'] ?? '') === 'Costura' && ($item['estado_bodega'] ?? '') === 'Pendiente';
                    });
                    $datos['items'] = array_values($datos['items']);
                }
            }
            
            // Log después de filtrar
            \Log::debug('[PedidosController@show] Items DESPUÉS de filtro', [
                'count' => count($datos['items'] ?? []),
                'filtro_aplicado' => in_array('EPP-Bodega', $rolesDelUsuario) ? 'EPP-Bodega' : (in_array('Costura-Bodega', $rolesDelUsuario) ? 'Costura-Bodega' : 'ninguno')
            ]);
            
            // Agregar la variable esReadOnly a los datos
            $datos['esReadOnly'] = $esReadOnly;
            
            return view('bodega.show', $datos);
            
        } catch (\Exception $e) {
            \Log::error('Error en PedidosController@show: ' . $e->getMessage());
            
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
        try {
            $revisado = $request->input('revisado', false);
            $userId = auth()->id();
            
            if ($revisado) {
                PedidoRevisado::firstOrCreate([
                    'pedido_id' => $pedidoId,
                    'user_id' => $userId
                ]);
            } else {
                PedidoRevisado::where('pedido_id', $pedidoId)
                    ->where('user_id', $userId)
                    ->delete();
            }
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Ocultar pedido para el usuario actual
     */
    public function ocultarPedido(Request $request, $pedidoId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            PedidoOculto::firstOrCreate([
                'pedido_id' => $pedidoId,
                'user_id' => $userId
            ]);
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Deshacer ocultamiento de pedido (mostrar nuevamente)
     */
    public function deshacerOcultarPedido(Request $request, $pedidoId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            PedidoOculto::where('pedido_id', $pedidoId)
                ->where('user_id', $userId)
                ->delete();
            
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Ver lista de pedidos ocultos por el usuario actual
     */
    public function ocultosIndex(Request $request)
    {
        try {
            $userId = auth()->id();
            $search = $request->query('search', '');
            $page = $request->query('page', 1);
            $perPage = 20;
            
            // Obtener IDs de pedidos ocultos por el usuario
            $pediodosOcultosIds = PedidoOculto::where('user_id', $userId)
                ->pluck('pedido_id')
                ->toArray();
            
            // Construir la consulta base
            $query = PedidoProduccion::whereIn('id', $pediodosOcultosIds);
            
            // Aplicar búsqueda si existe
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('numero_pedido', 'LIKE', '%' . $search . '%')
                      ->orWhere('cliente', 'LIKE', '%' . $search . '%');
                });
            }
            
            // Paginar
            $pedidosPaginados = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Preparar datos
            $datos = [];
            foreach ($pedidosPaginados->items() as $pedido) {
                $datos[] = [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'asesor' => $pedido->asesor ? $pedido->asesor->name : '—',
                    'fecha_pedido' => $pedido->created_at,
                    'fecha_actualizacion' => $pedido->updated_at,
                    'pedido_revisado' => PedidoRevisado::where('pedido_id', $pedido->id)
                        ->where('user_id', $userId)
                        ->exists(),
                    'tiene_cambios_nuevos' => false,
                    'todos_pendientes' => false,
                    'todos_entregados' => false
                ];
            }
            
            return view('bodega.pedidos-ocultos', [
                'pedidosPorPagina' => $datos,
                'totalPedidos' => $pedidosPaginados->total(),
                'paginaActual' => $pedidosPaginados->currentPage(),
                'porPagina' => $perPage,
                'search' => $search,
                'routeName' => 'gestion-bodega.pedidos-ocultos'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en ocultosIndex: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los pedidos ocultos: ' . $e->getMessage());
        }
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
    private function obtenerPendientesPorArea(Request $request, string $area): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        try {
            // Verificar si la tabla existe
            if (!\Schema::hasTable('bodega_detalles_talla')) {
                \Log::error('La tabla bodega_detalles_talla no existe en la base de datos');
                return back()->with('error', 'La tabla de detalles de talla no está disponible. Contacte al administrador.');
            }
            
            $query = BodegaDetalleTalla::porArea($area)
                ->porEstado('Pendiente')
                ->leftJoin('pedidos_produccion as pp', 'pp.numero_pedido', '=', 'bodega_detalles_talla.numero_pedido');

            // LEFT JOIN a bodega_detalles_visto para ambas áreas (EPP y Costura)
            // Filtrar solo los registros del usuario actual para que cada usuario tenga su propio estado de "visto"
            $query->leftJoin('bodega_detalles_visto as bdv', function($join) {
                $join->on('bdv.bodega_detalle_id', '=', 'bodega_detalles_talla.id')
                     ->where('bdv.user_id', '=', auth()->id());
            });

            // Excluir borradores (donde numero_pedido es NULL o vacío)
            $query->whereNotNull('bodega_detalles_talla.numero_pedido')
                  ->where('bodega_detalles_talla.numero_pedido', '!=', '');

            // Excluir pedidos anulados para ambas áreas
            // IMPORTANTE: excluir values NULL de la subquery para evitar problema de NOT IN (NULL, ...)
            $query->whereNotIn('bodega_detalles_talla.numero_pedido', function($subquery) {
                $subquery->select('numero_pedido')
                    ->from('pedidos_produccion')
                    ->where('estado', 'Anulada')
                    ->whereNotNull('numero_pedido');  // <-- Excluir NULL de la subquery
            });

            // Excluir pedidos entregados del principal para ambas áreas (Costura y EPP)
            $query->whereNotIn('bodega_detalles_talla.numero_pedido', function($subquery) {
                $subquery->select('numero_pedido')
                    ->from('pedidos_produccion')
                    ->where('estado', 'Entregado')
                    ->whereNotNull('numero_pedido');  // <-- Excluir NULL de la subquery
            });

            // Agregar filtro adicional en bodega_detalles_talla para excluir estado_bodega = 'Entregado' y 'Anulado'
            $query->whereNotIn('bodega_detalles_talla.estado_bodega', ['Entregado', 'Anulado']);

            // Agrupar por numero_pedido para evitar duplicación
            $query->select([
                'bodega_detalles_talla.numero_pedido',
                DB::raw('MIN(bodega_detalles_talla.id) as id'),
                DB::raw('MIN(pp.id) as pedido_produccion_id'),
                DB::raw('MIN(bodega_detalles_talla.empresa) as empresa'),
                DB::raw('MIN(bodega_detalles_talla.asesor) as asesor'),
                DB::raw('MIN(bodega_detalles_talla.prenda_nombre) as prenda_nombre'),
                DB::raw('MIN(bodega_detalles_talla.area) as area'),
                DB::raw('MIN(bodega_detalles_talla.estado_bodega) as estado_bodega'),
                DB::raw('MIN(bodega_detalles_talla.fecha_pedido) as fecha_pedido'),
                DB::raw('MIN(bodega_detalles_talla.fecha_entrega) as fecha_entrega'),
                DB::raw('MIN(bodega_detalles_talla.observaciones_bodega) as observaciones_bodega'),
                DB::raw('MIN(bodega_detalles_talla.usuario_bodega_nombre) as usuario_bodega_nombre'),
                DB::raw('MIN(bodega_detalles_talla.created_at) as created_at'),
                DB::raw('MIN(bodega_detalles_talla.updated_at) as updated_at'),
                DB::raw('MAX(bodega_detalles_talla.updated_at) as ultima_actualizacion_at'),
                DB::raw('SUM(bodega_detalles_talla.cantidad) as cantidad_total'),
                DB::raw('SUM(bodega_detalles_talla.pendientes) as pendientes_total'),
                DB::raw('MIN(bodega_detalles_talla.talla) as talla_ejemplo'),
                DB::raw('MAX(CASE WHEN bdv.id IS NOT NULL THEN 1 ELSE 0 END) as visto_exists'),
                DB::raw('MAX(bdv.created_at) as ultimo_visto_at')
            ])
            ->groupBy('bodega_detalles_talla.numero_pedido')
            ->orderBy($area === 'EPP' ? DB::raw('CAST(bodega_detalles_talla.numero_pedido AS UNSIGNED)') : 'bodega_detalles_talla.numero_pedido', 'desc');

            // Aplicar búsqueda general
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search, $area) {
                    $q->where('bodega_detalles_talla.numero_pedido', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.empresa', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.asesor', 'LIKE', "%{$search}%")
                      ->orWhere('bodega_detalles_talla.prenda_nombre', 'LIKE', "%{$search}%");
                    
                    // Para EPP, añadir búsqueda en talla
                    if ($area === 'EPP') {
                        $q->orWhere('bodega_detalles_talla.talla', 'LIKE', "%{$search}%");
                    }
                });
            }

            // Aplicar filtros específicos
            $filtrosAplicados = [];
            
            if ($request->filled('numero_pedido')) {
                $numerosPedido = explode(',', $request->get('numero_pedido'));
                $query->whereIn('bodega_detalles_talla.numero_pedido', $numerosPedido);
                $filtrosAplicados['numero_pedido'] = $numerosPedido;
            }
            
            if ($request->filled('cliente')) {
                $clientes = explode(',', $request->get('cliente'));
                $query->whereIn('bodega_detalles_talla.empresa', $clientes);
                $filtrosAplicados['cliente'] = $clientes;
            }
            
            if ($request->filled('asesor')) {
                $asesores = explode(',', $request->get('asesor'));
                $query->whereIn('bodega_detalles_talla.asesor', $asesores);
                $filtrosAplicados['asesor'] = $asesores;
            }
            
            if ($request->filled('estado')) {
                $estados = explode(',', $request->get('estado'));
                $query->whereIn('bodega_detalles_talla.estado_bodega', $estados);
                $filtrosAplicados['estado'] = $estados;
            }
            
            if ($request->filled('fecha_creacion')) {
                $fechas = explode(',', $request->get('fecha_creacion'));
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $index => $fecha) {
                        $fechaDecodificada = urldecode(trim($fecha));
                        
                        try {
                            $fechaFormateada = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaDecodificada)->format('Y-m-d');
                            
                            if ($index === 0) {
                                $q->whereDate('bodega_detalles_talla.created_at', $fechaFormateada);
                            } else {
                                $q->orWhereDate('bodega_detalles_talla.created_at', $fechaFormateada);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error al procesar fecha '{$fechaDecodificada}': " . $e->getMessage());
                            continue;
                        }
                    }
                });
                
                $filtrosAplicados['fecha_creacion'] = $fechas;
            }
            
            if ($request->filled('fecha_entrega')) {
                $fechas = explode(',', $request->get('fecha_entrega'));
                $query->where(function($q) use ($fechas) {
                    foreach ($fechas as $index => $fecha) {
                        $fechaDecodificada = urldecode(trim($fecha));
                        
                        try {
                            $fechaFormateada = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaDecodificada)->format('Y-m-d');
                            
                            if ($index === 0) {
                                $q->whereDate('bodega_detalles_talla.fecha_entrega', $fechaFormateada);
                            } else {
                                $q->orWhereDate('bodega_detalles_talla.fecha_entrega', $fechaFormateada);
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error al procesar fecha '{$fechaDecodificada}': " . $e->getMessage());
                            continue;
                        }
                    }
                });
                
                $filtrosAplicados['fecha_entrega'] = $fechas;
            }

            // Filtrar por retrasados si se solicita
            if ($request->boolean('retrasados', false)) {
                $query->retrasados();
                $filtrosAplicados['retrasados'] = true;
            }

            // Paginación
            $porPagina = 15;
            $paginaActual = $request->get('page', 1);
            
            // Clonar query para contar ANTES de paginar para evitar duplicación
            $queryParaContar = clone $query;
            $totalPedidos = $queryParaContar->count();

            $pedidosPorPagina = $query->skip(($paginaActual - 1) * $porPagina)
                                        ->take($porPagina)
                                        ->get();

            // Obtener estadísticas solo si es EPP, y solo si la vista las necesita
            // Por ahora diferidas: calcular solo si se solicitan explícitamente
            $estadisticas = [];
            
            // Preparar datos para la vista
            $pedidosFormateados = $pedidosPorPagina->map(function($detalle) use ($area) {
                $datos = [
                    'id' => $detalle->id,
                    'pedido_produccion_id' => $detalle->pedido_produccion_id,
                    'numero_pedido' => $detalle->numero_pedido,
                    'cliente' => $detalle->empresa,
                    'asesor' => is_string($detalle->asesor) ? $detalle->asesor : 
                               (is_array($detalle->asesor) && isset($detalle->asesor['name']) ? $detalle->asesor['name'] : 
                               (is_object($detalle->asesor) && isset($detalle->asesor->name) ? $detalle->asesor->name : 'No especificado')),
                    'estado' => $detalle->estado_bodega,
                    'area' => $detalle->area,
                    'prenda' => $detalle->prenda_nombre,
                    'talla' => $detalle->talla_ejemplo,
                    'cantidad' => $detalle->cantidad_total,
                    'pendientes' => $detalle->pendientes_total,
                    'observaciones' => $detalle->observaciones_bodega,
                    'fecha_pedido' => $detalle->fecha_pedido,
                    'fecha_entrega' => $detalle->fecha_entrega,
                    'usuario_bodega' => $detalle->usuario_bodega_nombre,
                    'created_at' => $detalle->created_at,
                    'updated_at' => $detalle->updated_at,
                    'tiene_pendientes' => $detalle->pendientes_total > 0,
                    'esta_retrasado' => $detalle->fecha_entrega && $detalle->fecha_entrega < now(),
                ];
                
                // Desmarcar automáticamente "visto" si hubo cambios después del último visto (comportamiento tipo correo no leído)
                $tieneVistoHistorico = (bool) $detalle->visto_exists;
                $ultimoVistoAt = $detalle->ultimo_visto_at ? strtotime((string) $detalle->ultimo_visto_at) : null;
                $ultimaActualizacionAt = $detalle->ultima_actualizacion_at ? strtotime((string) $detalle->ultima_actualizacion_at) : null;
                $sigueVisto = $tieneVistoHistorico && $ultimoVistoAt !== null && $ultimaActualizacionAt !== null
                    ? $ultimoVistoAt >= $ultimaActualizacionAt
                    : $tieneVistoHistorico;

                // Agregar 'visto_exists' para ambas áreas (EPP y Costura) - traído desde la query principal, sin N+1
                $datos['visto_exists'] = $sigueVisto;
                
                // Agregar 'visto' solo si es EPP para compatibilidad
                if ($area === 'EPP') {
                    $datos['visto'] = $sigueVisto;
                }
                
                return $datos;
            })->toArray();

            $viewName = $area === 'Costura' ? 'bodega.pendiente-costura' : 'bodega.pendiente-epp';
            
            return view($viewName, [
                'pedidosPorPagina' => $pedidosFormateados,
                'totalPedidos' => $totalPedidos,
                'paginaActual' => $paginaActual,
                'porPagina' => $porPagina,
                'search' => $request->query('search', ''),
                'estadisticas' => $estadisticas,
                'area' => $area,
                'filtros_aplicados' => array_merge([
                    'search' => $request->query('search', ''),
                    'retrasados' => $request->boolean('retrasados', false),
                ], $filtrosAplicados),
                'paginacion_info' => [
                    'pagina_actual' => $paginaActual,
                    'total_paginas' => ceil($totalPedidos / $porPagina),
                    'total' => $totalPedidos,
                    'por_pagina' => $porPagina,
                    'desde' => ($paginaActual - 1) * $porPagina + 1,
                    'hasta' => min($paginaActual * $porPagina, $totalPedidos),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Error en obtenerPendientes{$area}: " . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', "Error al cargar los pedidos de {$area}: " . $e->getMessage());
        }
    }

    /**
     * Mostrar pedidos pendientes de Costura
     */
    public function pendienteCostura(Request $request)
    {
        return $this->obtenerPendientesPorArea($request, 'Costura');
    }

    /**
     * Mostrar pedidos pendientes de EPP
     */
    public function pendienteEpp(Request $request)
    {
        return $this->obtenerPendientesPorArea($request, 'EPP');
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
                ->whereNotNull('bodega_detalles_talla.numero_pedido')
                ->where('bodega_detalles_talla.numero_pedido', '!=', '')
                ->leftJoin('pedidos_produccion', 'bodega_detalles_talla.numero_pedido', '=', 'pedidos_produccion.numero_pedido')
                ->select('bodega_detalles_talla.*', 'pedidos_produccion.created_at')
                ->orderBy('bodega_detalles_talla.fecha_entrega', 'asc');

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
                ->whereNotNull('bodega_detalles_talla.numero_pedido')
                ->where('bodega_detalles_talla.numero_pedido', '!=', '')
                ->leftJoin('pedidos_produccion', 'bodega_detalles_talla.numero_pedido', '=', 'pedidos_produccion.numero_pedido')
                ->select('bodega_detalles_talla.*', 'pedidos_produccion.created_at')
                ->orderBy('bodega_detalles_talla.fecha_entrega', 'asc');

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
                $fechaPedido = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : '—';
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
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $result = $this->updateService->actualizarObservaciones(
                $validated['id'],
                $validated['observaciones'] ?? null
            );
            
            $statusCode = $result['success'] ? 200 : 400;
            
            return response()->json($result, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Actualizar fecha de entrega
     */
    public function actualizarFecha(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'fecha_entrega' => 'required|date',
            ]);

            $result = $this->updateService->actualizarFecha(
                $validated['id'],
                $validated['fecha_entrega']
            );
            
            $statusCode = $result['success'] ? 200 : 400;
            
            return response()->json($result, $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Obtener datos de factura para modal - Usa la misma vista que Despacho
     */
    public function obtenerDatosFacturaJSON($id)
    {
        try {
            $resultado = $this->bodegaPedidoService->obtenerDatosFactura($id);
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFacturaJSON: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos'
            ], 500);
        }
    }

    /**
     * Obtener datos de factura
     */
    public function obtenerDatosFactura($id)
    {
        try {
            $resultado = $this->bodegaPedidoService->obtenerDatosFactura($id);
            
            return response()->json($resultado);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFactura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 400);
        }
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
                'pendientes' => 'nullable|string',
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
                'detalles.*.pendientes' => 'nullable|string',
                'detalles.*.observaciones_bodega' => 'nullable|string',
                'detalles.*.fecha_pedido' => 'nullable|date',
                'detalles.*.fecha_entrega' => 'nullable|date',
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

            $camposAuditar = ['asesor', 'empresa', 'cantidad', 'prenda_nombre', 'pendientes', 'observaciones_bodega', 'fecha_entrega', 'area', 'estado_bodega'];
            
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
                'pendientes' => 'nullable|string',
                'fecha_pedido' => 'nullable|date',
                'fecha_entrega' => 'nullable|date',
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
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->guardarNota($validated, $request);
            
        } catch (\Exception $e) {
            \Log::error('Error en guardarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de notas para un pedido y talla
     */
    public function obtenerNotas(Request $request, $numero_pedido = null, $talla = null): JsonResponse
    {
        try {
            // Si vienen como parámetros de ruta (GET), usarlos directamente
            if ($numero_pedido && $talla) {
                $validated = [
                    'numero_pedido' => $numero_pedido,
                    'talla' => $talla,
                ];
            } else {
                // Si vienen en el body (POST), validarlos
                $validated = $request->validate([
                    'numero_pedido' => 'required|string',
                    'talla' => 'required|string',
                    'talla_color_id' => 'nullable|integer',
                ]);
            }

            return $this->notaService->obtenerNotas($validated);
            
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    /**
     * Actualizar una nota existente
     */
    public function actualizarNota(Request $request, $notaId): JsonResponse
    {
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->actualizarNota($notaId, $validated);
            
        } catch (\Exception $e) {
            \Log::error('Error en actualizarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    /**
     * Eliminar una nota
     */
    public function eliminarNota(Request $request, $notaId): JsonResponse
    {
        // Validar que el usuario no sea de solo lectura
        $readOnlyError = $this->validateNotReadOnly();
        if ($readOnlyError) {
            return $readOnlyError;
        }

        try {
            return $this->notaService->eliminarNota($notaId);
            
        } catch (\Exception $e) {
            \Log::error('Error en eliminarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }

    /**
     * Obtener datos para filtros dinámicos
     */
    public function obtenerDatosFiltro(Request $request, string $tipo): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $perPage = 20;

            \Log::info('obtenerDatosFiltro iniciado', [
                'tipo' => $tipo,
                'page' => $page,
                'search' => $search,
                'path' => request()->path(),
                'url' => request()->fullUrl()
            ]);

            // Para el módulo de Costura, usar métodos específicos
            if (str_contains(request()->path(), 'pendiente-costura') || str_contains(request()->header('referer'), 'pendiente-costura')) {
                \Log::info('Usando métodos específicos para Costura');
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->filtroService->obtenerNumerosPedidoCostura($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->filtroService->obtenerClientesCostura($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->filtroService->obtenerAsesoresCostura($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->filtroService->obtenerEstadosCostura($search, $page, $perPage);
                        break;
                    case 'fecha_creacion':
                        $datos = $this->filtroService->obtenerFechasCreacionCostura($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->filtroService->obtenerFechasCostura($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            } elseif (str_contains(request()->path(), 'pendiente-epp') || str_contains(request()->header('referer'), 'pendiente-epp')) {
                \Log::info('Usando métodos específicos para EPP');
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->filtroService->obtenerNumerosPedidoEpp($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->filtroService->obtenerClientesEpp($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->filtroService->obtenerAsesoresEpp($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->filtroService->obtenerEstadosEpp($search, $page, $perPage);
                        break;
                    case 'fecha_creacion':
                        $datos = $this->filtroService->obtenerFechasCreacionEpp($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->filtroService->obtenerFechasEpp($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            } else {
                \Log::info('Usando métodos generales para bodega principal');
                switch($tipo) {
                    case 'numero_pedido':
                        $datos = $this->filtroService->obtenerNumerosPedidoCostura($search, $page, $perPage);
                        break;
                    case 'cliente':
                        $datos = $this->filtroService->obtenerClientesCostura($search, $page, $perPage);
                        break;
                    case 'asesor':
                        $datos = $this->filtroService->obtenerAsesoresCostura($search, $page, $perPage);
                        break;
                    case 'estado':
                        $datos = $this->filtroService->obtenerEstadosCostura($search, $page, $perPage);
                        break;
                    case 'fecha_creacion':
                        $datos = $this->filtroService->obtenerFechasCreacionCostura($search, $page, $perPage);
                        break;
                    case 'fecha':
                    case 'fecha_entrega':
                        $datos = $this->filtroService->obtenerFechasCostura($search, $page, $perPage);
                        break;
                    default:
                        \Log::warning('Tipo de filtro no reconocido: ' . $tipo);
                        $datos = collect();
                        break;
                }
            }

            \Log::info('Datos obtenidos', [
                'total' => $datos->count(),
                'tipo' => $tipo
            ]);

            $total = $datos->count();
            $paginated = $datos->forPage($page, $perPage);
            
            $pagination = [
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total)
            ];

            return response()->json([
                'success' => true,
                'datos' => $paginated->values()->all(),
                'paginacion' => $pagination
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosFiltro: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('Request info: ' . json_encode([
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'tipo' => $tipo,
                'headers' => request()->headers->all()
            ]));
            
            return response()->json([
                'success' => false,
                'error' => 'Error al cargar datos del filtro: ' . $e->getMessage()
            ], 500);
        }
    }





    /**
     * Actualizar fecha de entrega a despacho
     */
    public function actualizarFechaEntregaDespacho(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fecha_entrega_despacho' => 'required|date_format:Y-m-d',
            ]);

            $result = $this->updateService->actualizarFechaEntregaDespacho(
                $id,
                $validated['fecha_entrega_despacho']
            );
            
            $statusCode = $result['success'] ? 200 : 500;
            
            return response()->json($result, $statusCode);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar fecha de entrega a despacho: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la fecha'
            ], 500);
        }
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
        try {
            $eppNuevo = \App\Models\PedidoEpp::with('epp')->find($eppId);
            
            if (!$eppNuevo) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado'
                ], 404);
            }

            // Obtener el EPP anterior (homologado_de)
            $eppAnterior = null;
            if ($eppNuevo->homologado_de) {
                $eppAnterior = \App\Models\PedidoEpp::withTrashed()
                    ->with('epp')
                    ->find($eppNuevo->homologado_de);
            }

            if (!$eppAnterior) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP anterior no encontrado. Este EPP no fue homologado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'epp_anterior' => [
                        'id' => $eppAnterior->id,
                        'nombre' => $eppAnterior->epp->nombre_completo ?? $eppAnterior->epp->nombre ?? 'EPP Sin nombre',
                        'cantidad' => $eppAnterior->cantidad,
                        'observaciones' => $eppAnterior->observaciones,
                        'deleted_at' => $eppAnterior->deleted_at,
                    ],
                    'epp_nuevo' => [
                        'id' => $eppNuevo->id,
                        'nombre' => $eppNuevo->epp->nombre_completo ?? $eppNuevo->epp->nombre ?? 'EPP Sin nombre',
                        'cantidad' => $eppNuevo->cantidad,
                        'observaciones' => $eppNuevo->observaciones,
                        'created_at' => $eppNuevo->created_at,
                    ],
                    'cambios' => [
                        'epp_cambio' => $eppAnterior->epp->nombre_completo !== $eppNuevo->epp->nombre_completo,
                        'cantidad_cambio' => $eppAnterior->cantidad !== $eppNuevo->cantidad,
                        'observaciones_cambio' => $eppAnterior->observaciones !== $eppNuevo->observaciones,
                        'cantidad_anterior' => $eppAnterior->cantidad,
                        'cantidad_nueva' => $eppNuevo->cantidad,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosHomologacion: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de homologación'
            ], 500);
        }
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

}
