<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ToggleOrderVisibilityRequest;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Application\SupervisorPedidos\UseCases\ToggleOrderVisibilityUseCase;
use Illuminate\Support\Facades\Auth;
use Mockery as m;
use Tests\TestCase;

class ToggleOrderVisibilityUseCaseTest extends TestCase
{
    private PedidoProduccionReadService $readService;
    private ToggleOrderVisibilityUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new ToggleOrderVisibilityUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_oculta_pedido_usando_actor_autenticado(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn((object) ['id' => 55]);

        $request = new ToggleOrderVisibilityRequest(123, true);

        $this->readService
            ->shouldReceive('setOrderVisibility')
            ->once()
            ->with(123, true, 55);

        $response = $this->useCase->execute($request);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('Pedido ocultado correctamente', $response->getMessage());
    }
}

