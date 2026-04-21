<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ReturnOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ListPendingOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetComparisonDataUseCase;
use App\Application\SupervisorPedidos\UseCases\GetFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingOrdersCountUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use App\Application\SupervisorPedidos\UseCases\DownloadOrderPdfUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsViewUseCase;
use App\Application\SupervisorPedidos\UseCases\ChangeOrderStatusUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderDetailedUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDisplayUseCase;
use App\Application\SupervisorPedidos\UseCases\SelectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\DeselectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderSelectionsUseCase;
use App\Application\SupervisorPedidos\UseCases\DeleteImageUseCase;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\DTOs\DownloadOrderPdfRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewRequest;
use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderDetailedRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\DTOs\DeleteImageRequest;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountRequest;
use App\Application\SupervisorPedidos\DTOs\GetComparisonDataRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Exceptions\AuthenticationException;


class SupervisorOrdersController extends Controller
{
    private ApproveOrderUseCase $approveOrderUseCase;
    private ReturnOrderUseCase $returnOrderUseCase;
    private ListPendingOrdersUseCase $listPendingOrdersUseCase;
    private ListOrdersUseCase $listOrdersUseCase;
    private GetOrderDetailsUseCase $getOrderDetailsUseCase;
    private GetComparisonDataUseCase $getComparisonDataUseCase;
    private GetFilterOptionsUseCase $getFilterOptionsUseCase;
    private UpdateOrderUseCase $updateOrderUseCase;
    private GetPendingOrdersCountUseCase $getPendingOrdersCountUseCase;
    private ToggleOrderVisibilityUseCase $toggleOrderVisibilityUseCase;
    private DownloadOrderPdfUseCase $downloadOrderPdfUseCase;
    private GetOrderDetailsViewUseCase $getOrderDetailsViewUseCase;
    private ChangeOrderStatusUseCase $changeOrderStatusUseCase;
    private ApproveOrderDetailedUseCase $approveOrderDetailedUseCase;
    private GetOrderDisplayUseCase $getOrderDisplayUseCase;
    private SelectOrderUseCase $selectOrderUseCase;
    private DeselectOrderUseCase $deselectOrderUseCase;
    private GetOrderSelectionsUseCase $getOrderSelectionsUseCase;
    private DeleteImageUseCase $deleteImageUseCase;

    public function __construct(
        ApproveOrderUseCase $approveOrderUseCase,
        ReturnOrderUseCase $returnOrderUseCase,
        ListPendingOrdersUseCase $listPendingOrdersUseCase,
        ListOrdersUseCase $listOrdersUseCase,
        GetOrderDetailsUseCase $getOrderDetailsUseCase,
        GetComparisonDataUseCase $getComparisonDataUseCase,
        GetFilterOptionsUseCase $getFilterOptionsUseCase,
        UpdateOrderUseCase $updateOrderUseCase,
        GetPendingOrdersCountUseCase $getPendingOrdersCountUseCase,
        ToggleOrderVisibilityUseCase $toggleOrderVisibilityUseCase,
        DownloadOrderPdfUseCase $downloadOrderPdfUseCase,
        GetOrderDetailsViewUseCase $getOrderDetailsViewUseCase,
        ChangeOrderStatusUseCase $changeOrderStatusUseCase,
        ApproveOrderDetailedUseCase $approveOrderDetailedUseCase,
        GetOrderDisplayUseCase $getOrderDisplayUseCase,
        SelectOrderUseCase $selectOrderUseCase,
        DeselectOrderUseCase $deselectOrderUseCase,
        GetOrderSelectionsUseCase $getOrderSelectionsUseCase,
        DeleteImageUseCase $deleteImageUseCase
    ) {
        $this->approveOrderUseCase = $approveOrderUseCase;
        $this->returnOrderUseCase = $returnOrderUseCase;
        $this->listPendingOrdersUseCase = $listPendingOrdersUseCase;
        $this->listOrdersUseCase = $listOrdersUseCase;
        $this->getOrderDetailsUseCase = $getOrderDetailsUseCase;
        $this->getComparisonDataUseCase = $getComparisonDataUseCase;
        $this->getFilterOptionsUseCase = $getFilterOptionsUseCase;
        $this->updateOrderUseCase = $updateOrderUseCase;
        $this->getPendingOrdersCountUseCase = $getPendingOrdersCountUseCase;
        $this->toggleOrderVisibilityUseCase = $toggleOrderVisibilityUseCase;
        $this->downloadOrderPdfUseCase = $downloadOrderPdfUseCase;
        $this->getOrderDetailsViewUseCase = $getOrderDetailsViewUseCase;
        $this->changeOrderStatusUseCase = $changeOrderStatusUseCase;
        $this->approveOrderDetailedUseCase = $approveOrderDetailedUseCase;
        $this->getOrderDisplayUseCase = $getOrderDisplayUseCase;
        $this->selectOrderUseCase = $selectOrderUseCase;
        $this->deselectOrderUseCase = $deselectOrderUseCase;
        $this->getOrderSelectionsUseCase = $getOrderSelectionsUseCase;
        $this->deleteImageUseCase = $deleteImageUseCase;
    }

