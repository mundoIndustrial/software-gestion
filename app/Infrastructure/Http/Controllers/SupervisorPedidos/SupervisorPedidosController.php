<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\UseCases\ObtenerFacturaUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ReturnOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\ListPendingOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateProfileUseCase;
use App\Application\SupervisorPedidos\UseCases\GetComparisonDataUseCase;
use App\Application\SupervisorPedidos\UseCases\GetFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetSewingReceiptFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingOrdersCountUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use App\Application\SupervisorPedidos\UseCases\DownloadOrderPdfUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsViewUseCase;
use App\Application\SupervisorPedidos\UseCases\CancelSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveReceiptArrivalDateUseCase;
use App\Application\SupervisorPedidos\UseCases\ChangeOrderStatusUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderDetailedUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDisplayUseCase;
use App\Application\SupervisorPedidos\UseCases\GetNotificationsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetReceiptDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveSewingReceiptColorUseCase;
use App\Application\SupervisorPedidos\UseCases\SelectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\DeselectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderSelectionsUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkAllNotificationsAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\DeleteImageUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleNewsVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\TogglePedidoVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkNotificationAsReadUseCase;
use App\Application\SupervisorPedidos\DTOs\ActivateReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\CancelReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\SaveReceiptArrivalDateRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateProfileRequest;
use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\DTOs\DownloadOrderPdfRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderDetailedRequest;
use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\DTOs\DeleteImageRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoRequest;
use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\SaveSewingReceiptColorRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountRequest;
use App\Application\SupervisorPedidos\DTOs\GetComparisonDataRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveReceiptRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
class SupervisorPedidosController extends Controller
{
    private ApproveOrderUseCase $approveOrderUseCase;
    private ReturnOrderUseCase $returnOrderUseCase;
    private ActivateSewingReceiptUseCase $activateSewingReceiptUseCase;
    private ListPendingOrdersUseCase $listPendingOrdersUseCase;
    private ListOrdersUseCase $listOrdersUseCase;
    private GetOrderDetailsUseCase $getOrderDetailsUseCase;
    private GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase;
    private GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase;
    private UpdateProfileUseCase $updateProfileUseCase;
    private GetComparisonDataUseCase $getComparisonDataUseCase;
    private GetFilterOptionsUseCase $getFilterOptionsUseCase;
    private UpdateOrderUseCase $updateOrderUseCase;
    private GetSewingReceiptFilterOptionsUseCase $getSewingReceiptFilterOptionsUseCase;
    private GetPendingOrdersCountUseCase $getPendingOrdersCountUseCase;
    private ToggleOrderVisibilityUseCase $toggleOrderVisibilityUseCase;
    private DownloadOrderPdfUseCase $downloadOrderPdfUseCase;
    private GetOrderDetailsViewUseCase $getOrderDetailsViewUseCase;
    private CancelSewingReceiptUseCase $cancelSewingReceiptUseCase;
    private SaveReceiptArrivalDateUseCase $saveReceiptArrivalDateUseCase;
    private ChangeOrderStatusUseCase $changeOrderStatusUseCase;
    private ApproveOrderDetailedUseCase $approveOrderDetailedUseCase;
    private GetOrderDisplayUseCase $getOrderDisplayUseCase;
    private GetNotificationsUseCase $getNotificationsUseCase;
    private GetReceiptDetailsUseCase $getReceiptDetailsUseCase;
    private ApproveReceiptUseCase $approveReceiptUseCase;
    private SaveSewingReceiptColorUseCase $saveSewingReceiptColorUseCase;
    private SelectOrderUseCase $selectOrderUseCase;
    private DeselectOrderUseCase $deselectOrderUseCase;
    private GetOrderSelectionsUseCase $getOrderSelectionsUseCase;
    private MarkAllNotificationsAsReadUseCase $markAllNotificationsAsReadUseCase;
    private DeleteImageUseCase $deleteImageUseCase;
    private ToggleNewsVistoUseCase $toggleNewsVistoUseCase;
    private TogglePedidoVistoUseCase $togglePedidoVistoUseCase;
    private MarkNotificationAsReadUseCase $markNotificationAsReadUseCase;

