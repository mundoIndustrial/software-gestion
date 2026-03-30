<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use App\Application\Pedidos\Exceptions\ObtenerDetalleCompletoException;
use App\Application\Pedidos\Services\PedidoAuthorizationService;
use App\Application\Pedidos\Services\PedidoFiltroService;
use App\Application\Pedidos\UseCases\ObtenerDetalleCompletoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Mockery as m;
use Tests\TestCase;

class ObtenerDetalleCompletoUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_lanza_domain_exception_si_el_pedido_no_existe(): void
    {
        $obtenerPedidoUseCase = m::mock(ObtenerPedidoUseCase::class);
        $authService = m::mock(PedidoAuthorizationService::class);
        $filtroService = m::mock(PedidoFiltroService::class);
        $readService = m::mock(PedidoDetalleReadService::class);

        $readService
            ->shouldReceive('findPedidoByIdOrNumero')
            ->once()
            ->with(99999)
            ->andReturn(null);

        $useCase = new ObtenerDetalleCompletoUseCase(
            $obtenerPedidoUseCase,
            $authService,
            $filtroService,
            $readService
        );

        $this->expectException(ObtenerDetalleCompletoException::class);
        $this->expectExceptionMessage('Pedido 99999 no encontrado');

        $useCase->ejecutar(99999);
    }
}