    /**
     * Mostrar lista de órdenes para supervisar
     */
    public function index(Request $request)
    {
        $params = $request->query();
        $params['user_id'] = $request->user()?->id;
        $requestDTO = new ListOrdersRequest($params);
        $response = $this->listOrdersUseCase->execute($requestDTO);

        extract($response->toArray());
        return view('supervisor-pedidos.index', compact('ordenes', 'estados', 'pedidosSeleccionados'));
    }

    /**
     * Tabla de entregas (supervisor) y recibidas (recepcion-despacho)
     */
    public function entregasRecibidas(Request $request)
    {
        $busqueda = trim((string) $request->query('q', ''));

        $consecutivoBaseSubquery = DB::table('consecutivos_recibos_pedidos as crpb')
            ->select([
                'crpb.pedido_produccion_id',
                'crpb.prenda_id',
                DB::raw('MAX(crpb.consecutivo_actual) as consecutivo_actual'),
            ])
            ->whereIn('crpb.tipo_recibo', ['COSTURA', 'COSTURA-BODEGA'])
            ->whereRaw("UPPER(COALESCE(crpb.estado, '')) <> 'ANULADO'")
            ->groupBy('crpb.pedido_produccion_id', 'crpb.prenda_id');

        $registros = DB::table('prenda_entregas as pe')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'pe.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pp.pedido_produccion_id')
            ->join('clientes as c', 'c.id', '=', 'ped.cliente_id')
            ->leftJoin('prenda_entrega_movimientos as pem', 'pem.prenda_pedido_id', '=', 'pe.prenda_pedido_id')
            ->leftJoin('consecutivos_recibos_pedidos as crp', 'crp.id', '=', 'pem.consecutivo_recibo_id')
            ->leftJoinSub($consecutivoBaseSubquery, 'crp_base', function ($join) {
                $join->on('crp_base.pedido_produccion_id', '=', 'ped.id')
                    ->on('crp_base.prenda_id', '=', 'pp.id');
            })
            ->leftJoin('users as ue', 'ue.id', '=', 'pe.usuario_id')
            ->leftJoin('users as ur', 'ur.id', '=', 'pem.usuario_recibido_id')
            ->select([
                'ped.numero_pedido',
                'c.nombre as cliente',
                'pp.nombre_prenda',
                DB::raw('COALESCE(crp.consecutivo_actual, crp_base.consecutivo_actual) as numero_recibo'),
                'pe.fecha_entrega',
                'ue.name as usuario_entrega',
                'pem.fecha_recibido',
                'ur.name as usuario_recibido',
                'pem.estado as estado_recibido',
                'pem.cantidad_entregada',
            ]);

        if ($busqueda !== '') {
            $registros = $registros->where(function ($query) use ($busqueda) {
                $query->where('ped.numero_pedido', 'like', '%' . $busqueda . '%')
                    ->orWhere('c.nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('pp.nombre_prenda', 'like', '%' . $busqueda . '%')
                    ->orWhere('ue.name', 'like', '%' . $busqueda . '%')
                    ->orWhere('ur.name', 'like', '%' . $busqueda . '%');
            });
        }

        $registros = $registros
            ->orderByRaw('COALESCE(pem.fecha_recibido, pe.fecha_entrega) DESC')
            ->paginate(25)
            ->withQueryString();

        return view('supervisor-pedidos.entregas-recibidas', compact('registros', 'busqueda'));
    }