    public function __construct(
        ApproveOrderUseCase $approveOrderUseCase,
        ReturnOrderUseCase $returnOrderUseCase,
        ActivateSewingReceiptUseCase $activateSewingReceiptUseCase,
        ListPendingOrdersUseCase $listPendingOrdersUseCase,
        ListOrdersUseCase $listOrdersUseCase,
        GetOrderDetailsUseCase $getOrderDetailsUseCase,
        GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase,
        GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase,
        UpdateProfileUseCase $updateProfileUseCase,
        GetComparisonDataUseCase $getComparisonDataUseCase,
        GetFilterOptionsUseCase $getFilterOptionsUseCase,
        UpdateOrderUseCase $updateOrderUseCase,
        GetSewingReceiptFilterOptionsUseCase $getSewingReceiptFilterOptionsUseCase,
        GetPendingOrdersCountUseCase $getPendingOrdersCountUseCase,
        ToggleOrderVisibilityUseCase $toggleOrderVisibilityUseCase,
        DownloadOrderPdfUseCase $downloadOrderPdfUseCase,
        GetOrderDetailsViewUseCase $getOrderDetailsViewUseCase,
        CancelSewingReceiptUseCase $cancelSewingReceiptUseCase,
        SaveReceiptArrivalDateUseCase $saveReceiptArrivalDateUseCase,
        ChangeOrderStatusUseCase $changeOrderStatusUseCase,
        ApproveOrderDetailedUseCase $approveOrderDetailedUseCase,
        GetOrderDisplayUseCase $getOrderDisplayUseCase,
        GetNotificationsUseCase $getNotificationsUseCase,
        GetReceiptDetailsUseCase $getReceiptDetailsUseCase,
        ApproveReceiptUseCase $approveReceiptUseCase,
        SaveSewingReceiptColorUseCase $saveSewingReceiptColorUseCase,
        SelectOrderUseCase $selectOrderUseCase,
        DeselectOrderUseCase $deselectOrderUseCase,
        GetOrderSelectionsUseCase $getOrderSelectionsUseCase,
        MarkAllNotificationsAsReadUseCase $markAllNotificationsAsReadUseCase,
        DeleteImageUseCase $deleteImageUseCase,
        ToggleNewsVistoUseCase $toggleNewsVistoUseCase,
        TogglePedidoVistoUseCase $togglePedidoVistoUseCase,
        MarkNotificationAsReadUseCase $markNotificationAsReadUseCase
    ) {
        $this->approveOrderUseCase = $approveOrderUseCase;
        $this->returnOrderUseCase = $returnOrderUseCase;
        $this->activateSewingReceiptUseCase = $activateSewingReceiptUseCase;
        $this->listPendingOrdersUseCase = $listPendingOrdersUseCase;
        $this->listOrdersUseCase = $listOrdersUseCase;
        $this->getOrderDetailsUseCase = $getOrderDetailsUseCase;
        $this->getPendingSewingReceiptsUseCase = $getPendingSewingReceiptsUseCase;
        $this->getPendingEmbroideryStampingReceiptsUseCase = $getPendingEmbroideryStampingReceiptsUseCase;
        $this->updateProfileUseCase = $updateProfileUseCase;
        $this->getComparisonDataUseCase = $getComparisonDataUseCase;
        $this->getFilterOptionsUseCase = $getFilterOptionsUseCase;
        $this->updateOrderUseCase = $updateOrderUseCase;
        $this->getSewingReceiptFilterOptionsUseCase = $getSewingReceiptFilterOptionsUseCase;
        $this->getPendingOrdersCountUseCase = $getPendingOrdersCountUseCase;
        $this->toggleOrderVisibilityUseCase = $toggleOrderVisibilityUseCase;
        $this->downloadOrderPdfUseCase = $downloadOrderPdfUseCase;
        $this->getOrderDetailsViewUseCase = $getOrderDetailsViewUseCase;
        $this->cancelSewingReceiptUseCase = $cancelSewingReceiptUseCase;
        $this->saveReceiptArrivalDateUseCase = $saveReceiptArrivalDateUseCase;
        $this->changeOrderStatusUseCase = $changeOrderStatusUseCase;
        $this->approveOrderDetailedUseCase = $approveOrderDetailedUseCase;
        $this->getOrderDisplayUseCase = $getOrderDisplayUseCase;
        $this->getNotificationsUseCase = $getNotificationsUseCase;
        $this->getReceiptDetailsUseCase = $getReceiptDetailsUseCase;
        $this->approveReceiptUseCase = $approveReceiptUseCase;
        $this->saveSewingReceiptColorUseCase = $saveSewingReceiptColorUseCase;
        $this->selectOrderUseCase = $selectOrderUseCase;
        $this->deselectOrderUseCase = $deselectOrderUseCase;
        $this->getOrderSelectionsUseCase = $getOrderSelectionsUseCase;
        $this->markAllNotificationsAsReadUseCase = $markAllNotificationsAsReadUseCase;
        $this->deleteImageUseCase = $deleteImageUseCase;
        $this->toggleNewsVistoUseCase = $toggleNewsVistoUseCase;
        $this->togglePedidoVistoUseCase = $togglePedidoVistoUseCase;
        $this->markNotificationAsReadUseCase = $markNotificationAsReadUseCase;
    }

