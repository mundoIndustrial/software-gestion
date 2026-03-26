<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ResolverPedidoIdAsesorUseCase;
use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\ReadModels\PedidoNumeroRef;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use DomainException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ResolverPedidoIdAsesorUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_resuelve_por_id_si_pertenece_al_asesor(): void
    {
        $readRepo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ResolverPedidoIdAsesorUseCase($readRepo);

        $readRepo->shouldReceive('obtenerPorIdYAsesor')
            ->once()
            ->with(45, 9)
            ->andReturn(new PedidoBorradorRef(
                pedidoId: 45,
                asesorId: 9,
                numeroPedido: null,
                estado: 'Borrador',
                cliente: 'Cliente'
            ));

        $this->assertSame(45, $useCase->ejecutar(45, 9));
    }

    public function test_resuelve_por_numero_si_corresponde_al_asesor(): void
    {
        $readRepo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ResolverPedidoIdAsesorUseCase($readRepo);

        $readRepo->shouldReceive('obtenerPorIdYAsesor')->once()->with(5001, 12)->andReturn(null);
        $readRepo->shouldReceive('findByNumeroPedido')
            ->once()
            ->with('5001')
            ->andReturn(new PedidoNumeroRef(
                pedidoId: 88,
                numeroPedido: 5001,
                clienteId: 4,
                asesorId: 12,
                estado: 'Pendiente'
            ));

        $this->assertSame(88, $useCase->ejecutar('5001', 12));
    }

    public function test_falla_si_el_pedido_no_pertenece_al_asesor(): void
    {
        $readRepo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ResolverPedidoIdAsesorUseCase($readRepo);

        $readRepo->shouldReceive('obtenerPorIdYAsesor')->once()->with(7001, 3)->andReturn(null);
        $readRepo->shouldReceive('findByNumeroPedido')
            ->once()
            ->with('7001')
            ->andReturn(new PedidoNumeroRef(
                pedidoId: 91,
                numeroPedido: 7001,
                clienteId: 8,
                asesorId: 99,
                estado: 'Pendiente'
            ));

        $this->expectException(DomainException::class);
        $useCase->ejecutar('7001', 3);
    }
}
