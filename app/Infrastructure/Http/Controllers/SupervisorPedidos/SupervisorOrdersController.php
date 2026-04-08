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
     * Ver detalle de la orden
     */
    public function show($id)
    {
        $response = $this->getOrderDisplayUseCase->execute((int) $id);
        $orden = $response->getOrder();

        return view('supervisor-pedidos.show', compact('orden'));
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
