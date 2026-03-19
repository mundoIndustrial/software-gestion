<?php

namespace App\Infrastructure\Http\Controllers\Asesores;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AsesoresInventarioTelasController;
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaSimpleUseCase;
use App\Application\Pedidos\UseCases\ObtenerProximoNumeroPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\Pedidos\UseCases\ObtenerRecibosUseCase;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ConfirmarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\PrepararCreacionProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\DTOs\ObtenerProximoNumeroPedidoDTO;
use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\DTOs\ObtenerRecibosDTO;
use App\Application\Pedidos\UseCases\ObtenerEstadisticasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerDatosGraficasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerPerfilAsesorUseCase;
use App\Application\Pedidos\UseCases\ActualizarPerfilAsesorUseCase;
use App\Application\Pedidos\DTOs\ObtenerEstadisticasDashboardDTO;
use App\Application\Pedidos\DTOs\ObtenerDatosGraficasDashboardDTO;
use App\Application\Pedidos\DTOs\ObtenerNotificacionesDTO;
use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\DTOs\ActualizarPerfilAsesorDTO;
use App\Application\Bodega\Services\BodegaPedidoService;
use Illuminate\Routing\Controller;

class AsesoresController extends Controller
{
    protected PedidoProduccionRepository $pedidoProduccionRepository;
    protected DashboardService $dashboardService;
    protected NotificacionesService $notificacionesService;
    protected PerfilService $perfilService;
    protected CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase;
    protected ConfirmarProduccionPedidoUseCase $confirmarProduccionPedidoUseCase;
    protected ActualizarProduccionPedidoUseCase $actualizarProduccionPedidoUseCase;
    protected AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase;
    protected ObtenerProduccionPedidoUseCase $obtenerProduccionPedidoUseCase;
    protected ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase;
    protected PrepararCreacionProduccionPedidoUseCase $prepararCreacionProduccionPedidoUseCase;
    protected AgregarPrendaSimpleUseCase $agregarPrendaSimpleUseCase;
    protected ObtenerProximoNumeroPedidoUseCase $obtenerProximoNumeroPedidoUseCase;
    protected ObtenerFacturaUseCase $obtenerFacturaUseCase;
    protected ObtenerRecibosUseCase $obtenerRecibosUseCase;
    protected ObtenerEstadisticasDashboardUseCase $obtenerEstadisticasDashboardUseCase;
    protected ObtenerDatosGraficasDashboardUseCase $obtenerDatosGraficasDashboardUseCase;
    protected ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase;
    protected MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase;
    protected ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase;
    protected ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase;
    protected BodegaPedidoService $bodegaPedidoService;

    public function __construct(
        PedidoProduccionRepository $pedidoProduccionRepository,
        DashboardService $dashboardService,
        NotificacionesService $notificacionesService,
        PerfilService $perfilService,
        CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase,
        ConfirmarProduccionPedidoUseCase $confirmarProduccionPedidoUseCase,
        ActualizarProduccionPedidoUseCase $actualizarProduccionPedidoUseCase,
        AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase,
        ObtenerProduccionPedidoUseCase $obtenerProduccionPedidoUseCase,
        ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase,
        PrepararCreacionProduccionPedidoUseCase $prepararCreacionProduccionPedidoUseCase,
        AgregarPrendaSimpleUseCase $agregarPrendaSimpleUseCase,
        ObtenerProximoNumeroPedidoUseCase $obtenerProximoNumeroPedidoUseCase,
        ObtenerFacturaUseCase $obtenerFacturaUseCase,
        ObtenerRecibosUseCase $obtenerRecibosUseCase,
        ObtenerEstadisticasDashboardUseCase $obtenerEstadisticasDashboardUseCase,
        ObtenerDatosGraficasDashboardUseCase $obtenerDatosGraficasDashboardUseCase,
        ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase,
        MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase,
        ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase,
        ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase,
        BodegaPedidoService $bodegaPedidoService
    ) {
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->dashboardService = $dashboardService;
        $this->notificacionesService = $notificacionesService;
        $this->perfilService = $perfilService;
        $this->crearProduccionPedidoUseCase = $crearProduccionPedidoUseCase;
        $this->confirmarProduccionPedidoUseCase = $confirmarProduccionPedidoUseCase;
        $this->actualizarProduccionPedidoUseCase = $actualizarProduccionPedidoUseCase;
        $this->anularProduccionPedidoUseCase = $anularProduccionPedidoUseCase;
        $this->obtenerProduccionPedidoUseCase = $obtenerProduccionPedidoUseCase;
        $this->listarProduccionPedidosUseCase = $listarProduccionPedidosUseCase;
        $this->prepararCreacionProduccionPedidoUseCase = $prepararCreacionProduccionPedidoUseCase;
        $this->agregarPrendaSimpleUseCase = $agregarPrendaSimpleUseCase;
        $this->obtenerProximoNumeroPedidoUseCase = $obtenerProximoNumeroPedidoUseCase;
        $this->obtenerFacturaUseCase = $obtenerFacturaUseCase;
        $this->obtenerRecibosUseCase = $obtenerRecibosUseCase;
        $this->obtenerEstadisticasDashboardUseCase = $obtenerEstadisticasDashboardUseCase;
        $this->obtenerDatosGraficasDashboardUseCase = $obtenerDatosGraficasDashboardUseCase;
        $this->obtenerNotificacionesUseCase = $obtenerNotificacionesUseCase;
        $this->marcarNotificacionLeidaUseCase = $marcarNotificacionLeidaUseCase;
        $this->obtenerPerfilAsesorUseCase = $obtenerPerfilAsesorUseCase;
        $this->actualizarPerfilAsesorUseCase = $actualizarPerfilAsesorUseCase;
        $this->bodegaPedidoService = $bodegaPedidoService;
    }

