<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\EliminarBorradorAsesorUseCase;
use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use DomainException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class EliminarBorradorAsesorUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_elimina_borrador_valido(): void
    {
        $readRepo = m::mock(PedidoProduccionReadRepository::class);
        $repo = m::mock(PedidoRepository::class);
        $useCase = new EliminarBorradorAsesorUseCase($readRepo, $repo);

        $readRepo->shouldReceive('obtenerPorIdYAsesor')
            ->once()
            ->with(10, 7)
            ->andReturn(new PedidoBorradorRef(
                pedidoId: 10,
                asesorId: 7,
                numeroPedido: null,
                estado: 'Borrador',
                cliente: 'Cliente'
            ));

        $repo->shouldReceive('eliminar')
            ->once()
            ->with(10);

        $useCase->ejecutar(10, 7);
        $this->assertTrue(true);
    }

    public function test_falla_si_no_es_borrador_sin_numero(): void
    {
        $readRepo = m::mock(PedidoProduccionReadRepository::class);
        $repo = m::mock(PedidoRepository::class);
        $useCase = new EliminarBorradorAsesorUseCase($readRepo, $repo);

        $readRepo->shouldReceive('obtenerPorIdYAsesor')
            ->once()
            ->andReturn(new PedidoBorradorRef(
                pedidoId: 10,
                asesorId: 7,
                numeroPedido: 123,
                estado: 'Pendiente',
                cliente: 'Cliente'
            ));

        $this->expectException(DomainException::class);
        $useCase->ejecutar(10, 7);
    }
}