    /**
     * Ver detalle de la orden
     */
    public function show($id)
    {
        try {
            $response = $this->getOrderDisplayUseCase->execute((int) $id);
            $orden = $response->getOrder();

            return view('supervisor-pedidos.show', compact('orden'));
        } catch (\DomainException $e) {
            return abort(404, 'Orden no encontrada: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de la orden (AJAX)
     */
    public function showPedidoDetalle($pedidoId)
    {
        $viewData = $this->getOrderDetailsViewUseCase->execute(new GetOrderDetailsViewRequest((int) $pedidoId));
        
        return response()->json($viewData, 200);
    }

    /**
     * Descargar PDF de la orden
     */
    public function descargarPDF($id)
    {
        $request = new DownloadOrderPdfRequest((int) $id);
        $response = $this->downloadOrderPdfUseCase->execute($request);
        return $response->download();
    }

    /**
     * Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente Insumos)
     */
    public function aprobar($id)
    {
        $approveRequest = new ApproveOrderRequest((int) $id);
        
        $response = $this->approveOrderUseCase->execute($approveRequest);
        
        $orden = PedidoProduccion::find($id);
        
        if ($orden) {
            $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'aprobado_por_supervisor_en']);
            broadcast($event);
        }

        return response()->json($response->toArray());
    }

    /**
     * Anular orden con observación
     */
    public function anular(Request $request, $id)
    {
        $validated = $request->validate([
            'motivo_anulacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_anulacion.required' => 'El motivo de anulación es obligatorio',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivo_anulacion.max' => 'El motivo no puede exceder 500 caracteres',
        ]);

        $returnRequest = new ReturnOrderRequest(
            (int) $id,
            $validated['motivo_anulacion']
        );
        
        $response = $this->returnOrderUseCase->execute($returnRequest);
        
        $orden = PedidoProduccion::find($id);
        
        if ($orden) {
            $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'motivo_revision']);
            broadcast($event);
        }