    /**
     * Mostrar el perfil del asesor
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerPerfilAsesorDTO::crear();

            // Usar el nuevo Use Case DDD
            $user = $this->obtenerPerfilAsesorUseCase->ejecutar($dto);

            return view('asesores.profile', compact('user'));

        } catch (\Exception $e) {
            abort(500, 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar el dashboard de asesores - DELEGADO A USE CASE
     */
    public function dashboard()
    {
        $dto = ObtenerEstadisticasDashboardDTO::crear();
        $stats = $this->obtenerEstadisticasDashboardUseCase->ejecutar($dto);
        return view('asesores.dashboard', compact('stats'));
    }

    /**
     * Obtener datos para gráficas del dashboard - DELEGADO A USE CASE
     */
    public function getDashboardData(Request $request)
    {
        $dias = (int) $request->get('tipo', 30);
        $dto = ObtenerDatosGraficasDashboardDTO::fromRequest($dias);
        $datos = $this->obtenerDatosGraficasDashboardUseCase->ejecutar($dto);
        return response()->json($datos);
    }

    /**
     * Listar pedidos del asesor - DELEGADO A SERVICIO
     */
    public function index(Request $request)
    {
        try {
            $tipo = $request->query('tipo');
            $filtros = [];
            
            if ($request->filled('estado')) {
                $filtros['estado'] = $request->estado;
            }
            
            if ($request->filled('search')) {
                $filtros['search'] = $request->search;
            }

            // Crear DTO para el Use Case
            $dto = ListarProduccionPedidosDTO::fromRequest($tipo, $filtros);

            // Usar el nuevo Use Case DDD
            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);
            $estados = $tipo !== 'logo' ? $this->listarProduccionPedidosUseCase->obtenerEstados() : [];

            return view('asesores.pedidos.index', compact('pedidos', 'estados'));

        } catch (\Exception $e) {
            \Log::error('Error al listar pedidos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al listar pedidos');
        }
    }

    /**
     * Mostrar vista de pedidos pendientes del asesor logueado
     */
    public function pendientes(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->query('search', '');
            $tipo = $request->query('tipo', 'todos'); // 'costura', 'epp', 'todos'
            
            return view('asesores.pedidos.pendientes', [
                'search' => $search,
                'tipo' => $tipo,
                'userName' => $user->name ?? 'Usuario'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al mostrar pendientes: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar pedidos pendientes');
        }
    }

    /**
     * Mostrar detalle de pendientes de un pedido específico
     */
    public function pendientesDetalle($id)
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';
            
            // Obtener el pedido
            $pedido = PedidoProduccion::findOrFail($id);
            
            // Obtener detalles completos usando el servicio de bodega
            $datosCompletos = $this->bodegaPedidoService->obtenerDetallePedido($id);
            
            // Filtrar solo los ítems con estado Pendiente
            $itemsPendientes = collect($datosCompletos['items'])->filter(function($item) {
                return ($item['estado_bodega'] ?? '') === 'Pendiente';
            })->values()->all();
            
            return view('asesores.pedidos.pendientes-detalle', [
                'pedido' => $pedido,
                'detalles' => $itemsPendientes
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al mostrar detalle de pendientes: ' . $e->getMessage());
            return redirect()->route('asesores.pendientes')->with('error', 'Error al cargar el detalle');
        }
    }

    /**
     * Obtener todas las notas de un pedido
     */
    public function obtenerNotasPedido($id)
    {
        try {
            $pedido = PedidoProduccion::findOrFail($id);
            
            $notas = DB::table('bodega_notas')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $notas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener notas del pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las notas'
            ], 500);
        }
    }

    /**
     * Contar pedidos pendientes del asesor
     */
    public function contarPendientesAsesor()
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';
            
            // Contar pedidos únicos pendientes del asesor
            $conteo = DB::table('bodega_detalles_talla')
                ->select('numero_pedido')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '')
                ->where('estado_bodega', 'Pendiente')
                ->where('asesor', 'like', "%{$asesorNombre}%")
                ->whereNull('deleted_at')
                ->distinct()
                ->count('numero_pedido');
            
            return response()->json([
                'success' => true,
                'conteo' => $conteo
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al contar pendientes del asesor: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'conteo' => 0
            ], 500);
        }
    }

    /**
     * API para obtener pendientes del asesor logueado
     */
    public function obtenerPendientesAsesor(Request $request)
    {
        try {
            $user = Auth::user();
            $asesorNombre = $user->name ?? '';
            
            $search = $request->query('search', '');
            $tipo = $request->query('tipo', 'todos');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 20);
            
            \Log::info('[ASESOR] Obteniendo pendientes para asesor: ' . $asesorNombre, [
                'search' => $search,
                'tipo' => $tipo
            ]);
            
            // Consultar bodega_detalles_talla filtrado por asesor y estado Pendiente
            $query = DB::table('bodega_detalles_talla as bdt')
                ->leftJoin('pedidos_produccion as pp', 'bdt.pedido_produccion_id', '=', 'pp.id')
                ->select('bdt.*', 'pp.created_at as pedido_fecha_creacion')
                ->whereNotNull('bdt.numero_pedido')
                ->where('bdt.numero_pedido', '!=', '')
                ->where('bdt.estado_bodega', 'Pendiente')
                ->whereNull('bdt.deleted_at');
            
            // Filtrar por asesor
            if ($asesorNombre) {
                $query->where('bdt.asesor', 'like', "%{$asesorNombre}%");
            }
            
            // Búsqueda
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('bdt.numero_pedido', 'like', "%{$search}%")
                      ->orWhere('bdt.empresa', 'like', "%{$search}%")
                      ->orWhere('bdt.prenda_nombre', 'like', "%{$search}%");
                });
            }
            
            // Obtener todos los datos
            $detalles = $query->orderBy('pp.created_at', 'desc')->get();
            
            // Agrupar por número de pedido
            $pedidosAgrupados = $detalles->groupBy('numero_pedido')->map(function ($items, $numeroPedido) {
                $primerItem = $items->first();
                $totalItems = $items->count();
                $totalCantidad = $items->sum(function($item) {
                    return is_numeric($item->cantidad) ? (int)$item->cantidad : 0;
                });
                
                $areas = $items->pluck('area')->unique()->filter()->values()->toArray();
                $tipoDisplay = implode(' + ', $areas);
                
                return [
                    'id' => $primerItem->pedido_produccion_id ?? 0,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerItem->empresa ?? 'Sin Empresa',
                    'asesor' => $primerItem->asesor ?? '',
                    'estado' => $primerItem->estado_bodega ?? 'Pendiente',
                    'fecha_creacion' => $primerItem->pedido_fecha_creacion ? \Carbon\Carbon::parse($primerItem->pedido_fecha_creacion)->format('d/m/Y') : '-',
                    'fecha_entrega' => $primerItem->fecha_entrega ? \Carbon\Carbon::parse($primerItem->fecha_entrega)->format('d/m/Y') : '',
                    'tipo' => $tipoDisplay,
                    'total_items' => $totalItems,
                    'total_pendientes' => $totalItems,
                    'total_cantidad' => $totalCantidad,
                    'areas' => $areas,
                    'detalles' => $items->map(function($item) {
                        return [
                            'prenda' => $item->prenda_nombre,
                            'talla' => $item->talla,
                            'cantidad' => $item->cantidad,
                            'pendientes' => $item->pendientes,
                            'area' => $item->area,
                            'estado_costura' => $item->costura_estado,
                            'estado_epp' => $item->epp_estado,
                            'observaciones' => $item->observaciones_bodega
                        ];
                    })->toArray()
                ];
            })->values();
            
            // Filtrar por tipo de área después de agrupar
            if ($tipo === 'costura') {
                $pedidosAgrupados = $pedidosAgrupados->filter(function($pedido) {
                    // Solo mostrar si tiene al menos un item con area='Costura' Y costura_estado='Pendiente'
                    return collect($pedido['detalles'])->some(function($detalle) {
                        return $detalle['area'] === 'Costura' && $detalle['estado_costura'] === 'Pendiente';
                    });
                })->values();
            } elseif ($tipo === 'epp') {
                $pedidosAgrupados = $pedidosAgrupados->filter(function($pedido) {
                    // Solo mostrar si tiene al menos un item con area='EPP' Y epp_estado='Pendiente'
                    return collect($pedido['detalles'])->some(function($detalle) {
                        return $detalle['area'] === 'EPP' && $detalle['estado_epp'] === 'Pendiente';
                    });
                })->values();
            }
            
            // Paginación manual
            $total = $pedidosAgrupados->count();
            $pedidosPaginados = $pedidosAgrupados->forPage($page, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $pedidosPaginados->values(),
                'meta' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('[ASESOR] Error al obtener pendientes: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para crear pedido (versión amigable) - DELEGADO A USE CASE
     */
    public function create(Request $request)
    {
        try {
            $editarId = $request->query('editar');
            $allowEditarCotizacionCreada = $request->boolean('editar_cotizacion');

            $tipoQuery = $request->query('tipo');
            if ($tipoQuery === 'PARA_CLIENTE') {
                $tipo = $tipoQuery;
                return view('asesores.cotizaciones.epp.create', compact('tipo'));
            }
            
            // Crear DTO para el Use Case
            $dto = PrepararCreacionProduccionPedidoDTO::fromRequest(
                tipo: $request->query('tipo', 'PB'),
                editarId: $editarId,
                usuarioId: \Auth::id(),
                allowEditarCotizacionCreada: $allowEditarCotizacionCreada
            );

            // Usar el nuevo Use Case DDD
            $datos = $this->prepararCreacionProduccionPedidoUseCase->ejecutar($dto);
            
            $tipo = $datos['tipo'];
            $esEdicion = $datos['esEdicion'];
            $cotizacion = $datos['cotizacion'];
            
            // Redirigir según el tipo
            if ($tipo === 'B') {
                return redirect()->route('asesores.cotizaciones-bordado.create');
            }
            
            if ($tipo === 'PL') {
                return redirect()->route('asesores.cotizaciones-prenda.create');
            }
            
            return view('asesores.pedidos.create-friendly', compact('tipo', 'esEdicion', 'cotizacion'));

        } catch (\Exception $e) {
            \Log::error('Error al preparar formulario de creación: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function editCotizacion(int $id, Request $request)
    {
        try {
            $tipoQuery = $request->query('tipo');
            if ($tipoQuery !== 'PARA_CLIENTE') {
                $cotizacion = \App\Models\Cotizacion::findOrFail($id);
                $codigoTipo = $cotizacion->tipoCotizacion?->codigo;
                if (is_string($codigoTipo) && strtoupper(trim($codigoTipo)) === 'EPP') {
                    return redirect("/asesores/cotizaciones/{$id}/edit?tipo=PARA_CLIENTE");
                }

                abort(404);
            }

            $cotizacion = \App\Models\Cotizacion::findOrFail($id);
            if ($cotizacion->asesor_id !== \Auth::id()) {
                abort(403, 'No tienes permiso para editar esta cotización');
            }

            $eppCot = \DB::table('epp_cotizacion')->where('cotizacion_id', $cotizacion->id)->first();
            $items = \DB::table('epp_items_cot')->where('cotizacion_id', $cotizacion->id)->orderBy('id')->get();
            $valores = \DB::table('epp_valor_unitario')
                ->whereIn('epp_item_id', $items->pluck('id')->all())
                ->get()
                ->keyBy('epp_item_id');
            $imagenes = \DB::table('epp_img_cot')
                ->whereIn('epp_item_id', $items->pluck('id')->all())
                ->orderBy('id')
                ->get()
                ->groupBy('epp_item_id');

            $itemsUi = $items->map(function ($it) use ($valores, $imagenes) {
                $vu = $valores[$it->id] ?? null;
                $imgs = $imagenes->get($it->id, collect());

                return [
                    'tipo' => 'epp',
                    'id' => (int)$it->id,
                    'nombre' => $it->nombre,
                    'nombre_epp' => $it->nombre,
                    'cantidad' => (int)($it->cantidad ?? 1),
                    'observaciones' => $it->observaciones,
                    'valor_unitario' => $vu ? $vu->valor_unitario : null,
                    'total' => ($vu && $it->cantidad) ? ((float)$vu->valor_unitario * (int)$it->cantidad) : null,
                    'imagenes' => $imgs->map(function ($row) {
                        if (!$row || !$row->ruta) return null;
                        return \Storage::disk('public')->url($row->ruta);
                    })->filter()->values()->all(),
                ];
            })->values()->all();
            
            // Obtener también prendas de la cotización
            $prendas = \DB::table('prenda_items_cot')->where('cotizacion_id', $cotizacion->id)->orderBy('id')->get();
            $valoresPrendas = \DB::table('prenda_valor_unitario')
                ->whereIn('prenda_item_id', $prendas->pluck('id')->all())
                ->get()
                ->keyBy('prenda_item_id');
            $imagenesPrendas = \DB::table('prenda_img_cot')
                ->whereIn('prenda_item_id', $prendas->pluck('id')->all())
                ->orderBy('id')
                ->get()
                ->groupBy('prenda_item_id');

            $prendasUi = $prendas->map(function ($prenda) use ($valoresPrendas, $imagenesPrendas) {
                $vu = $valoresPrendas[$prenda->id] ?? null;
                $imgs = $imagenesPrendas->get($prenda->id, collect());

                return [
                    'tipo' => 'prenda',
                    'id' => (int)$prenda->id,
                    'nombre' => $prenda->descripcion,
                    'nombre_epp' => $prenda->descripcion,
                    'cantidad' => (int)($prenda->cantidad ?? 1),
                    'observaciones' => $prenda->observaciones,
                    'valor_unitario' => $vu ? $vu->valor_unitario : null,
                    'total' => ($vu && $prenda->cantidad) ? ((float)$vu->valor_unitario * (int)$prenda->cantidad) : null,
                    'imagenes' => $imgs->map(function ($row) {
                        if (!$row || !$row->ruta) return null;
                        return \Storage::disk('public')->url($row->ruta);
                    })->filter()->values()->all(),
                ];
            })->values()->all();
            
            // Combinar EPPs y prendas en un solo array
            $itemsUi = array_merge($itemsUi, $prendasUi);

            // Obtener IVA directamente desde el campo de la cotización
            $iva = $cotizacion->iva ?? null;
            
            // Extraer datos adicionales desde especificaciones (JSON)
            $especificaciones = [];
            if ($cotizacion->especificaciones) {
                $decoded = json_decode($cotizacion->especificaciones, true);
                if (is_array($decoded)) {
                    $especificaciones = $decoded;
                }
            }
            
            // Extraer información adicional para el formulario
            $condicionesPago = $especificaciones['condiciones_pago'] ?? '';
            $tiempoEntrega = $especificaciones['tiempo_entrega'] ?? '';
            $cuentasAutorizadas = $especificaciones['cuentas_autorizadas'] ?? '';
            
            // Logging para depurar datos del cliente
            \Log::info('AsesoresController.editCotizacion: Datos del cliente', [
                'cotizacion_id' => $cotizacion->id,
                'cliente_nit' => $cotizacion->cliente_nit,
                'cliente_direccion' => $cotizacion->cliente_direccion,
                'cliente_telefono' => $cotizacion->cliente_telefono,
                'condiciones_pago' => $condicionesPago,
                'tiempo_entrega' => $tiempoEntrega,
                'cuentas_autorizadas' => $cuentasAutorizadas,
                'iva' => $iva
            ]);

            $tipo = 'PARA_CLIENTE';
            return view('asesores.cotizaciones.epp.create', compact('tipo', 'cotizacion', 'eppCot', 'itemsUi', 'iva', 'condicionesPago', 'tiempoEntrega', 'cuentasAutorizadas'));
        } catch (\Exception $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                throw $e;
            }

            \Log::error('[AsesoresController.editCotizacion] Error', ['error' => $e->getMessage()]);
            abort(500, $e->getMessage());
        }
    }

    /**
     * Guardar nuevo pedido - DELEGADO A SERVICIOS
     */
    public function store(Request $request)
    {
        $productosKey = $request->has('productos') ? 'productos' : 'productos_friendly';
        
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            $productosKey.'.*.descripcion' => 'nullable|string',
            $productosKey.'.*.tella' => 'nullable|string',
            $productosKey.'.*.tipo_manga' => 'nullable|string',
            $productosKey.'.*.color' => 'nullable|string',
            $productosKey.'.*.talla' => 'nullable|string',
            $productosKey.'.*.genero' => 'nullable|string',
            $productosKey.'.*.cantidad' => 'required|integer|min:1',
            $productosKey.'.*.ref_hilo' => 'nullable|string',
            $productosKey.'.*.precio_unitario' => 'nullable|numeric|min:0',
            $productosKey.'.*.telas' => 'nullable|array',
            $productosKey.'.*.telas.*.tela_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.color_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.referencia' => 'nullable|string',
            'logo.descripcion' => 'nullable|string',
            'logo.observaciones_tecnicas' => 'nullable|string',
            'logo.tecnicas' => 'nullable|string',
            'logo.ubicaciones' => 'nullable|string',
            'logo.observaciones_generales' => 'nullable|string',
            'logo.imagenes' => 'nullable|array',
            'logo.imagenes.*' => 'nullable|file|image|max:5242880',
            'tipo_cotizacion' => 'nullable|string',
            'cotizacion_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $tipoCotizacion = $request->input('tipo_cotizacion');
            $cotizacionId = $request->input('cotizacion_id');
            
            if ($this->guardarPedidoLogoService->esLogoPedido($tipoCotizacion, $cotizacionId)) {
                $imagenesProcesadas = $this->procesarFotosTelasService->procesarImagenesLogo($request);
                $logoPedidoId = $this->guardarPedidoLogoService->guardar($validated, $imagenesProcesadas);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de logo guardado correctamente',
                    'logo_pedido_id' => $logoPedidoId,
                    'tipo' => 'logo'
                ]);
            }

            $productosConFotos = $this->procesarFotosTelasService->procesar($request, $validated[$productosKey]);
            
            // Crear DTO para el Use Case
            $dto = new CrearProduccionPedidoDTO(
                $validated['cliente'],
                $validated['cliente'],
                $productosConFotos
            );
            
            // Usar el nuevo Use Case DDD
            $pedido = $this->crearProduccionPedidoUseCase->ejecutar($dto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $pedido->getId()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al guardar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirmar pedido y asignar ID - DELEGADO A USE CASE
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        try {
            // Crear DTO para el Use Case
            $dto = new ConfirmarProduccionPedidoDTO(
                (string)$validated['borrador_id']
            );

            // Usar el nuevo Use Case DDD
            $pedido = $this->confirmarProduccionPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente con ID: ' . $validated['numero_pedido'],
                'pedido' => $validated['numero_pedido']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Mostrar un pedido específico - DELEGADO A SERVICIO
     */
    public function show($id)
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $pedidoData = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);

            // DEBUG: Verificar telas_array en show()
            \Log::warning('╔═══ [SHOW - ANTES DE VISTA] ═══╗', [
                'pedidoData_keys' => is_array($pedidoData) ? array_keys($pedidoData) : 'not_array',
                'tiene_pedido_key' => isset($pedidoData['pedido']),
            ]);
            
            $datosEnriquecidos = isset($pedidoData['pedido']) ? $pedidoData['pedido'] : $pedidoData;
            
            if (isset($datosEnriquecidos['prendas']) && count($datosEnriquecidos['prendas']) > 0) {
                $prenda0 = $datosEnriquecidos['prendas'][0];
                \Log::warning('╚═══ SHOW - PRENDA 0 FINAL ═══╝', [
                    'prenda_keys' => array_keys($prenda0),
                    'tiene_telas_array' => isset($prenda0['telas_array']),
                    'cantidad_telas_array' => isset($prenda0['telas_array']) ? count($prenda0['telas_array']) : 0,
                    'telas_array' => $prenda0['telas_array'] ?? 'NULL'
                ]);
            }

            return view('asesores.pedidos.plantilla-erp-antigua', compact('pedidoData'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición - DELEGADO A SERVICIO
     */
    public function edit($id)
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $respuesta = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);
            
            // Extraer el modelo del pedido envuelto por UseCase
            $datosEnriquecidos = isset($respuesta['pedido']) ? $respuesta['pedido'] : $respuesta;
            
            // Para obtener el modelo Eloquent real
            $pedidoModel = \App\Models\PedidoProduccion::find($id);
            
            // DEBUG: Verificar que telas_array está en los datos JUSTO ANTES de enviar a vista
            \Log::warning('╔═══ [ANTES DE ENVIAR A VISTA] ═══╗', [
                'respuesta_keys' => array_keys($respuesta),
                'tiene_pedido_key' => isset($respuesta['pedido']),
                'prendas_count' => isset($datosEnriquecidos['prendas']) ? count($datosEnriquecidos['prendas']) : 0
            ]);
            
            if (isset($datosEnriquecidos['prendas']) && count($datosEnriquecidos['prendas']) > 0) {
                $prenda0 = $datosEnriquecidos['prendas'][0];
                \Log::warning('╚═══ PRENDA 0 FINAL ═══╝', [
                    'prenda_keys' => array_keys($prenda0),
                    'tiene_telas_array' => isset($prenda0['telas_array']),
                    'cantidad_telas_array' => isset($prenda0['telas_array']) ? count($prenda0['telas_array']) : 0,
                    'telas_array' => $prenda0['telas_array'] ?? 'NULL'
                ]);
            }

            return view('asesores.pedidos.editar-pedido', [
                'pedido' => $pedidoModel,
                'pedidoData' => $respuesta,  // Envía la estructura completa con 'pedido' adentro
            ]);
        } catch (\Exception $e) {
            \Log::error('[AsesoresController.edit] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Actualizar pedido - DELEGADO A USE CASE
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string|in:Pendiente,Entregado,En Ejecución,No iniciado,Anulada,PENDIENTE_SUPERVISOR',
            'area' => 'nullable|string|max:255',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
            'epp' => 'sometimes|array',
            'epp.*.id' => 'required_with:epp|integer|exists:pedido_epp,id',
            'epp.*.cantidad' => 'required_with:epp|integer|min:0',
            'epp.*.observaciones' => 'nullable|string',
        ]);

        try {
            // Crear DTO para el Use Case
            $dto = ActualizarProduccionPedidoDTO::fromRequest((string)$id, $validated);

            // Usar el nuevo Use Case DDD
            $pedidoActualizado = $this->actualizarProduccionPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Anular pedido - DELEGADO A USE CASE
     */
    public function destroy(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'razon' => 'required|string|max:500',
            ]);

            // Crear DTO para el Use Case
            $dto = AnularProduccionPedidoDTO::fromRequest((string)$id, $validated);

            // Usar el nuevo Use Case DDD
            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pedido: ' . $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener siguiente número de pedido - DELEGADO A SERVICIO
     */
    /**
     * Obtener siguiente número de pedido - DELEGADO A USE CASE
     */
    public function getNextPedido()
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerProximoNumeroPedidoDTO::crear();

            // Usar el nuevo Use Case DDD
            $siguientePedido = $this->obtenerProximoNumeroPedidoUseCase->ejecutar($dto);

            return response()->json([
                'siguiente_pedido' => $siguientePedido
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener próximo número',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener notificaciones del asesor
     */
    public function getNotificaciones()
    {
        $dto = ObtenerNotificacionesDTO::crear();
        $notificaciones = $this->obtenerNotificacionesUseCase->ejecutar($dto);
        return response()->json($notificaciones);
    }

    /**
     * Obtener notificaciones del asesor (alias para compatibilidad)
     */
    public function getNotifications()
    {
        return $this->getNotificaciones();
    }

    /**
     * Marcar todas las notificaciones como leídas - DELEGADO A USE CASE
     */
    public function markAllAsRead()
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::marcarTodos();
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una notificación específica como leída - DELEGADO A USE CASE
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::fromRequest($notificationId);
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificación',
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Actualizar el perfil del asesor
     */
    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048'
            ]);

            $archivoAvatar = $request->hasFile('avatar') ? $request->file('avatar') : null;

            // Crear DTO para el Use Case
            $dto = ActualizarPerfilAsesorDTO::fromRequest($validated, $archivoAvatar);

            // Usar el nuevo Use Case DDD
            $resultado = $this->actualizarPerfilAsesorUseCase->ejecutar($dto);

            return response()->json($resultado);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Errores de validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular pedido con novedad
     */
    /**
     * Anular pedido con novedad - DELEGADO A USE CASE (refactorizado de anularPedidoService)
     */
    public function anularPedido(Request $request, $id)
    {
        $validated = $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            // Buscar el pedido por numero_pedido (ya que el parámetro $id es el número del pedido, no el ID de BD)
            $pedidoModel = \App\Models\PedidoProduccion::where('numero_pedido', (int)$id)
                ->orWhere('id', (int)$id)
                ->first();
            
            if (!$pedidoModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }
            
            // Obtener el ID real del pedido
            $pedidoId = $pedidoModel->id;
            
            // Obtener usuario autenticado
            $usuario = auth()->user();
            $nombreUsuario = $usuario ? $usuario->name : 'Sistema';
            
            // Obtener el rol del usuario
            $rolUsuario = 'Sin rol';
            if ($usuario) {
                try {
                    // Obtener los roles del usuario (relación)
                    $rolesUsuario = $usuario->roles();
                    
                    \Log::info('[anularPedido] Roles del usuario:', [
                        'usuario_id' => $usuario->id,
                        'roles_ids' => $usuario->roles_ids ?? [],
                        'roles_count' => $rolesUsuario->count(),
                    ]);
                    
                    if ($rolesUsuario && $rolesUsuario->count() > 0) {
                        $rolUsuario = $rolesUsuario->first()->name ?? 'Sin rol';
                    }
                } catch (\Exception $e) {
                    \Log::warning('[anularPedido] Error obteniendo roles:', [
                        'error' => $e->getMessage(),
                        'usuario_id' => $usuario->id,
                    ]);
                }
            }
            
            \Log::info('[anularPedido] Información del usuario:', [
                'nombre' => $nombreUsuario,
                'rol' => $rolUsuario,
                'numero_pedido' => $id,
                'pedido_id' => $pedidoId,
            ]);
            
            // Crear DTO para el Use Case con información del usuario
            $dto = AnularProduccionPedidoDTO::fromRequest((string)$pedidoId, [
                'razon' => $validated['novedad'],
                'nombreUsuario' => $nombreUsuario,
                'rolUsuario' => $rolUsuario
            ]);

            // Usar el nuevo Use Case DDD
            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedidoAnulado,
            ]);
        } catch (\Exception $e) {
            \Log::error('[anularPedido] Error al anular pedido:', [
                'numero_pedido' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Confirmar corrección de pedido - Cambiar de DEVUELTO_A_ASESORA a PENDIENTE_SUPERVISOR
     */
    public function confirmarCorreccion(Request $request, $id)
    {
        \Log::info('[confirmarCorreccion] Iniciando confirmación de corrección', [
            'pedido_id' => $id,
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()->name ?? 'Desconocido',
        ]);

        try {
            // Buscar el pedido por ID primario
            $pedido = PedidoProduccion::find((int)$id);
            
            if (!$pedido) {
                \Log::warning('[confirmarCorreccion] Pedido no encontrado', [
                    'pedido_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            // Verificar que el pedido esté en estado DEVUELTO_A_ASESORA
            if (trim($pedido->estado) !== 'DEVUELTO_A_ASESORA') {
                \Log::warning('[confirmarCorreccion] Intento de confirmar pedido que no está en DEVUELTO_A_ASESORA', [
                    'pedido_id' => $pedido->id,
                    'estado_actual' => $pedido->estado,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'El pedido no está en estado "Devuelto a Asesora". Estado actual: ' . $pedido->estado,
                ], 422);
            }

            // Cambiar estado a PENDIENTE_SUPERVISOR
            $pedido->estado = 'PENDIENTE_SUPERVISOR';
            
            // Limpiar datos de revisión
            $pedido->motivo_revision = null;
            $pedido->fecha_revision = null;
            $pedido->usuario_revision = null;

            $pedido->save();

            // Refrescar el modelo
            $pedido->refresh();

            // Registrar novedad
            $separador = str_repeat('=', 50);
            $usuario = auth()->user();
            $nombreUsuario = $usuario ? $usuario->name : 'Sistema';
            
            $novedad = "CONFIRMACIÓN DE CORRECCIÓN DE PEDIDO:\n";
            $novedad .= $separador . "\n";
            $novedad .= "Fecha de confirmación: " . \Carbon\Carbon::now('UTC')->format('Y-m-d H:i:s') . "\n";
            $novedad .= "Usuario que confirma: " . $nombreUsuario . "\n";
            $novedad .= "Estado anterior: DEVUELTO_A_ASESORA\n";
            $novedad .= "Estado nuevo: PENDIENTE_SUPERVISOR\n";
            $novedad .= $separador . "\n";
            $novedad .= "El pedido ha sido corregido y está listo para supervisión.\n";

            // Append a las novedades existentes
            if ($pedido->novedades) {
                $pedido->novedades = $pedido->novedades . "\n" . $novedad;
            } else {
                $pedido->novedades = $novedad;
            }

            $pedido->save();

            // Log de éxito
            \Log::info('[confirmarCorreccion] Corrección confirmada exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'usuario_id' => auth()->id(),
                'nuevo_estado' => $pedido->estado,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Corrección confirmada. El pedido ha sido enviado a supervisión.',
                'data' => [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('[confirmarCorreccion] Error al confirmar corrección', [
                'pedido_id' => $id,
                'usuario_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar corrección: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar inventario de telas
     */
    public function inventarioTelas()
    {
        return app(AsesoresInventarioTelasController::class)->index();
    }

    /**
     * Obtener datos de la factura de un pedido - DELEGADO A SERVICIO
     */
    /**
     * Obtener datos de la factura de un pedido - DELEGADO A USE CASE
     */
    public function obtenerDatosFactura($id)
    {
        \Log::warning(' [CONTROLLER-FACTURA] ENDPOINT LLAMADO ', ['pedido_id' => $id]);
        
        try {
            //  LOGS DE DIAGNÓSTICO - AUTENTICACIÓN Y AUTORIZACIÓN
            $usuarioAutenticado = \Auth::user();
            \Log::info('[DIAGNÓSTICO] Verificando autenticación y autorización', [
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'NO_AUTENTICADO',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'ANÓNIMO',
                'usuario_email' => $usuarioAutenticado ? $usuarioAutenticado->email : 'N/A',
                'pedido_id' => $id,
                'ruta_accedida' => \Route::getCurrentRoute()->uri ?? 'desconocida',
                'método_http' => \Request::getMethod(),
            ]);
            
            //  OBTENER ROLES DEL USUARIO
            if ($usuarioAutenticado) {
                $rolesUsuario = $usuarioAutenticado->roles()->pluck('name')->toArray();
                
                //  EXTENSIÓN: APLICAR JERARQUÍA DE ROLES (herencia)
                $rolesConHerencia = \App\Services\RoleHierarchyService::getEffectiveRoles($rolesUsuario);
                
                \Log::info('[DIAGNÓSTICO] Roles y permisos del usuario', [
                    'usuario_id' => $usuarioAutenticado->id,
                    'roles' => $rolesUsuario,
                    'roles_con_herencia' => $rolesConHerencia,
                    'tiene_supervisor_pedidos' => in_array('supervisor_pedidos', $rolesConHerencia),
                    'tiene_asesor' => in_array('asesor', $rolesConHerencia),
                    'tiene_admin' => in_array('admin', $rolesConHerencia),
                ]);
            }
            
            \Log::info('[CONTROLLER-FACTURA] Obteniendo datos de factura para pedido: ' . $id);
            
            // Crear DTO para el Use Case
            $dto = ObtenerFacturaDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $datos = $this->obtenerFacturaUseCase->ejecutar($dto);
            
            \Log::info('[CONTROLLER-FACTURA] Datos obtenidos correctamente', [
                'pedido_id' => $id,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);
            
            // LOG CRÍTICO ANTES DE ENVIAR JSON
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    \Log::warning('[CONTROLLER-FACTURA-TELAS] Verificación ANTES de JSON', [
                        'prenda_idx' => $idx,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'tiene_telas_array' => isset($prenda['telas_array']),
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array_full' => json_encode($prenda['telas_array'] ?? []),
                    ]);
                }
            }
            
            \Log::info(' [CONTROLLER-FACTURA] Datos de factura obtenidos exitosamente');
            
            // LOG: Verificar modo_tallas en procesos
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    if (!empty($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as $pidx => $proc) {
                            \Log::info('[CONTROLLER-FACTURA-MODO-TALLAS] Verificación de proceso', [
                                'prenda_idx' => $idx,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                                'proceso_idx' => $pidx,
                                'proceso_tipo' => $proc['tipo'] ?? 'N/A',
                                'tiene_modo_tallas' => isset($proc['modo_tallas']),
                                'modo_tallas_valor' => $proc['modo_tallas'] ?? 'NULL',
                                'tiene_tallas_detalles' => isset($proc['tallas_detalles']),
                                'tallas_detalles_keys' => isset($proc['tallas_detalles']) ? array_keys($proc['tallas_detalles']) : 'N/A',
                                'tallas_detalles_count' => isset($proc['tallas_detalles']) ? count(array_filter($proc['tallas_detalles'])) : 0,
                            ]);
                        }
                    }
                }
            }
            
            //  LOG CRÍTICO: Verificar que las imágenes tienen IDs
            if (!empty($datos['prendas'])) {
                $primeraPrend = $datos['prendas'][0];
                if (!empty($primeraPrend['imagenes'])) {
                    \Log::info('[CONTROLLER-FACTURA-IMAGENES-VERIFICACION] IMÁGENES DE PRIMERA PRENDA:', [
                        'prenda_nombre' => $primeraPrend['nombre'] ?? 'N/A',
                        'cantidad_imagenes' => count($primeraPrend['imagenes']),
                        'primerImagen_estructura' => $primeraPrend['imagenes'][0] ?? 'NO_EXISTE',
                        'primerImagen_id' => $primeraPrend['imagenes'][0]['id'] ?? 'NO_TIENE_ID',
                        'primerImagen_ruta_original' => $primeraPrend['imagenes'][0]['ruta_original'] ?? 'NO_TIENE_RUTA_ORIGINAL',
                        'primerImagen_ruta_webp' => $primeraPrend['imagenes'][0]['ruta_webp'] ?? 'NO_TIENE_RUTA_WEBP',
                    ]);
                }
            }
            
            //  LOG FINAL: Verificar estructura exacta antes de retornar
            \Log::info('[CONTROLLER-FACTURA-JSON-RESPONSE] Estructura JSON final que se envía', [
                'estructura_keys' => array_keys($datos),
                'tiene_prendas' => isset($datos['prendas']),
                'prendas_count' => count($datos['prendas'] ?? []),
                'prendas_vacio' => empty($datos['prendas']),
                'prendas_tipo' => gettype($datos['prendas'] ?? null),
                'prendas_es_array' => is_array($datos['prendas'] ?? false),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $datos
            ]);
        } catch (\Exception $e) {
            $usuarioAutenticado = \Auth::user();
            \Log::error(' [CONTROLLER-FACTURA] ERROR obteniendo datos de factura', [
                'pedido_id' => $id,
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'N/A',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'N/A',
                'error_mensaje' => $e->getMessage(),
                'error_código' => $e->getCode(),
                'error_clase' => get_class($e),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener datos de recibos dinámicos para un pedido - DELEGADO A SERVICIO
     */
    /**
     * Obtener datos de recibos dinámicos para un pedido - DELEGADO A USE CASE
     */
    public function obtenerDatosRecibos($id)
    {
        try {
            // Crear DTO para el Use Case
            $dto = ObtenerRecibosDTO::fromRequest((string)$id);

            // Usar el nuevo Use Case DDD
            $datos = $this->obtenerRecibosUseCase->ejecutar($dto);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Agregar prenda simple al pedido (sin requerimientos complejos)
     */
    /**
     * Agregar prenda simple al pedido - DELEGADO A USE CASE
     */
    public function agregarPrendaSimple(Request $request, $pedidoId)
    {
        try {
            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'descripcion' => 'nullable|string|max:1000',
            ]);

            // Crear DTO para el Use Case
            $dto = AgregarPrendaSimpleDTO::fromRequest((string)$pedidoId, $validated);

            // Usar el nuevo Use Case DDD
            $resultado = $this->agregarPrendaSimpleUseCase->ejecutar($dto);

            return response()->json($resultado, 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * API para listar pedidos en tiempo real
     */
    public function apiListar(Request $request)
    {
        try {
            $tipo = $request->query('tipo');
            $filtros = [];
            
            if ($request->filled('estado')) {
                $filtros['estado'] = $request->estado;
            }
            
            if ($request->filled('search')) {
                $filtros['search'] = $request->search;
            }

            // Crear DTO para el Use Case
            $dto = ListarProduccionPedidosDTO::fromRequest($tipo, $filtros);

            // Usar el nuevo Use Case DDD
            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);

            // Transformar a array para JSON
            $pedidosArray = $pedidos->getCollection()->map(function ($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'area' => $pedido->area,
                    'novedades' => $pedido->novedades,
                    'forma_pago' => $pedido->forma_pago,
                    'fecha_creacion' => $pedido->fecha_creacion,
                    'fecha_estimada' => $pedido->fecha_estimada,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $pedidosArray
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en apiListar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al listar pedidos'
            ], 500);
        }
    }

    /**
     * Mostrar vista de borradores de pedidos
     * 
     * GET /asesores/pedidos/borradores
     */
    public function borradores(Request $request)
    {
        try {
            // Obtener borradores del usuario actual (estado = 'Borrador')
            $user = Auth::user();
            
            // Obtener todos los borradores del asesor (sin número de pedido asignado)
            $borradores = PedidoProduccion::where('estado', 'Borrador')
                ->where('asesor_id', $user->id)
                ->whereNull('numero_pedido')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
            
            \Log::info('[AsesoresController.borradores] Listando borradores', [
                'asesor_id' => $user->id,
                'count' => $borradores->count(),
                'total' => $borradores->total(),
            ]);
            
            return view('asesores.pedidos.borradores', [
                'borradores' => $borradores,
                'asesor' => $user
            ]);

        } catch (\Exception $e) {
            \Log::error('[AsesoresController.borradores] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error al listar borradores: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar borrador de pedido
     * 
     * DELETE /asesores/pedidos/borradores/{id}
     */
    public function destroyBorrador(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $borrador = PedidoProduccion::where('id', $id)
                ->where('estado', 'Borrador')
                ->where('asesor_id', $user->id)
                ->whereNull('numero_pedido')
                ->firstOrFail();

            $borrador->delete();

            \Log::info('[AsesoresController.destroyBorrador] Borrador eliminado', [
                'pedido_id' => $id,
                'asesor_id' => $user->id,
            ]);

            return redirect()->back()->with('success', 'Borrador eliminado exitosamente');

        } catch (\Exception $e) {
            \Log::error('[AsesoresController.destroyBorrador] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Error al eliminar el borrador: ' . $e->getMessage());
        }
    }
}
