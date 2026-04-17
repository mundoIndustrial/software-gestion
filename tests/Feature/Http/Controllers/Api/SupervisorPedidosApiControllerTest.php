<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Application\SupervisorPedidos\DTOs\GetComparisonDataResponse;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderResponse;
use App\Application\SupervisorPedidos\DTOs\ChangeOrderStatusResponse;
use App\Application\SupervisorPedidos\DTOs\GetFilterOptionsResponse;
use App\Application\SupervisorPedidos\DTOs\GetNotificationsResponse;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsResponse;
use App\Application\SupervisorPedidos\DTOs\GetOrderSelectionsResponse;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsResponse;
use App\Application\SupervisorPedidos\DTOs\GetPendingOrdersCountResponse;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsResponse;
use App\Application\SupervisorPedidos\DTOs\GetReceiptDetailsResponse;
use App\Application\SupervisorPedidos\DTOs\ListOrdersResponse;
use App\Application\SupervisorPedidos\DTOs\ApproveReceiptResponse;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderResponse;
use App\Application\SupervisorPedidos\DTOs\SaveReceiptArrivalDateResponse;
use App\Application\SupervisorPedidos\DTOs\SaveSewingReceiptColorResponse;
use App\Application\SupervisorPedidos\DTOs\SelectOrderResponse;
use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityResponse;
use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\CancelSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ChangeOrderStatusUseCase;
use App\Application\SupervisorPedidos\UseCases\GetComparisonDataUseCase;
use App\Application\SupervisorPedidos\UseCases\GetFilterOptionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetNotificationsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetOrderSelectionsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetReceiptDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingOrdersCountUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\UseCases\ReturnOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveReceiptArrivalDateUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveSewingReceiptColorUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkAllNotificationsAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\MarkNotificationAsReadUseCase;
use App\Application\SupervisorPedidos\UseCases\DeselectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\SelectOrderUseCase;
use App\Application\SupervisorPedidos\UseCases\ToggleNewsVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\TogglePedidoVistoUseCase;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class SupervisorPedidosApiControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unauthenticated_user_is_redirected_from_supervisor_orders_api(): void
    {
        $response = $this->get('/api/supervisor-pedidos/ordenes');

        $response->assertRedirect(route('login'));
    }

    public function test_asesor_user_cannot_access_supervisor_only_supervisor_orders_api(): void
    {
        $asesor = $this->createUserWithRole('asesor');

        $response = $this->actingAs($asesor, 'web')
            ->get('/api/supervisor-pedidos/ordenes');

        $response->assertForbidden();
    }

    public function test_supervisor_user_can_access_orders_index_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $listOrdersMock = Mockery::mock(ListOrdersUseCase::class);
        $listOrdersMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ListOrdersResponse(
                [['id' => 1, 'numero_pedido' => '1001']],
                ['PENDIENTE_SUPERVISOR'],
                [1]
            ));
        $this->app->instance(ListOrdersUseCase::class, $listOrdersMock);

        $response = $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/ordenes');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estados.0', 'PENDIENTE_SUPERVISOR')
            ->assertJsonPath('data.pedidosSeleccionados.0', 1)
            ->assertJsonPath('data.ordenes.0.numero_pedido', '1001');
    }

    public function test_supervisor_user_can_access_orders_fragment_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $listOrdersMock = Mockery::mock(ListOrdersUseCase::class);
        $listOrdersMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ListOrdersResponse(
                new LengthAwarePaginator([], 0, 15, 1, ['path' => '/supervisor-pedidos']),
                [],
                []
            ));
        $this->app->instance(ListOrdersUseCase::class, $listOrdersMock);

        $response = $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/ordenes-fragment');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['html'],
            ]);
    }

    public function test_supervisor_user_can_access_receipts_endpoints_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $embroideryMock = Mockery::mock(GetPendingEmbroideryStampingReceiptsUseCase::class);
        $embroideryMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetPendingEmbroideryStampingReceiptsResponse([
                ['numero_recibo' => 'RB-1'],
            ]));
        $this->app->instance(GetPendingEmbroideryStampingReceiptsUseCase::class, $embroideryMock);

        $sewingMock = Mockery::mock(GetPendingSewingReceiptsUseCase::class);
        $sewingMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetPendingSewingReceiptsResponse([
                ['numero_recibo' => 'RC-1'],
            ]));
        $this->app->instance(GetPendingSewingReceiptsUseCase::class, $sewingMock);

        $qcMock = Mockery::mock(GetPendingQualityControlReceiptsUseCase::class);
        $qcMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetPendingSewingReceiptsResponse([
                ['numero_recibo' => 'RQC-1'],
            ]));
        $this->app->instance(GetPendingQualityControlReceiptsUseCase::class, $qcMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/recibos/pendientes-bordado-estampado')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.procesosConCantidad.0.numero_recibo', 'RB-1');

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/recibos/pendientes-costura')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.procesosConCantidad.0.numero_recibo', 'RC-1');

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/recibos/pendientes-control-calidad')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.procesosConCantidad.0.numero_recibo', 'RQC-1');
    }

    public function test_supervisor_receipts_api_passes_search_term_to_embroidery_use_case(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $embroideryMock = Mockery::mock(GetPendingEmbroideryStampingReceiptsUseCase::class);
        $embroideryMock->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($request) {
                return $request instanceof \App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest
                    && $request->getBusqueda() === '32';
            }))
            ->andReturn(new GetPendingEmbroideryStampingReceiptsResponse([
                ['numero_recibo' => '32'],
            ]));
        $this->app->instance(GetPendingEmbroideryStampingReceiptsUseCase::class, $embroideryMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/recibos/pendientes-bordado-estampado?busqueda=32')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.procesosConCantidad.0.numero_recibo', '32');
    }

    public function test_supervisor_receipts_api_passes_numeric_exact_search_term_to_embroidery_use_case(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $embroideryMock = Mockery::mock(GetPendingEmbroideryStampingReceiptsUseCase::class);
        $embroideryMock->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($request) {
                return $request instanceof \App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest
                    && $request->getBusqueda() === '6';
            }))
            ->andReturn(new GetPendingEmbroideryStampingReceiptsResponse([
                ['numero_recibo' => '6'],
            ]));
        $this->app->instance(GetPendingEmbroideryStampingReceiptsUseCase::class, $embroideryMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/recibos/pendientes-bordado-estampado?busqueda=6')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.procesosConCantidad.0.numero_recibo', '6');
    }

    public function test_asesor_user_can_access_shared_order_data_and_comparison_api(): void
    {
        $asesor = $this->createUserWithRole('asesor');

        $detailsMock = Mockery::mock(GetOrderDetailsUseCase::class);
        $detailsMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetOrderDetailsResponse([
                'id' => 123,
                'numero_pedido' => '9001',
            ]));
        $this->app->instance(GetOrderDetailsUseCase::class, $detailsMock);

        $comparisonMock = Mockery::mock(GetComparisonDataUseCase::class);
        $comparisonMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetComparisonDataResponse([
                'pedido' => ['numero' => '9001'],
                'cotizacion' => null,
            ]));
        $this->app->instance(GetComparisonDataUseCase::class, $comparisonMock);

        $this->actingAs($asesor, 'web')
            ->getJson('/api/supervisor-pedidos/ordenes/123/datos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', 123)
            ->assertJsonPath('data.numero_pedido', '9001');

        $this->actingAs($asesor, 'web')
            ->getJson('/api/supervisor-pedidos/ordenes/123/comparar')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pedido.numero', '9001');
    }

    public function test_supervisor_user_can_access_pending_orders_count_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $countMock = Mockery::mock(GetPendingOrdersCountUseCase::class);
        $countMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetPendingOrdersCountResponse(7, 2));
        $this->app->instance(GetPendingOrdersCountUseCase::class, $countMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/ordenes-pendientes-count')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 7)
            ->assertJsonPath('data.pendientesLogo', 2);
    }

    public function test_supervisor_user_can_use_filter_and_selection_endpoints_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $filterMock = Mockery::mock(GetFilterOptionsUseCase::class);
        $filterMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetFilterOptionsResponse(['PENDIENTE', 'EN_PROCESO']));
        $this->app->instance(GetFilterOptionsUseCase::class, $filterMock);

        $selectMock = Mockery::mock(SelectOrderUseCase::class);
        $selectMock->shouldReceive('execute')
            ->once()
            ->andReturn(new SelectOrderResponse(true, 'Pedido seleccionado correctamente', ['pedido_id' => 1]));
        $this->app->instance(SelectOrderUseCase::class, $selectMock);

        $deselectMock = Mockery::mock(DeselectOrderUseCase::class);
        $deselectMock->shouldReceive('execute')
            ->once()
            ->andReturn(new SelectOrderResponse(true, 'Pedido deseleccionado correctamente', ['pedido_id' => 1]));
        $this->app->instance(DeselectOrderUseCase::class, $deselectMock);

        $selectionsMock = Mockery::mock(GetOrderSelectionsUseCase::class);
        $selectionsMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetOrderSelectionsResponse(true, 'Selecciones obtenidas correctamente', [1, 5], 2));
        $this->app->instance(GetOrderSelectionsUseCase::class, $selectionsMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/filtro-opciones/estado')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opciones.0', 'PENDIENTE');

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/seleccionar/1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true);

        $this->actingAs($supervisor, 'web')
            ->deleteJson('/api/supervisor-pedidos/seleccionar/1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/selecciones')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.selections.0', 1);
    }

    public function test_supervisor_user_can_access_notifications_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $getNotificationsMock = Mockery::mock(GetNotificationsUseCase::class);
        $getNotificationsMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetNotificationsResponse(
                true,
                collect([['id' => 1, 'numero_pedido' => '1234']]),
                collect([]),
                1,
                1,
                0,
                0,
                1
            ));
        $this->app->instance(GetNotificationsUseCase::class, $getNotificationsMock);

        $this->app->instance(MarkAllNotificationsAsReadUseCase::class, Mockery::mock(MarkAllNotificationsAsReadUseCase::class));
        $this->app->instance(MarkNotificationAsReadUseCase::class, Mockery::mock(MarkNotificationAsReadUseCase::class));
        $this->app->instance(ToggleNewsVistoUseCase::class, Mockery::mock(ToggleNewsVistoUseCase::class));
        $this->app->instance(TogglePedidoVistoUseCase::class, Mockery::mock(TogglePedidoVistoUseCase::class));

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/notificaciones')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('totalGeneral', 1)
            ->assertJsonPath('notificaciones.0.id', 1);
    }

    public function test_supervisor_user_can_use_order_action_endpoints_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $approveMock = Mockery::mock(ApproveOrderUseCase::class);
        $approveMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ApproveOrderResponse(true, 'Pedido aprobado correctamente', 'Pendiente'));
        $this->app->instance(ApproveOrderUseCase::class, $approveMock);

        $cancelMock = Mockery::mock(ReturnOrderUseCase::class);
        $cancelMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ReturnOrderResponse(true, 'Pedido devuelto a revisión correctamente', 'DEVUELTO_A_ASESORA'));
        $this->app->instance(ReturnOrderUseCase::class, $cancelMock);

        $visibilityMock = Mockery::mock(ToggleOrderVisibilityUseCase::class);
        $visibilityMock->shouldReceive('execute')
            ->twice()
            ->andReturn(
                new ToggleOrderVisibilityResponse(true, 'Pedido ocultado correctamente'),
                new ToggleOrderVisibilityResponse(true, 'Pedido mostrado correctamente')
            );
        $this->app->instance(ToggleOrderVisibilityUseCase::class, $visibilityMock);

        $statusMock = Mockery::mock(ChangeOrderStatusUseCase::class);
        $statusMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ChangeOrderStatusResponse(
                true,
                'Estado actualizado correctamente',
                1,
                'No iniciado',
                'En Ejecución',
                ['id' => 1, 'estado' => 'En Ejecución']
            ));
        $this->app->instance(ChangeOrderStatusUseCase::class, $statusMock);

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/ordenes/1/aprobar')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/ordenes/1/anular', ['motivo_anulacion' => 'Motivo valido de prueba'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/ordenes/1/ocultar')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/ordenes/1/mostrar')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($supervisor, 'web')
            ->patchJson('/api/supervisor-pedidos/ordenes/1/estado', ['estado' => 'En Ejecución'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_supervisor_user_can_use_receipts_action_endpoints_api(): void
    {
        $supervisor = $this->createUserWithRole('supervisor_pedidos');

        $activateMock = Mockery::mock(ActivateSewingReceiptUseCase::class);
        $cancelMock = Mockery::mock(CancelSewingReceiptUseCase::class);
        $detailsMock = Mockery::mock(GetReceiptDetailsUseCase::class);
        $approveMock = Mockery::mock(ApproveReceiptUseCase::class);
        $saveColorMock = Mockery::mock(SaveSewingReceiptColorUseCase::class);
        $saveArrivalDateMock = Mockery::mock(SaveReceiptArrivalDateUseCase::class);
        $pendingSewingMock = Mockery::mock(GetPendingSewingReceiptsUseCase::class);
        $pendingQcMock = Mockery::mock(GetPendingQualityControlReceiptsUseCase::class);
        $pendingEmbroideryMock = Mockery::mock(GetPendingEmbroideryStampingReceiptsUseCase::class);

        $detailsMock->shouldReceive('execute')
            ->once()
            ->andReturn(new GetReceiptDetailsResponse(
                true,
                'Detalles recuperados correctamente',
                ['id' => 55, 'numero_recibo' => 'RC-55']
            ));

        $approveMock->shouldReceive('execute')
            ->once()
            ->andReturn(new ApproveReceiptResponse(
                true,
                'Recibo aprobado correctamente',
                55,
                3,
                ['numero_recibo' => 'RC-55']
            ));

        $saveArrivalDateMock->shouldReceive('execute')
            ->once()
            ->andReturn(new SaveReceiptArrivalDateResponse(
                true,
                'Fecha guardada correctamente',
                Carbon::parse('2026-03-28 10:00:00')
            ));

        $saveColorMock->shouldReceive('execute')
            ->once()
            ->andReturn(new SaveSewingReceiptColorResponse(
                true,
                'Color guardado correctamente',
                'RC-55'
            ));

        $this->app->instance(ActivateSewingReceiptUseCase::class, $activateMock);
        $this->app->instance(CancelSewingReceiptUseCase::class, $cancelMock);
        $this->app->instance(GetReceiptDetailsUseCase::class, $detailsMock);
        $this->app->instance(ApproveReceiptUseCase::class, $approveMock);
        $this->app->instance(SaveSewingReceiptColorUseCase::class, $saveColorMock);
        $this->app->instance(SaveReceiptArrivalDateUseCase::class, $saveArrivalDateMock);
        $this->app->instance(GetPendingSewingReceiptsUseCase::class, $pendingSewingMock);
        $this->app->instance(GetPendingQualityControlReceiptsUseCase::class, $pendingQcMock);
        $this->app->instance(GetPendingEmbroideryStampingReceiptsUseCase::class, $pendingEmbroideryMock);

        $this->actingAs($supervisor, 'web')
            ->getJson('/api/supervisor-pedidos/procesos/55/detalles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.numero_recibo', 'RC-55');

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/procesos/55/aprobar')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('receiptId', 55);

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/recibos/55/fecha-llegada', [
                'fecha_llegada' => '2026-03-28 10:00:00',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fecha_llegada', '2026-03-28 10:00:00');

        $this->actingAs($supervisor, 'web')
            ->postJson('/api/supervisor-pedidos/recibos/guardar-color-costura', [
                'numero_recibo' => 'RC-55',
                'color' => 'VERDE',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('receiptNumber', 'RC-55');
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['description' => 'Rol de prueba', 'requires_credentials' => false]
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'roles_ids' => [$role->id],
        ]);
    }
}