    /**
     * Mostrar el perfil del supervisor
     */
    public function profile()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'Por favor inicia sesión para ver tu perfil.');
            }
            return view('supervisor-pedidos.profile', compact('user'));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al cargar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Activar recibo de costura
     * REFACTORIZADO CON DDD - Usa ActivateSewingReceiptUseCase
     */
    public function activarReciboCostura(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            // Validación rápida
            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $prenda = PrendaPedido::where('id', $prendaId)
                ->where('pedido_produccion_id', $pedidoId)
                ->first();

            if (!$prenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada en el pedido'
                ], 404);
            }

            // Crear DTO de request
            $activateRequest = new ActivateReceiptRequest(
                $pedidoId,
                $prendaId
            );
            
            // Ejecutar Use Case
            $response = $this->activateSewingReceiptUseCase->execute($activateRequest);
            
            return response()->json($response->toArray());
            
        } catch (\DomainException $e) {
            Log::warning('Domain error en activarReciboCostura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('[activarReciboCostura] Error: ' . $e->getMessage(), [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al activar recibo COSTURA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anular recibo de costura
     * REFACTORIZADO CON DDD - Usa CancelSewingReceiptUseCase
     */
    public function anularReciboCostura(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            // Crear DTO de request
            $cancelRequest = new CancelReceiptRequest(
                $pedidoId,
                $prendaId,
                "ANULADO desde supervisor"
            );
            
            // Ejecutar Use Case
            $response = $this->cancelSewingReceiptUseCase->execute($cancelRequest);

            Log::info('Recibo de costura anulado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'usuario' => auth()->user()?->name ?? 'N/A'
            ]);

            return response()->json($response->toArray());
            
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('[anularReciboCostura] Error: ' . $e->getMessage(), [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al anular recibo COSTURA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Guardar fecha de llegada del recibo
     * REFACTORIZADO CON DDD - Usa SaveReceiptArrivalDateUseCase
     */
    public function guardarFechaLlegadaRecibo($id)
    {
        try {
            // Crear DTO de request
            $saveRequest = new SaveReceiptArrivalDateRequest(
                (int) $id,
                request()->input('fecha_llegada')
            );

            // Ejecutar Use Case
            $response = $this->saveReceiptArrivalDateUseCase->execute($saveRequest);

            return response()->json($response->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al guardar fecha de llegada del recibo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la fecha de llegada'
            ], 500);
        }
    }

    /**
     * Actualizar el perfil del supervisor
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Validar datos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048'
            ]);

            // Crear DTO de request
            $updateRequest = new UpdateProfileRequest(
                userId: (string) $user->id,
                name: $validated['name'],
                email: $validated['email'],
                telefono: $validated['telefono'] ?? null,
                ciudad: $validated['ciudad'] ?? null,
                departamento: $validated['departamento'] ?? null,
                bio: $validated['bio'] ?? null,
                password: $validated['password'] ?? null,
                avatarFile: $request->file('avatar')
            );

            // Ejecutar Use Case
            $response = $this->updateProfileUseCase->execute($updateRequest);

            return response()->json($response->toArray());

        } catch (\RuntimeException $e) {
            Log::warning('Error de validación en updateProfile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar lista de órdenes para supervisar
     */
    public function index(Request $request)
    {
        try {
            $requestDTO = new ListOrdersRequest($request->query());
            $response = $this->listOrdersUseCase->execute($requestDTO);

            extract($response->toArray());
            return view('supervisor-pedidos.index', compact('ordenes', 'estados', 'pedidosSeleccionados'));
        } catch (\Exception $e) {
            Log::error('[index] Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar pedidos: ' . $e->getMessage());
        }
    }

    /**
     * Ver detalle de la orden
     * REFACTORIZADO CON DDD - Usa GetOrderDisplayUseCase
     */
    public function show($id)
    {
        try {
            $response = $this->getOrderDisplayUseCase->execute((int) $id);
            $orden = $response->getOrder();

            return view('supervisor-pedidos.show', compact('orden'));
        } catch (\Exception $e) {
            Log::error('Error al cargar detalle de orden: ' . $e->getMessage(), ['orden_id' => $id]);
            return redirect()->back()->with('error', 'Error al cargar el detalle: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de la orden (AJAX)
     * REFACTORIZADO CON DDD - Usa GetOrderDetailsViewUseCase
     */
    public function showPedidoDetalle($pedidoId)
    {
        try {
            $viewData = $this->getOrderDetailsViewUseCase->execute(new GetOrderDetailsViewRequest((int) $pedidoId));
            
            return response()->json($viewData, 200);
        } catch (\Exception $e) {
            \Log::error('Error en showPedidoDetalle: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener detalles del pedido'], 500);
        }
    }

    /**
     * Descargar PDF de la orden
     * REFACTORIZADO CON DDD - Usa DownloadOrderPdfUseCase
     */
    public function descargarPDF($id)
    {
        try {
            $request = new DownloadOrderPdfRequest((int) $id);
            $response = $this->downloadOrderPdfUseCase->execute($request);
            return $response->download();
        } catch (\Exception $e) {
            Log::error('Error al descargar PDF: ' . $e->getMessage(), ['orden_id' => $id]);
            return redirect()->back()->with('error', 'Error al descargar PDF: ' . $e->getMessage());
        }
    }

    /**
     * Aprobar orden (cambiar estado de PENDIENTE_SUPERVISOR a Pendiente Insumos)
     * REFACTORIZADO CON DDD - Usa ApproveOrderUseCase
     */
    public function aprobar($id)
    {
        try {
            // Crear DTO de request
            $approveRequest = new ApproveOrderRequest((int) $id);
            
            // Ejecutar Use Case
            $response = $this->approveOrderUseCase->execute($approveRequest);
            
            // Obtener la orden actualizada para broadcast
            $orden = PedidoProduccion::find($id);
            
            // Broadcast evento en tiempo real
            try {
                Log::info('Enviando broadcast de aprobación', [
                    'orden_id' => $id,
                    'numero_pedido' => $orden->numero_pedido ?? 'N/A',
                ]);
                
                $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'aprobado_por_supervisor_en']);
                broadcast($event);
                
                Log::info("✅ Broadcast enviado para aprobación de pedido {$orden->numero_pedido}");
            } catch (\Exception $e) {
                Log::error("❌ Error despachando broadcast: " . $e->getMessage());
            }

            return response()->json($response->toArray());
            
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error en aprobar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Anular orden con observación
     * REFACTORIZADO CON DDD - Usa ReturnOrderUseCase
     */
    public function anular(Request $request, $id)
    {
        $request->validate([
            'motivo_anulacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_anulacion.required' => 'El motivo de anulación es obligatorio',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivo_anulacion.max' => 'El motivo no puede exceder 500 caracteres',
        ]);

        try {
            // Crear DTO de request
            $returnRequest = new ReturnOrderRequest(
                (int) $id,
                $request->motivo_anulacion
            );
            
            // Ejecutar Use Case
            $response = $this->returnOrderUseCase->execute($returnRequest);
            
            // Obtener la orden actualizada para broadcast
            $orden = PedidoProduccion::find($id);
            
            // Broadcast evento en tiempo real
            try {
                Log::info('Enviando broadcast de devolución', [
                    'orden_id' => $id,
                    'numero_pedido' => $orden->numero_pedido ?? 'N/A',
                ]);
                
                $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'motivo_revision']);
                broadcast($event);
                
                Log::info("✅ Broadcast enviado para devolución de pedido {$orden->numero_pedido}");
            } catch (\Exception $e) {
                Log::error("❌ Error despachando broadcast: " . $e->getMessage());
            }

            return response()->json($response->toArray());
            
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error en anular: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al devolver el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ocultar orden en la vista de supervisor-pedidos
     * REFACTORIZADO CON DDD - Usa ToggleOrderVisibilityUseCase
     */
    public function ocultarPedido(Request $request, $id)
    {
        try {
            $visibilityRequest = new ToggleOrderVisibilityRequest(
                orderId: (int) $id,
                isHidden: true
            );
            $response = $this->toggleOrderVisibilityUseCase->execute($visibilityRequest);
            return response()->json($response->toArray());
        } catch (\Exception $e) {
            Log::error('Error al ocultar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al ocultar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar (revelar) un pedido oculto en la vista de supervisor-pedidos
     * REFACTORIZADO CON DDD - Usa ToggleOrderVisibilityUseCase
     */
    public function mostrarPedido(Request $request, $id)
    {
        try {
            $visibilityRequest = new ToggleOrderVisibilityRequest(
                orderId: (int) $id,
                isHidden: false
            );
            $response = $this->toggleOrderVisibilityUseCase->execute($visibilityRequest);
            return response()->json($response->toArray());
        } catch (\Exception $e) {
            Log::error('Error al mostrar pedido: ' . $e->getMessage(), [
                'pedido_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar orden completa (cambiar estado según tipo de cotización)
     * REFACTORIZADO CON DDD - Usa ApproveOrderDetailedUseCase
     */
    public function aprobarOrden($id)
    {
        try {
            // Crear DTO de request
            $approveRequest = new ApproveOrderDetailedRequest((int) $id);
            
            // Ejecutar Use Case
            $response = $this->approveOrderDetailedUseCase->execute($approveRequest);

            // Obtener la orden actualizada para broadcast
            $orden = PedidoProduccion::find($id);
            
            // Broadcast evento en tiempo real
            try {
                Log::info('Enviando broadcast de aprobación detallada', [
                    'orden_id' => $id,
                    'numero_pedido' => $orden->numero_pedido ?? 'N/A',
                    'tipo_estado' => $response->getNewStatus(),
                ]);
                
                $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['estado', 'area', 'aprobado_por_supervisor_en']);
                broadcast($event);
                
                Log::info("✅ Broadcast enviado para aprobación detallada de pedido {$orden->numero_pedido}");
            } catch (\Exception $e) {
                Log::error("❌ Error despachando broadcast: " . $e->getMessage());
            }

            return response()->json($response->toArray());

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error en aprobarOrden: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado de la orden
     * REFACTORIZADO CON DDD - Usa ChangeOrderStatusUseCase
     */
    public function cambiarEstado(Request $request, $id)
    {
        try {
            // Validar estado
            $request->validate([
                'estado' => 'required|in:No iniciado,En Ejecución,Entregado,Anulada',
            ]);

            // Crear DTO de request
            $changeStatusRequest = new ChangeOrderStatusRequest(
                (int) $id,
                $request->estado
            );

            // Ejecutar Use Case
            $response = $this->changeOrderStatusUseCase->execute($changeStatusRequest);

            Log::info('Estado de orden cambiado', [
                'orden_id' => $id,
                'usuario' => auth()->user()?->name ?? 'N/A'
            ]);

            return response()->json($response->toArray());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de la orden en JSON
     * EXACTAMENTE IGUAL que RegistroOrdenQueryController::show()
     */
    public function obtenerDatos($id)
    {
        try {
            $request = new GetOrderDetailsRequest((int)$id);
            $response = $this->getOrderDetailsUseCase->execute($request);
            
            return response()->json($response->toArray());
        } catch (\RuntimeException $e) {
            Log::warning('Orden no encontrada', ['order_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de orden', ['order_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener datos de factura para mostrar en modal - DELEGADO A USE CASE
     * Copia del método en AsesoresController para mantener consistencia
     */
    public function obtenerDatosFactura($id)
    {
        Log::warning(' [CONTROLLER-FACTURA-SUPERVISOR] ENDPOINT LLAMADO ', ['pedido_id' => $id]);
        
        try {
            //  LOGS DE DIAGNÓSTICO - AUTENTICACIÓN Y AUTORIZACIÓN
            $usuarioAutenticado = Auth::user();
            Log::info('[DIAGNÓSTICO-SUPERVISOR] Verificando autenticación y autorización', [
                'usuario_id' => $usuarioAutenticado ? $usuarioAutenticado->id : 'NO_AUTENTICADO',
                'usuario_nombre' => $usuarioAutenticado ? $usuarioAutenticado->name : 'ANÓNIMO',
                'usuario_email' => $usuarioAutenticado ? $usuarioAutenticado->email : 'N/A',
                'pedido_id' => $id,
                'ruta_accedida' => Route::getCurrentRoute()->uri ?? 'desconocida',
                'método_http' => request()->getMethod(),
            ]);
            
            //  OBTENER ROLES DEL USUARIO
            if ($usuarioAutenticado) {
                $rolesUsuario = $usuarioAutenticado->roles()->pluck('name')->toArray();
                
                //  EXTENSIÓN: APLICAR JERARQUÍA DE ROLES (herencia)
                $rolesConHerencia = \App\Services\RoleHierarchyService::getEffectiveRoles($rolesUsuario);
                
                Log::info('[DIAGNÓSTICO-SUPERVISOR] Roles y permisos del usuario', [
                    'usuario_id' => $usuarioAutenticado->id,
                    'roles' => $rolesUsuario,
                    'roles_con_herencia' => $rolesConHerencia,
                    'tiene_supervisor_pedidos' => in_array('supervisor_pedidos', $rolesConHerencia),
                    'tiene_asesor' => in_array('asesor', $rolesConHerencia),
                    'tiene_admin' => in_array('admin', $rolesConHerencia),
                ]);
            }
            
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR] Obteniendo datos de factura para pedido: ' . $id);
            
            // Crear DTO para el Use Case
            $dto = ObtenerFacturaDTO::fromRequest((string)$id);

            // Obtener Use Case desde container (inyectado por Laravel)
            $obtenerFacturaUseCase = app(ObtenerFacturaUseCase::class);
            
            // Usar el Use Case DDD
            $datos = $obtenerFacturaUseCase->ejecutar($dto);
            
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR] Datos obtenidos correctamente', [
                'pedido_id' => $id,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);
            
            // LOG CRÍTICO ANTES DE ENVIAR JSON
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    Log::warning('[CONTROLLER-FACTURA-SUPERVISOR-TELAS] Verificación ANTES de JSON', [
                        'prenda_idx' => $idx,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'tiene_telas_array' => isset($prenda['telas_array']),
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array_full' => json_encode($prenda['telas_array'] ?? []),
                    ]);
                }
            }
            
            Log::info(' [CONTROLLER-FACTURA-SUPERVISOR] Datos de factura obtenidos exitosamente');
            
            //  LOG FINAL: Verificar estructura exacta antes de retornar
            Log::info('[CONTROLLER-FACTURA-SUPERVISOR-JSON-RESPONSE] Estructura JSON final que se envía', [
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
            $usuarioAutenticado = Auth::user();
            Log::error(' [CONTROLLER-FACTURA-SUPERVISOR] ERROR obteniendo datos de factura', [
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
     * Obtener opciones de filtro para una columna
     */
    public function obtenerOpcionesFiltro($campo)
    {
        try {
            $request = new GetFilterOptionsRequest($campo);
            $response = $this->getFilterOptionsUseCase->execute($request);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            Log::error('Error al obtener opciones de filtro', [
                'campo' => $campo,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'opciones' => []
            ]);
        }
    }

    public function obtenerOpcionesFiltroPendientesCostura($campo)
    {
        try {
            $request = new GetSewingReceiptFilterOptionsRequest($campo);
            $response = $this->getSewingReceiptFilterOptionsUseCase->execute($request);
            return response()->json($response->toArray());
        } catch (\Exception $e) {
            Log::error('Error al obtener opciones filtro costura: ' . $e->getMessage());
            return response()->json(['opciones' => []]);
        }
    }

    /**
     * Obtener notificaciones del supervisor
     */
    /**
     * Obtener notificaciones (órdenes pendientes de aprobación)
     */
    public function getNotifications()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Use GetNotificationsUseCase to handle all notification logic
            $response = $this->getNotificationsUseCase->execute();

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al obtener notificaciones - Línea: ' . $e->getLine() . ' - ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    /**
     * Marcar todas las notificaciones como leídas
     * REFACTORIZADO CON DDD - Usa MarkAllNotificationsAsReadUseCase
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Crear DTO de request
            $markRequest = new MarkNotificationsAsReadRequest(
                (int)$user->id
            );

            // Ejecutar Use Case
            $response = $this->markAllNotificationsAsReadUseCase->execute($markRequest);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al marcar notificaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una notificación como leída
     * REFACTORIZADO CON DDD - Usa MarkNotificationAsReadUseCase
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Crear DTO de request
            $markRequest = new MarkNotificationAsReadRequest(
                (int)$notificationId
            );

            // Ejecutar Use Case
            $response = $this->markNotificationAsReadUseCase->execute($markRequest);

            return response()->json($response->toArray());

        } catch (\DomainException $e) {
            \Log::error('Error al marcar notificación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al marcar notificación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle visto de una novedad (News) para el usuario actual
     * REFACTORIZADO CON DDD - Usa ToggleNewsVistoUseCase
     */
    public function toggleNewsVisto(Request $request, $newsId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            // Crear DTO de request
            $toggleRequest = new ToggleNewsVistoRequest(
                (int)$newsId,
                (int)$user->id
            );

            // Ejecutar Use Case
            $response = $this->toggleNewsVistoUseCase->execute($toggleRequest);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado de noticia: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Toggle visto de un pedido para el usuario actual
     * REFACTORIZADO CON DDD - Usa TogglePedidoVistoUseCase
     */
    public function togglePedidoVisto(Request $request, $pedidoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            // Crear DTO de request
            $toggleRequest = new TogglePedidoVistoRequest(
                (int)$pedidoId,
                (int)$user->id
            );

            // Ejecutar Use Case
            $response = $this->togglePedidoVistoUseCase->execute($toggleRequest);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado de visualización del pedido: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener contador de órdenes pendientes de aprobación
     * Endpoint: GET /supervisor-pedidos/ordenes-pendientes-count
     */
    public function ordenesPendientesCount()
    {
        try {
            $request = new GetPendingOrdersCountRequest();
            $response = $this->getPendingOrdersCountUseCase->execute($request);
            return response()->json($response->toArray());
        } catch (\Exception $e) {
            Log::error('Error al obtener contador de órdenes pendientes', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'count' => 0,
                'pendientesLogo' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos del pedido y su cotización para comparación
     * GET /supervisor-pedidos/{id}/comparar
     */
    public function obtenerDatosComparacion($id)
    {
        try {
            $request = new GetComparisonDataRequest((int)$id);
            $response = $this->getComparisonDataUseCase->execute($request);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            Log::error('Error al obtener datos de comparación', [
                'error' => $e->getMessage(),
                'orden_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de comparación'
            ], 500);
        }
    }


    /**
     * Actualizar pedido completo
     * PUT /supervisor-pedidos/{id}/actualizar
     * REFACTORIZADO CON DDD - Usa UpdateOrderUseCase
     */
    public function update(Request $request, $id)
    {
        try {
            // Validar datos básicos del pedido
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

            // Crear DTO de request
            $updateRequest = new UpdateOrderRequest(
                orderId: (int) $id,
                cliente: $validated['cliente'],
                formaDePago: $validated['forma_de_pago'] ?? null,
                novedades: $validated['novedades'] ?? null,
                diaDeEntrega: $validated['dia_de_entrega'] ?? null,
                fechaEstimadaDeEntrega: $validated['fecha_estimada_de_entrega'] ?? null,
                prendas: $validated['prendas']
            );

            // Ejecutar Use Case
            $response = $this->updateOrderUseCase->execute($updateRequest, $request);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            Log::error('Error al actualizar pedido', [
                'error' => $e->getMessage(),
                'orden_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen de prenda
     * DELETE /supervisor-pedidos/imagen/{tipo}/{id}
     */
    /**
     * Eliminar imagen de la galería de un pedido
     * REFACTORIZADO CON DDD - Usa DeleteImageUseCase
     */
    public function deleteImage($tipo, $id)
    {
        try {
            // Crear DTO de request
            $deleteRequest = new DeleteImageRequest(
                $tipo,
                (int)$id
            );

            // Ejecutar Use Case
            $response = $this->deleteImageUseCase->execute($deleteRequest);

            return response()->json($response->toArray());

        } catch (\DomainException $e) {
            \Log::error('Error de dominio al eliminar imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar imagen', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar vista de pendientes de bordados y estampados por recibos
     */
    public function pendientesBordadoEstampado()
    {
        try {
            $request = new GetPendingEmbroideryStampingReceiptsRequest();
            $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($request);
            $procesosConCantidad = $response->getProcesses();

            return view('supervisor-pedidos.pendientes-bordado-estampado', compact('procesosConCantidad'));

        } catch (\Exception $e) {
            Log::error('Error al cargar pendientes bordado/estampado: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }

    /**
     * Pendientes de Costura
     */
    public function pendientesCostura(Request $request)
    {
        try {
            $requestDTO = new GetPendingSewingReceiptsRequest(
                numeroRecibo: $request->filled('numero_recibo') ? $request->numero_recibo : null,
                cliente: $request->filled('cliente') ? $request->cliente : null,
                asesor: $request->filled('asesor') ? $request->asesor : null,
                prendas: $request->filled('prendas') ? $request->prendas : null,
                fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null
            );

            $response = $this->getPendingSewingReceiptsUseCase->execute($requestDTO);
            $procesosConCantidad = $response->getReceipts();

            return view('supervisor-pedidos.pendientes-costura', compact('procesosConCantidad'));

        } catch (\Exception $e) {
            Log::error('Error al cargar pendientes costura: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }
    

    /**
     * Obtener detalles de un recibo específico
     */
    public function obtenerDetallesProceso($id)
    {
        try {
            // Use GetReceiptDetailsUseCase to handle all receipt detail logic
            $response = $this->getReceiptDetailsUseCase->execute((int)$id);

            if (!$response->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener detalles del recibo'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $response->getDetails()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener detalles del recibo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar un recibo
     * REFACTORIZADO CON DDD - Usa ApproveReceiptUseCase
     */
    public function aprobarProceso($id)
    {
        try {
            // Use ApproveReceiptUseCase to handle all approval logic
            $response = $this->approveReceiptUseCase->execute(new ApproveReceiptRequest((int)$id));

            if (!$response->isSuccess()) {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage()
                ], 400);
            }

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al aprobar recibo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el recibo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Construir descripción con tallas por prenda (igual que en módulo asesores)
     * Usa el método generarDescripcionDetallada de cada prenda para obtener la descripción completa
     * 
     * @param PedidoProduccion $order
     * @return string
     */
    private function buildDescripcionConTallas($order)
    {
        if (!$order->prendas || $order->prendas->isEmpty()) {
            return '';
        }

        // Generar descripción detallada para TODAS las prendas usando el método del modelo
        // Esto incluye automáticamente: Color, Tela, Manga, Reflectivo, Bolsillos, Broche y Tallas
        $totalPrendas = $order->prendas->count();
        $descripciones = $order->prendas->map(function($prenda, $index) use ($totalPrendas) {
            $base = $prenda->generarDescripcionDetallada($index + 1, $totalPrendas);

            // Adjuntar observaciones por tallas de PROCESOS cuando aplique
            // Reglas:
            // - modo_tallas = general    => traer observaciones desde pedidos_procesos_prenda_tallas.observaciones
            // - modo_tallas = especifico => usar la columna ubicaciones en pedidos_procesos_prenda_detalles
            try {
                $procesos = \DB::table('pedidos_procesos_prenda_detalles as ppd')
                    ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
                    ->whereNull('ppd.deleted_at')
                    ->where('ppd.prenda_pedido_id', $prenda->id)
                    ->orderBy('ppd.id', 'asc')
                    ->get([
                        'ppd.id',
                        'ppd.modo_tallas',
                        'ppd.ubicaciones',
                        'ppd.observaciones as observaciones_generales',
                        'tp.nombre as tipo_proceso_nombre',
                    ]);

                $lineasProc = [];
                foreach ($procesos as $proc) {
                    $modo = $proc->modo_tallas ?? 'generico';
                    $tipoProcesoNombre = $proc->tipo_proceso_nombre ?? 'PROCESO';

                    if ($modo === 'general') {
                        $tallasObs = \DB::table('pedidos_procesos_prenda_tallas')
                            ->where('proceso_prenda_detalle_id', $proc->id)
                            ->whereNotNull('observaciones')
                            ->where('observaciones', '!=', '')
                            ->orderBy('genero', 'asc')
                            ->orderBy('talla', 'asc')
                            ->get(['genero', 'talla', 'observaciones']);

                        if ($tallasObs->count() > 0) {
                            $lineasProc[] = "\nOBSERVACIONES POR TALLA - " . strtoupper($tipoProcesoNombre) . ":";
                            foreach ($tallasObs as $row) {
                                $genero = strtoupper((string) $row->genero);
                                $talla = $row->talla !== null ? (string) $row->talla : 'SOBREMEDIDA';
                                $obs = trim((string) $row->observaciones);
                                if ($obs === '') {
                                    continue;
                                }
                                $lineasProc[] = "- {$genero} {$talla}: {$obs}";
                            }
                        }
                    } elseif ($modo === 'especifico') {
                        $ubicaciones = [];
                        if (!empty($proc->ubicaciones)) {
                            $decoded = json_decode($proc->ubicaciones, true);
                            if (is_array($decoded)) {
                                $ubicaciones = $decoded;
                            }
                        }

                        if (!empty($ubicaciones)) {
                            $lineasProc[] = "\nUBICACIONES - " . strtoupper($tipoProcesoNombre) . ":";
                            foreach ($ubicaciones as $u) {
                                if (is_string($u)) {
                                    $nombre = trim($u);
                                    if ($nombre !== '') {
                                        $lineasProc[] = "- {$nombre}";
                                    }
                                    continue;
                                }

                                if (is_array($u)) {
                                    $nombre = trim((string)($u['nombre'] ?? $u['ubicacion'] ?? ''));
                                    $obs = trim((string)($u['observaciones'] ?? $u['obs'] ?? ''));
                                    if ($nombre === '' && $obs === '') {
                                        continue;
                                    }
                                    $lineasProc[] = $obs !== '' ? "- {$nombre}: {$obs}" : "- {$nombre}";
                                }
                            }
                        }
                    }
                }

                if (!empty($lineasProc)) {
                    $base .= "\n" . implode("\n", $lineasProc);
                }
            } catch (\Exception $e) {
                // silencioso
            }

            return $base;
        })->toArray();

        return implode("\n\n", $descripciones);
    }

    /**
     * Guardar imagen como WebP en carpeta del pedido
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $numeroPedido ID del pedido
     * @param string $tipo Tipo de imagen: 'prendas', 'logos', 'telas'
     * @return string Ruta relativa del archivo guardado
     */
    private function guardarImagenComoWebp($file, $numeroPedido, $tipo)
    {
        try {
            // Generar nombre único
            $nombreUnico = time() . '_' . uniqid() . '.webp';
            
            // Construir ruta: storage/app/public/pedidos/{numeroPedido}/{tipo}
            $carpetaRelativa = "pedidos/{$numeroPedido}/{$tipo}";
            $rutaCompleta = storage_path("app/public/{$carpetaRelativa}");
            
            // Crear directorio si no existe
            if (!\File::exists($rutaCompleta)) {
                \File::makeDirectory($rutaCompleta, 0755, true);
                \Log::info('📁 Carpeta creada', ['ruta' => $rutaCompleta]);
            }
            
            // Usar Intervention Image para convertir a webp
            $manager = \Intervention\Image\ImageManager::gd();
            $imagen = $manager->read($file->getRealPath());
            
            // Guardar como webp con calidad 85
            $rutaArchivo = $rutaCompleta . '/' . $nombreUnico;
            $imagen->toWebp(85)->save($rutaArchivo);
            
            \Log::info(' Imagen guardada como webp', [
                'nombre' => $nombreUnico,
                'numero_pedido' => $numeroPedido,
                'tipo' => $tipo,
                'ruta_completa' => $rutaArchivo,
                'ruta_relativa' => $carpetaRelativa . '/' . $nombreUnico
            ]);
            
            // Retornar ruta relativa para la base de datos
            return $carpetaRelativa . '/' . $nombreUnico;
            
        } catch (\Exception $e) {
            \Log::error(' Error al convertir imagen a webp: ' . $e->getMessage());
            // Fallback: guardar sin conversión en carpeta del pedido
            return $file->store("pedidos/{$numeroPedido}/{$tipo}", 'public');
        }
    }

    /**
     * Guardar el color de costura para un recibo
     */
    /**
     * Guardar color de costura en recibo
     * REFACTORIZADO CON DDD - Usa SaveSewingReceiptColorUseCase
     */
    public function guardarColorCostura(Request $request)
    {
        try {
            // Validar datos
            $validated = $request->validate([
                'numero_recibo' => 'required|string',
                'color' => 'required|string|max:100',
            ]);

            // Crear DTO de request
            $colorRequest = new SaveSewingReceiptColorRequest(
                $validated['numero_recibo'],
                $validated['color']
            );

            // Ejecutar Use Case
            $response = $this->saveSewingReceiptColorUseCase->execute($colorRequest);

            return response()->json($response->toArray());

        } catch (\DomainException $e) {
            \Log::error('Error de dominio al guardar color de costura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Error al guardar color de costura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el color'
            ], 500);
        }
    }

    /**
     * Seleccionar un pedido
     * REFACTORIZADO CON DDD - Usa SelectOrderUseCase
     */
    public function seleccionarPedido($pedidoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Crear DTO de request
            $selectRequest = new SelectOrderRequest(
                (int)$pedidoId,
                (int)$user->id
            );

            // Ejecutar Use Case
            $response = $this->selectOrderUseCase->execute($selectRequest);

            return response()->json($response->toArray());

        } catch (\DomainException $e) {
            \Log::error('Error al seleccionar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al seleccionar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al seleccionar el pedido'
            ], 500);
        }
    }

    /**
     * Deseleccionar un pedido
     * REFACTORIZADO CON DDD - Usa DeselectOrderUseCase
     */
    public function deseleccionarPedido($pedidoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Crear DTO de request
            $deselectRequest = new SelectOrderRequest(
                (int)$pedidoId,
                (int)$user->id
            );

            // Ejecutar Use Case
            $response = $this->deselectOrderUseCase->execute($deselectRequest);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al deseleccionar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al deseleccionar el pedido'
            ], 500);
        }
    }

    /**
     * Obtener selecciones del usuario actual
     * REFACTORIZADO CON DDD - Usa GetOrderSelectionsUseCase
     */
    public function obtenerSelecciones()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Ejecutar Use Case
            $response = $this->getOrderSelectionsUseCase->execute((int)$user->id);

            return response()->json($response->toArray());

        } catch (\Exception $e) {
            \Log::error('Error al obtener selecciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las selecciones'
            ], 500);
        }
    }
}
