<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ConfirmarCorreccionPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use DomainException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ConfirmarCorreccionPedidoUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_confirma_correccion_cuando_estado_es_devuelto(): void
    {
        $repo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ConfirmarCorreccionPedidoUseCase($repo);

        $repo->shouldReceive('obtenerPedidoPorId')
            ->once()
            ->with(10)
            ->andReturn([
                'id' => 10,
                'numero_pedido' => 5001,
                'estado' => 'DEVUELTO_A_ASESORA',
                'novedades' => 'previo',
            ]);

        $repo->shouldReceive('actualizarDatosBasicos')
            ->once()
            ->with(10, m::on(function (array $datos): bool {
                return $datos['estado'] === 'PENDIENTE_SUPERVISOR'
                    && array_key_exists('novedades', $datos)
                    && str_contains($datos['novedades'], 'CONFIRMACIÓN DE CORRECCIÓN DE PEDIDO');
            }));

        $repo->shouldReceive('obtenerPedidoPorId')
            ->once()
            ->with(10)
            ->andReturn([
                'id' => 10,
                'numero_pedido' => 5001,
                'estado' => 'PENDIENTE_SUPERVISOR',
                'novedades' => 'actualizado',
            ]);

        $resultado = $useCase->ejecutar(10, 'Asesor Test');

        $this->assertSame(10, $resultado['pedido_id']);
        $this->assertSame(5001, $resultado['numero_pedido']);
        $this->assertSame('PENDIENTE_SUPERVISOR', $resultado['estado']);
    }

    public function test_falla_si_pedido_no_existe(): void
    {
        $repo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ConfirmarCorreccionPedidoUseCase($repo);

        $repo->shouldReceive('obtenerPedidoPorId')->once()->with(99)->andReturn(null);

        $this->expectException(DomainException::class);
        $useCase->ejecutar(99, 'Asesor Test');
    }
}
