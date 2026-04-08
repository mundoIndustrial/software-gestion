<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleNewsVistoRequest;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Application\SupervisorPedidos\UseCases\ToggleNewsVistoUseCase;
use Mockery as m;
use Tests\TestCase;

class ToggleNewsVistoUseCaseTest extends TestCase
{
    private PedidoProduccionReadService $readService;
    private ToggleNewsVistoUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new ToggleNewsVistoUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_marca_noticia_como_vista(): void
    {
        $request = new ToggleNewsVistoRequest(5, 7);

        $this->readService
            ->shouldReceive('toggleNewsVisto')
            ->once()
            ->with(5, 7)
            ->andReturn(true);

        $response = $this->useCase->execute($request);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isVisto());
    }
}

