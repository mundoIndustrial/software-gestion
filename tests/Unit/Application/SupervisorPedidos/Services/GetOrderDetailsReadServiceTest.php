<?php

namespace Tests\Unit\Application\SupervisorPedidos\Services;

use App\Application\Pedidos\Services\PrendaPedidoDescriptionFormatter;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\Services\GetOrderDetailsReadService;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use Mockery as m;
use Tests\TestCase;

class GetOrderDetailsReadServiceTest extends TestCase
{
    private OrderRepository $orderRepository;
    private PrendaPedidoDescriptionFormatter $formatter;
    private GetOrderDetailsReadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderRepository = m::mock(OrderRepository::class);
        $this->formatter = m::mock(PrendaPedidoDescriptionFormatter::class);
        $this->service = new GetOrderDetailsReadService($this->orderRepository, $this->formatter);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_lanza_error_si_pedido_no_existe_en_repositorio(): void
    {
        $request = new GetOrderDetailsRequest(999);

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->withArgs(function (OrderId $id) {
                return $id->value() === 999;
            })
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Pedido #999 no encontrado');

        $this->service->getDetails($request);
    }
}
