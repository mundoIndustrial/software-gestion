<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

class ConfirmarPedidoUseCaseTest extends TestCase
{
    private ConfirmarPedidoUseCase $useCase;
    private PedidoRepository $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = m::mock(PedidoRepository::class);
        $this->useCase = new ConfirmarPedidoUseCase($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test: Confirmar pedido exitosamente
     */
    public function test_confirmar_pedido_exitosamente()
    {
        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido de prueba',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );

        $this->repositoryMock
            ->shouldReceive('porId')
            ->with(1)
            ->once()
            ->andReturn($pedido);

        $this->repositoryMock
            ->shouldReceive('guardar')
            ->once();

        $response = $this->useCase->ejecutar(1);

        $this->assertEquals(1, $response->clienteId);
        $this->assertEquals('CONFIRMADO', $response->estado);
        $this->assertEquals('Pedido confirmado exitosamente', $response->mensaje);
    }

    /**
     * Test: Lanzar excepción si pedido no existe
     */
    public function test_error_si_pedido_no_existe()
    {
        $this->repositoryMock
            ->shouldReceive('porId')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Pedido 999 no encontrado');

        $this->useCase->ejecutar(999);
    }
}
