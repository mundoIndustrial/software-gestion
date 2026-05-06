<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\DTOs\GetComparisonDataRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusRequest;
use App\Application\SupervisorPedidos\DTOs\DeleteImageRequest;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountRequest;
use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ChangeOrderStatusUseCase;
use App\Application\SupervisorPedidos\UseCases\DeleteImageUseCase;
use App\Application\SupervisorPedidos\UseCases\GetComparisonDataUseCase;
use App\Application\SupervisorPedidos\UseCases\GetFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderSelectionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingOrdersCountUseCase;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\ReturnOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use App\Application\SupervisorPedidos\UseCases\UpdateOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\DeselectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\SelectOrderUseCase;
use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SupervisorOrdersApiController extends Controller
{
    public function __construct(
        private readonly ListOrdersUseCase $listOrdersUseCase,
        private readonly GetPendingOrdersCountUseCase $getPendingOrdersCountUseCase,
        private readonly GetOrderDetailsUseCase $getOrderDetailsUseCase,
        private readonly GetComparisonDataUseCase $getComparisonDataUseCase,
        private readonly GetFilterOptionsUseCase $getFilterOptionsUseCase,
        private readonly SelectOrderUseCase $selectOrderUseCase,
        private readonly DeselectOrderUseCase $deselectOrderUseCase,
        private readonly GetOrderSelectionsUseCase $getOrderSelectionsUseCase,
        private readonly ApproveOrderUseCase $approveOrderUseCase,
        private readonly ReturnOrderUseCase $returnOrderUseCase,
        private readonly ToggleOrderVisibilityUseCase $toggleOrderVisibilityUseCase,
        private readonly ChangeOrderStatusUseCase $changeOrderStatusUseCase,
        private readonly UpdateOrderUseCase $updateOrderUseCase,
        private readonly DeleteImageUseCase $deleteImageUseCase
    ) {}

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $params['user_id'] = $request->user()?->id;

        $response = $this->listOrdersUseCase->execute(new ListOrdersRequest($params));
        extract($response->toArray());

        $html = View::make('supervisor-pedidos.partials.tabla-ordenes', compact('ordenes', 'estados', 'pedidosSeleccionados'))
            ->render();

        $responseBody = [
            'success' => true,
            'message' => 'Ordenes recuperadas correctamente',
            'data' => array_merge($response->toArray(), [
                'html' => $html,
            ]),
        ];

        return response()->json($responseBody);
    }

    public function indexFragment(Request $request): JsonResponse
    {
        $params = $request->query();
        $params['user_id'] = $request->user()?->id;

        $response = $this->listOrdersUseCase->execute(new ListOrdersRequest($params));
        extract($response->toArray());

        $html = View::make('supervisor-pedidos.partials.tabla-ordenes', compact('ordenes', 'estados', 'pedidosSeleccionados'))
            ->render();

        return response()->json([
            'success' => true,
            'message' => 'Fragmento de ordenes recuperado correctamente',
            'data' => [
                'html' => $html,
            ],
        ]);
    }

    public function pendingCount(): JsonResponse
    {
        $response = $this->getPendingOrdersCountUseCase->execute(new GetPendingOrdersCountRequest());

        return response()->json([
            'success' => true,
            'message' => $response->toArray()['message'] ?? 'Conteo recuperado correctamente',
            'data' => [
                'count' => $response->getTotalPendientes(),
                'pendientesLogo' => $response->getPendientesLogo(),
                'pendientesCarteraNoAprobado' => $response->getPendientesCarteraNoAprobado(),
            ],
        ]);
    }

    public function showData(int $id): JsonResponse
    {
        $response = $this->getOrderDetailsUseCase->execute(new GetOrderDetailsRequest($id));

        return response()->json([
            'success' => true,
            'message' => 'Detalle de orden recuperado correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function novedades(int $id): JsonResponse
    {
        $pedido = PedidoProduccion::withTrashed()
            ->select(['id', 'numero_pedido', 'novedades'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Novedades recuperadas correctamente',
            'data' => [
                'id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'novedades' => $pedido->novedades ?? '',
            ],
        ]);
    }

    public function comparison(int $id): JsonResponse
    {
        $response = $this->getComparisonDataUseCase->execute(new GetComparisonDataRequest($id));

        return response()->json([
            'success' => true,
            'message' => 'Datos de comparacion recuperados correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function filterOptions(string $campo): JsonResponse
    {
        $response = $this->getFilterOptionsUseCase->execute(new GetFilterOptionsRequest($campo));

        return response()->json([
            'success' => true,
            'message' => 'Opciones de filtro recuperadas correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function select(Request $request, int $pedidoId): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $response = $this->selectOrderUseCase->execute(new SelectOrderRequest($pedidoId, $userId));

        return response()->json([
            'success' => true,
            'message' => $response->getMessage(),
            'data' => $response->toArray(),
        ]);
    }

    public function deselect(Request $request, int $pedidoId): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $response = $this->deselectOrderUseCase->execute(new SelectOrderRequest($pedidoId, $userId));

        return response()->json([
            'success' => true,
            'message' => $response->getMessage(),
            'data' => $response->toArray(),
        ]);
    }

    public function selections(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $response = $this->getOrderSelectionsUseCase->execute($userId);

        return response()->json([
            'success' => true,
            'message' => $response->getMessage(),
            'data' => $response->toArray(),
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $response = $this->approveOrderUseCase->execute(new ApproveOrderRequest($id));
        $this->broadcastOrderUpdate($id, ['estado', 'aprobado_por_supervisor_en']);

        return response()->json($response->toArray());
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'motivo_anulacion' => 'required|string|min:10|max:500',
        ], [
            'motivo_anulacion.required' => 'El motivo de anulacion es obligatorio',
            'motivo_anulacion.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivo_anulacion.max' => 'El motivo no puede exceder 500 caracteres',
        ]);

        $response = $this->returnOrderUseCase->execute(
            new ReturnOrderRequest($id, $validated['motivo_anulacion'])
        );
        $this->broadcastOrderUpdate($id, ['estado', 'motivo_revision']);

        return response()->json($response->toArray());
    }

    public function hide(int $id): JsonResponse
    {
        $response = $this->toggleOrderVisibilityUseCase->execute(
            new ToggleOrderVisibilityRequest(orderId: $id, isHidden: true)
        );

        return response()->json($response->toArray());
    }

    public function show(int $id): JsonResponse
    {
        $response = $this->toggleOrderVisibilityUseCase->execute(
            new ToggleOrderVisibilityRequest(orderId: $id, isHidden: false)
        );

        return response()->json($response->toArray());
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'estado' => 'required|in:No iniciado,En Ejecucion,Entregado,Anulada,En Ejecución',
        ]);

        $normalizedStatus = $validated['estado'] === 'En Ejecucion'
            ? 'En Ejecución'
            : $validated['estado'];

        $response = $this->changeOrderStatusUseCase->execute(
            new ChangeOrderStatusRequest($id, $normalizedStatus)
        );

        return response()->json($response->toArray());
    }

    public function update(Request $request, int $id): JsonResponse
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
            orderId: $id,
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

    public function deleteImage(string $tipo, int $id): JsonResponse
    {
        $response = $this->deleteImageUseCase->execute(new DeleteImageRequest($tipo, $id));
        return response()->json($response->toArray());
    }

    private function broadcastOrderUpdate(int $orderId, array $changedFields): void
    {
        $orden = PedidoProduccion::find($orderId);
        if (!$orden) {
            return;
        }

        $event = new \App\Events\OrdenUpdated($orden->fresh(), 'updated', $changedFields);
        broadcast($event);
    }
}
