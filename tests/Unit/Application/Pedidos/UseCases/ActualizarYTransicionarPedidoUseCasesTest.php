<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use App\Application\Pedidos\UseCases\ActualizarDescripcionPedidoUseCase;
use App\Application\Pedidos\UseCases\IniciarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CompletarPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

class ActualizarYTransicionarPedidoUseCasesTest extends TestCase
{
    private PedidoRepository $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = m::mock(PedidoRepository::class);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test: Actualizar descripción de un pedido
     */
    public function test_actualizar_descripcion_pedido()
    {
        $useCase = new ActualizarDescripcionPedidoUseCase($this->repositoryMock);

        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Descripción original',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );
        $pedido->setId(1);

        $this->repositoryMock
            ->shouldReceive('porId')
            ->with(1)
            ->once()
            ->andReturn($pedido);

        $this->repositoryMock
            ->shouldReceive('guardar')
            ->once();

        $response = $useCase->ejecutar(1, 'Nueva descripción');

        $this->assertEquals('Nueva descripción', $response->descripcion);
        $this->assertEquals('Descripción actualizada exitosamente', $response->mensaje);
    }

    /**
     * Test: Iniciar producción desde CONFIRMADO
     */
    public function test_iniciar_produccion_desde_confirmado()
    {
        $useCase = new IniciarProduccionPedidoUseCase($this->repositoryMock);

        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );
        $pedido->setId(1);
        $pedido->confirmar(); // Pasar a CONFIRMADO primero

        $this->repositoryMock
            ->shouldReceive('porId')
            ->with(1)
            ->once()
            ->andReturn($pedido);

        $this->repositoryMock
            ->shouldReceive('guardar')
            ->once();

        $response = $useCase->ejecutar(1);

        $this->assertEquals('EN_PRODUCCION', $response->estado);
        $this->assertEquals('Producción iniciada exitosamente', $response->mensaje);
    }

    /**
     * Test: Completar pedido desde EN_PRODUCCION
     */
    public function test_completar_pedido_desde_en_produccion()
    {
        $useCase = new CompletarPedidoUseCase($this->repositoryMock);

        $pedido = PedidoAggregate::crear(
            clienteId: 1,
            descripcion: 'Pedido',
            prendasData: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ]
        );
        $pedido->setId(1);
        $pedido->confirmar();
        $pedido->iniciarProduccion();

        $this->repositoryMock
            ->shouldReceive('porId')
            ->with(1)
            ->once()
            ->andReturn($pedido);

        $this->repositoryMock
            ->shouldReceive('guardar')
            ->once();

        $response = $useCase->ejecutar(1);

        $this->assertEquals('COMPLETADO', $response->estado);
        $this->assertEquals('Pedido completado exitosamente', $response->mensaje);
    }

    /**
     * Test: Error al actualizar con descripción vacía
     */
    public function test_error_descripcion_vacia()
    {
        $useCase = new ActualizarDescripcionPedidoUseCase($this->repositoryMock);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Descripción no puede estar vacía');

        $useCase->ejecutar(1, '');
    }
}
