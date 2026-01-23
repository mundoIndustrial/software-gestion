<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

class ListarPedidosPorClienteUseCaseTest extends TestCase
{
    private ListarPedidosPorClienteUseCase $useCase;
    private PedidoRepository $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = m::mock(PedidoRepository::class);
        $this->useCase = new ListarPedidosPorClienteUseCase($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test: Listar pedidos de un cliente
     */
    public function test_listar_pedidos_del_cliente()
    {
        $pedido1 = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido 1',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );
        $pedido1->setId(1);

        $pedido2 = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido 2',
            prendasData: [
                [
                    'prenda_id' => 2,
                    'descripcion' => 'Pantalón',
                    'cantidad' => 5,
                    'tallas' => ['DAMA' => ['S' => 5]],
                ]
            ]
        );
        $pedido2->setId(2);

        $this->repositoryMock
            ->shouldReceive('porClienteId')
            ->with(1)
            ->once()
            ->andReturn([$pedido1, $pedido2]);

        $response = $this->useCase->ejecutar(1);

        $this->assertCount(2, $response);
        $this->assertEquals(1, $response[0]->id);
        $this->assertEquals(2, $response[1]->id);
    }

    /**
     * Test: Listar pedidos vacío si cliente no tiene pedidos
     */
    public function test_listar_pedidos_vacio_si_no_hay()
    {
        $this->repositoryMock
            ->shouldReceive('porClienteId')
            ->with(999)
            ->once()
            ->andReturn([]);

        $response = $this->useCase->ejecutar(999);

        $this->assertCount(0, $response);
    }
}
