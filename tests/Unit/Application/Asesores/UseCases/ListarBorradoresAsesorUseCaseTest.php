<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ListarBorradoresAsesorUseCase;
use App\Domain\Pedidos\ReadModels\PaginatedPedidosResult;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ListarBorradoresAsesorUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_lista_borradores_de_asesor_con_filtros_esperados(): void
    {
        $repo = m::mock(PedidoProduccionReadRepository::class);
        $useCase = new ListarBorradoresAsesorUseCase($repo);

        $repo->shouldReceive('obtenerPedidosAsesor')
            ->once()
            ->with(m::on(function (array $filtros): bool {
                return $filtros['asesor_id'] === 12
                    && $filtros['estado'] === 'Borrador'
                    && $filtros['sin_numero'] === true
                    && $filtros['page'] === 2
                    && $filtros['per_page'] === 15;
            }))
            ->andReturn(new PaginatedPedidosResult(
                items: [(object) ['id' => 1]],
                total: 1,
                perPage: 15,
                currentPage: 2,
                path: '/asesores/pedidos/borradores',
                query: []
            ));

        $resultado = $useCase->ejecutar(12, 2, 15);

        $this->assertSame(1, $resultado->total());
        $this->assertCount(1, $resultado->items());
    }
}
