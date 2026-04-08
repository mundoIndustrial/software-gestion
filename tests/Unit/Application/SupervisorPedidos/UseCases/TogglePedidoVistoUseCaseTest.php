<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoRequest;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Application\SupervisorPedidos\UseCases\TogglePedidoVistoUseCase;
use Mockery as m;
use Tests\TestCase;

class TogglePedidoVistoUseCaseTest extends TestCase
{
    private PedidoProduccionReadService $readService;
    private TogglePedidoVistoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new TogglePedidoVistoUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_desmarca_pedido_como_visto(): void
    {
        $request = new TogglePedidoVistoRequest(9, 2);

        $this->readService
            ->shouldReceive('togglePedidoVisto')
            ->once()
            ->with(9, 2)
            ->andReturn(false);

        $response = $this->useCase->execute($request);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isVisto());
    }
}