        return response()->json($response->toArray());
    }

    /**
     * Ocultar orden en la vista de supervisor-pedidos
     */
    public function ocultarPedido(Request $request, $id)
    {
        $visibilityRequest = new ToggleOrderVisibilityRequest(
            orderId: (int) $id,
            isHidden: true
        );
        $response = $this->toggleOrderVisibilityUseCase->execute($visibilityRequest);
        return response()->json($response->toArray());
    }

    /**
     * Mostrar (revelar) un pedido oculto en la vista de supervisor-pedidos
     */
    public function mostrarPedido(Request $request, $id)
    {
        $visibilityRequest = new ToggleOrderVisibilityRequest(
            orderId: (int) $id,
            isHidden: false
        );
        $response = $this->toggleOrderVisibilityUseCase->execute($visibilityRequest);
        return response()->json($response->toArray());
    }

    /**
     * Aprobar orden completa (cambiar estado según tipo de cotización)
     */
    public function aprobarOrden($id)
    {
        $approveRequest = new ApproveOrderDetailedRequest((int) $id);
        
        $response = $this->approveOrderDetailedUseCase->execute($approveRequest);

        $orden = PedidoProduccion::find($id);
        
        if ($orden) {
            $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'area', 'aprobado_por_supervisor_en']);
            broadcast($event);
        }

        return response()->json($response->toArray());
    }

    /**
     * Cambiar estado de la orden
     */
    public function cambiarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|in:No iniciado,En Ejecución,Entregado,Anulada',
        ]);

        $changeStatusRequest = new ChangeOrderStatusRequest(
            (int) $id,
            $validated['estado']
        );

        $response = $this->changeOrderStatusUseCase->execute($changeStatusRequest);

        return response()->json($response->toArray());
    }

    /**
     * Obtener datos de la orden en JSON
     */
    public function obtenerDatos($id)
    {
        $request = new GetOrderDetailsRequest((int)$id);
        $response = $this->getOrderDetailsUseCase->execute($request);
        
        return response()->json($response->toArray());
    }

    /**
     * Obtener datos de factura para mostrar en modal - DELEGADO A USE CASE
     */
    public function obtenerDatosFactura($id)
    {
        $usuarioAutenticado = Auth::user();
        if (!$usuarioAutenticado) {
            throw new AuthenticationException('Usuario no autenticado');
        }
        
        $dto = ObtenerFacturaDTO::fromRequest((string)$id);

        $obtenerFacturaUseCase = app(ObtenerFacturaUseCase::class);
        
        $datos = $obtenerFacturaUseCase->ejecutar($dto);
        
        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
    }

    /**
     * Obtener opciones de filtro para una columna
     */
    public function obtenerOpcionesFiltro($campo)
    {
        $request = new GetFilterOptionsRequest($campo);
        $response = $this->getFilterOptionsUseCase->execute($request);

        return response()->json($response->toArray());
    }

    /**
     * Obtener contador de órdenes pendientes de aprobación
     * Endpoint: GET /supervisor-pedidos/ordenes-pendientes-count
     */
    public function ordenesPendientesCount()
    {
        $request = new GetPendingOrdersCountRequest();
        $response = $this->getPendingOrdersCountUseCase->execute($request);
        return response()->json($response->toArray());
    }

    /**
     * Obtener datos del pedido y su cotización para comparación
     * GET /supervisor-pedidos/{id}/comparar
     */
    public function obtenerDatosComparacion($id)
    {
        $request = new GetComparisonDataRequest((int)$id);
        $response = $this->getComparisonDataUseCase->execute($request);

        return response()->json($response->toArray());
    }

    /**
     * Panel de estadísticas de asesoras para supervisor de pedidos.
     */
    public function estadisticasAsesoras(Request $request)
    {
        $hoy = Carbon::now();
        $periodo = strtolower((string) $request->query('periodo', 'mes'));
        if (!in_array($periodo, ['mes', 'ano', 'rango'], true)) {
            $periodo = 'mes';
        }

        $year = (int) $request->query('year', $hoy->year);
        $month = (int) $request->query('month', $hoy->month);
        if ($month < 1 || $month > 12) {
            $month = $hoy->month;
        }

        if ($periodo === 'ano') {
            $inicioActual = Carbon::create($year, 1, 1)->startOfDay();
            $finActual = $inicioActual->copy()->endOfYear();
            $inicioAnterior = $inicioActual->copy()->subYear()->startOfYear();
            $finAnterior = $inicioAnterior->copy()->endOfYear();
            $periodoActualLabel = (string) $year;
            $periodoAnteriorLabel = (string) ($year - 1);
        } elseif ($periodo === 'rango') {
            $desdeInput = (string) $request->query('desde', $hoy->copy()->startOfMonth()->toDateString());
            $hastaInput = (string) $request->query('hasta', $hoy->toDateString());

            try {
                $inicioActual = Carbon::parse($desdeInput)->startOfDay();
            } catch (\Throwable $e) {
                $inicioActual = $hoy->copy()->startOfMonth()->startOfDay();
            }

            try {
                $finActual = Carbon::parse($hastaInput)->endOfDay();
            } catch (\Throwable $e) {
                $finActual = $hoy->copy()->endOfDay();
            }

            if ($inicioActual->gt($finActual)) {
                [$inicioActual, $finActual] = [$finActual->copy()->startOfDay(), $inicioActual->copy()->endOfDay()];
            }

            $diasPeriodo = max(1, $inicioActual->diffInDays($finActual) + 1);
            $finAnterior = $inicioActual->copy()->subDay()->endOfDay();
            $inicioAnterior = $finAnterior->copy()->subDays($diasPeriodo - 1)->startOfDay();

            $periodoActualLabel = $inicioActual->format('d/m/Y') . ' - ' . $finActual->format('d/m/Y');
            $periodoAnteriorLabel = $inicioAnterior->format('d/m/Y') . ' - ' . $finAnterior->format('d/m/Y');
        } else {
            $inicioActual = Carbon::create($year, $month, 1)->startOfDay();
            $finActual = $inicioActual->copy()->endOfMonth();
            $inicioAnterior = $inicioActual->copy()->subMonthNoOverflow()->startOfMonth();
            $finAnterior = $inicioAnterior->copy()->endOfMonth();
            $periodoActualLabel = $inicioActual->copy()->locale('es')->translatedFormat('F Y');
            $periodoAnteriorLabel = $inicioAnterior->copy()->locale('es')->translatedFormat('F Y');
        }

        $baseQuery = fn () => DB::table('pedidos_produccion')
            ->whereNull('deleted_at')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhere(function ($q) {
                        $q->where('estado', '!=', 'Anulada')
                          ->where('estado', '!=', 'Borrador');
                    });
            });

        $totalActual = $baseQuery()
            ->whereBetween('created_at', [$inicioActual, $finActual])
            ->count();

        $totalAnterior = $baseQuery()
            ->whereBetween('created_at', [$inicioAnterior, $finAnterior])
            ->count();

        $variacionPedidos = $totalAnterior > 0
            ? round((($totalActual - $totalAnterior) / $totalAnterior) * 100, 2)
            : ($totalActual > 0 ? 100.0 : 0.0);

        $rankingActual = $baseQuery()
            ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
            ->whereBetween('pedidos_produccion.created_at', [$inicioActual, $finActual])
            ->selectRaw('pedidos_produccion.asesor_id, COALESCE(u.name, "Sin asesora") as asesora_nombre, COUNT(*) as total')
            ->groupBy('pedidos_produccion.asesor_id', 'u.name')
            ->orderByDesc('total')
            ->get();

        $rankingAnterior = $baseQuery()
            ->whereBetween('created_at', [$inicioAnterior, $finAnterior])
            ->selectRaw('asesor_id, COUNT(*) as total')
            ->groupBy('asesor_id')
            ->pluck('total', 'asesor_id');

        $rankingAsesoras = $rankingActual->map(function ($fila) use ($rankingAnterior) {
            $asesorId = $fila->asesor_id;
            $totalAnteriorAsesora = (int) ($rankingAnterior[$asesorId] ?? 0);
            $diferencia = (int) $fila->total - $totalAnteriorAsesora;
            $variacion = $totalAnteriorAsesora > 0
                ? round(($diferencia / $totalAnteriorAsesora) * 100, 2)
                : ((int) $fila->total > 0 ? 100.0 : 0.0);

            return [
                'asesor_id' => $asesorId,
                'asesora_nombre' => $fila->asesora_nombre,
                'total_actual' => (int) $fila->total,
                'total_anterior' => $totalAnteriorAsesora,
                'diferencia' => $diferencia,
                'variacion' => $variacion,
            ];
        });

        // Clientes por asesora en el período actual
        $clientesPorAsesora = $baseQuery()
            ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
            ->whereBetween('pedidos_produccion.created_at', [$inicioActual, $finActual])
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('pedidos_produccion.asesor_id, COALESCE(u.name, "Sin asesora") as asesora_nombre, COUNT(DISTINCT LOWER(TRIM(pedidos_produccion.cliente))) as clientes_unicos')
            ->groupBy('pedidos_produccion.asesor_id', 'u.name')
            ->orderByDesc('clientes_unicos')
            ->get()
            ->keyBy('asesor_id');

        // Enriquecer ranking de asesoras con clientes únicos
        $rankingAsesoras = $rankingAsesoras->map(function ($asesora) use ($clientesPorAsesora) {
            $clientesUnicos = $clientesPorAsesora[$asesora['asesor_id']]->clientes_unicos ?? 0;
            $asesora['clientes_unicos'] = (int) $clientesUnicos;
            return $asesora;
        });

        $clientesActualPeriodo = $baseQuery()
            ->whereBetween('created_at', [$inicioActual, $finActual])
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('LOWER(TRIM(cliente)) as cliente_key')
            ->groupBy('cliente_key');

        $clientesHistoricos = $baseQuery()
            ->where('created_at', '<', $inicioActual)
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('LOWER(TRIM(cliente)) as cliente_key')
            ->groupBy('cliente_key');

        $clientesRecurrentesCount = DB::query()
            ->fromSub($clientesActualPeriodo, 'actual')
            ->joinSub($clientesHistoricos, 'historico', function ($join) {
                $join->on('actual.cliente_key', '=', 'historico.cliente_key');
            })
            ->count();

        $clientesUnicosPeriodo = $baseQuery()
            ->whereBetween('created_at', [$inicioActual, $finActual])
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(cliente))) as total')
            ->value('total') ?? 0;

        $porcentajeRecompra = $clientesUnicosPeriodo > 0
            ? round(($clientesRecurrentesCount / $clientesUnicosPeriodo) * 100, 2)
            : 0.0;

        $pedidosClientesPeriodo = $baseQuery()
            ->leftJoin('users as u', 'pedidos_produccion.asesor_id', '=', 'u.id')
            ->whereBetween('pedidos_produccion.created_at', [$inicioActual, $finActual])
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('LOWER(TRIM(pedidos_produccion.cliente)) as cliente_key')
            ->addSelect([
                'pedidos_produccion.cliente as cliente_nombre',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.created_at',
                'pedidos_produccion.estado',
            ])
            ->selectRaw('COALESCE(u.name, "Sin asesora") as asesora_nombre')
            ->orderByDesc('pedidos_produccion.created_at')
            ->get();

        $clientesHistoricosSet = $baseQuery()
            ->where('created_at', '<', $inicioActual)
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('LOWER(TRIM(cliente)) as cliente_key')
            ->groupBy('cliente_key')
            ->pluck('cliente_key')
            ->flip();

        $clientesRegistradosPreviosSet = DB::table('clientes')
            ->whereNotNull('nombre')
            ->whereRaw("TRIM(nombre) <> ''")
            ->where('created_at', '<', $inicioActual)
            ->selectRaw('LOWER(TRIM(nombre)) as cliente_key')
            ->groupBy('cliente_key')
            ->pluck('cliente_key')
            ->flip();

        $clientesConPedidos = $pedidosClientesPeriodo
            ->groupBy('cliente_key')
            ->map(function ($rows, $clienteKey) use ($clientesHistoricosSet) {
                $clienteNombre = $rows->first()->cliente_nombre;
                $pedidos = $rows->map(function ($pedido) {
                    $fecha = $pedido->created_at ? Carbon::parse($pedido->created_at) : null;
                    return [
                        'numero_pedido' => $pedido->numero_pedido,
                        'fecha' => $fecha ? $fecha->format('d/m/Y H:i') : '-',
                        'asesora_nombre' => $pedido->asesora_nombre,
                        'estado' => $pedido->estado ?: 'Sin estado',
                    ];
                })->values();

                $asesorasUnicas = $rows->pluck('asesora_nombre')->unique()->values()->implode(', ');

                return [
                    'cliente_key' => $clienteKey,
                    'cliente_nombre' => $clienteNombre,
                    'asesoras' => $asesorasUnicas,
                    'total_pedidos' => $rows->count(),
                    'es_recurrente' => isset($clientesHistoricosSet[$clienteKey]),
                    'pedidos' => $pedidos,
                ];
            })
            ->sortByDesc('total_pedidos')
            ->values();

        $clientesNuevos = $clientesConPedidos
            ->filter(function ($cliente) use ($clientesRegistradosPreviosSet) {
                return !$cliente['es_recurrente']
                    && !isset($clientesRegistradosPreviosSet[$cliente['cliente_key']]);
            })
            ->values();
        $clientesNuevosCount = $clientesNuevos->count();

        $topClientes = $clientesConPedidos
            ->take(8)
            ->map(fn ($cliente) => [
                'cliente_nombre' => $cliente['cliente_nombre'],
                'total' => $cliente['total_pedidos'],
                'es_recurrente' => $cliente['es_recurrente'],
            ])
            ->values();

        $clientesActualSet = $clientesConPedidos->pluck('cliente_key')->flip();
        $clientesPeriodoAnterior = $baseQuery()
            ->whereBetween('created_at', [$inicioAnterior, $finAnterior])
            ->whereNotNull('cliente')
            ->whereRaw("TRIM(cliente) <> ''")
            ->selectRaw('LOWER(TRIM(cliente)) as cliente_key, MIN(cliente) as cliente_nombre, COUNT(*) as total_anterior, MAX(created_at) as ultima_compra')
            ->groupBy('cliente_key')
            ->get();

        $clientesInactivos = $clientesPeriodoAnterior
            ->filter(fn ($cliente) => !isset($clientesActualSet[$cliente->cliente_key]))
            ->map(function ($cliente) {
                $ultimaCompra = $cliente->ultima_compra ? Carbon::parse($cliente->ultima_compra)->format('d/m/Y') : '-';
                return [
                    'cliente_nombre' => $cliente->cliente_nombre,
                    'total_anterior' => (int) $cliente->total_anterior,
                    'ultima_compra' => $ultimaCompra,
                ];
            })
            ->sortByDesc('total_anterior')
            ->values();

        return view('supervisor-pedidos.estadisticas-asesoras', [
            'periodo' => $periodo,
            'year' => $year,
            'month' => $month,
            'desde' => $inicioActual->toDateString(),
            'hasta' => $finActual->toDateString(),
            'periodoActual' => $periodoActualLabel,
            'periodoAnterior' => $periodoAnteriorLabel,
            'totalActual' => $totalActual,
            'totalAnterior' => $totalAnterior,
            'diferenciaPedidos' => $totalActual - $totalAnterior,
            'variacionPedidos' => $variacionPedidos,
            'rankingAsesoras' => $rankingAsesoras,
            'clientesUnicosMes' => (int) $clientesUnicosPeriodo,
            'clientesNuevosCount' => $clientesNuevosCount,
            'clientesRecurrentesCount' => (int) $clientesRecurrentesCount,
            'porcentajeRecompra' => $porcentajeRecompra,
            'topClientes' => $topClientes,
            'clientesConPedidos' => $clientesConPedidos,
            'clientesNuevos' => $clientesNuevos,
            'clientesInactivos' => $clientesInactivos,
        ]);
    }

    /**
     * Actualizar pedido completo
     * PUT /supervisor-pedidos/{id}/actualizar
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:255',
            'novedades' => 'nullable|string',
            'dia_de_entrega' => 'nullable|integer|min:1',
            'fecha_estimada_de_entrega' => 'nullable|string',
            'prendas' => 'required|array|min:1',
            'prendas.*.id' => 'required|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required|string|max:255',
            'prendas.*.descripcion' => 'nullable|string',
            'prendas.*.obs_manga' => 'nullable|string',
            'prendas.*.obs_bolsillos' => 'nullable|string',
            'prendas.*.obs_broche' => 'nullable|string',
            'prendas.*.obs_reflectivo' => 'nullable|string',
            'prendas.*.cantidad_talla' => 'nullable|array',
            'prendas.*.color_id' => 'nullable|exists:colores_prenda,id',
            'prendas.*.tela_id' => 'nullable|exists:telas_prenda,id',
            'prendas.*.tipo_manga_id' => 'nullable|exists:tipos_manga,id',
            'prendas.*.tipo_broche_boton_id' => 'nullable|exists:tipos_broche_boton,id',
            'prendas.*.tiene_bolsillos' => 'nullable|boolean',
            'prendas.*.tiene_reflectivo' => 'nullable|boolean',
            'prendas.*.nuevas_fotos' => 'nullable|array',
            'prendas.*.nuevas_fotos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'prendas.*.nuevas_fotos_logo' => 'nullable|array',
            'prendas.*.nuevas_fotos_logo.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'prendas.*.nuevas_fotos_tela' => 'nullable|array',
            'prendas.*.nuevas_fotos_tela.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $updateRequest = new UpdateOrderRequest(
            orderId: (int) $id,
            cliente: $validated['cliente'],
            formaDePago: $validated['forma_de_pago'] ?? null,
            novedades: $validated['novedades'] ?? null,
            diaDeEntrega: $validated['dia_de_entrega'] ?? null,
            fechaEstimadaDeEntrega: $validated['fecha_estimada_de_entrega'] ?? null,
            prendas: $validated['prendas']
        );

        $response = $this->updateOrderUseCase->execute($updateRequest, $request);

        return response()->json($response->toArray());
    }

    /**
     * Eliminar imagen de prenda
     */
    public function deleteImage($tipo, $id)
    {
        $deleteRequest = new DeleteImageRequest(
            $tipo,
            (int)$id
        );

        $response = $this->deleteImageUseCase->execute($deleteRequest);

        return response()->json($response->toArray());
    }

    /**
     * Seleccionar un pedido
     */
    public function seleccionarPedido($pedidoId)
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Usuario no autenticado');
        }

        $selectRequest = new SelectOrderRequest(
            (int)$pedidoId,
            (int)$user->id
        );

        $response = $this->selectOrderUseCase->execute($selectRequest);

        return response()->json($response->toArray());
    }

    /**
     * Deseleccionar un pedido
     */
    public function deseleccionarPedido($pedidoId)
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Usuario no autenticado');
        }

        $deselectRequest = new SelectOrderRequest(
            (int)$pedidoId,
            (int)$user->id
        );

        $response = $this->deselectOrderUseCase->execute($deselectRequest);

        return response()->json($response->toArray());
    }

    /**
     * Obtener selecciones del usuario actual
     */
    public function obtenerSelecciones()
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('Usuario no autenticado');
        }

        $response = $this->getOrderSelectionsUseCase->execute((int)$user->id);

        return response()->json($response->toArray());
    }
}
