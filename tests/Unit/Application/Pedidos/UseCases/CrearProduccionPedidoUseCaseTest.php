<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Domain\Pedidos\UseCases\CrearProduccionPedidoUseCaseContract;
use App\Services\PedidoEppService;
use Illuminate\Events\Dispatcher;
use Mockery as m;
use Tests\TestCase;

class CrearProduccionPedidoUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_implementa_contrato_y_se_puede_instanciar(): void
    {
        $dispatcher = m::mock(Dispatcher::class);
        $pedidoEppService = m::mock(PedidoEppService::class);

        $useCase = new CrearProduccionPedidoUseCase($dispatcher, $pedidoEppService);

        $this->assertInstanceOf(CrearProduccionPedidoUseCaseContract::class, $useCase);
    }
}
