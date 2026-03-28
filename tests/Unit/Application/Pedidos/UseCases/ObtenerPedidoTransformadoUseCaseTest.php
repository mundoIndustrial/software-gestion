<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Application\Pedidos\UseCases\ObtenerPedidoTransformadoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use Mockery as m;
use Tests\TestCase;

class ObtenerPedidoTransformadoUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_retorna_epps_transformados_vacio_si_no_hay_pedido_en_read_service(): void
    {
        $obtenerPedidoUseCase = m::mock(ObtenerPedidoUseCase::class);
        $readService = m::mock(PedidoDetalleReadService::class);

        $obtenerPedidoUseCase
            ->shouldReceive('ejecutar')
            ->once()
            ->with(123)
            ->andReturn(new PedidoResponseDTO(
                id: 123,
                numero: '123',
                clienteId: 1,
                estado: 'Pendiente',
                descripcion: 'Pedido',
                totalPrendas: 0,
                totalArticulos: 0,
                prendas: []
            ));

        $readService
            ->shouldReceive('findPedidoById')
            ->twice()
            ->with(123)
            ->andReturn(null);

        $useCase = new ObtenerPedidoTransformadoUseCase($obtenerPedidoUseCase, $readService);
        $response = $useCase->ejecutar(123);

        $this->assertSame([], $response->getEppsTransformados());
    }
}

