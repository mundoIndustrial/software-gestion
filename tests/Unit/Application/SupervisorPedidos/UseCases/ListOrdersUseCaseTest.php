<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Models\PedidoProduccion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery as m;
use Tests\TestCase;

class ListOrdersUseCaseTest extends TestCase
{
    private PedidoProduccionReadService $readService;
    private ListOrdersUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new ListOrdersUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_orquesta_listado_estados_y_selecciones_desde_read_service(): void
    {
        $request = new ListOrdersRequest([
            'user_id' => 99,
            'page' => 1,
            'perPage' => 15,
        ]);

        $orden1 = new PedidoProduccion();
        $orden1->forceFill(['id' => 1, 'prendas_count' => 0, 'epps_count' => 2]);

        $orden2 = new PedidoProduccion();
        $orden2->forceFill(['id' => 2, 'prendas_count' => 1, 'epps_count' => 0]);
        $paginator = new LengthAwarePaginator(
            new Collection([$orden1, $orden2]),
            2,
            15,
            1
        );

        $this->readService
            ->shouldReceive('listOrders')
            ->once()
            ->with($request)
            ->andReturn($paginator);

        $this->readService
            ->shouldReceive('esSoloEpp')
            ->once()
            ->with($orden1)
            ->andReturn(true);

        $this->readService
            ->shouldReceive('esSoloEpp')
            ->once()
            ->with($orden2)
            ->andReturn(false);

        $this->readService
            ->shouldReceive('listDistinctStates')
            ->once()
            ->andReturn(['PENDIENTE_SUPERVISOR', 'No iniciado']);

        $this->readService
            ->shouldReceive('getSelectedOrders')
            ->once()
            ->with(99)
            ->andReturn([1]);

        $response = $this->useCase->execute($request);

        $this->assertSame($paginator, $response->getOrdenes());
        $this->assertSame(['PENDIENTE_SUPERVISOR', 'No iniciado'], $response->getEstados());
        $this->assertSame([1], $response->getPedidosSeleccionados());
        $this->assertTrue($orden1->es_solo_epp);
        $this->assertFalse($orden2->es_solo_epp);
    }
}
