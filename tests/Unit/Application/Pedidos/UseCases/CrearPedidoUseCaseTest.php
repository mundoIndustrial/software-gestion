<?php

namespace Tests\Unit\Application\Pedidos\UseCases;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;

class CrearPedidoUseCaseTest extends TestCase
{
    private CrearPedidoUseCase $useCase;
    private PedidoRepository $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = m::mock(PedidoRepository::class);
        $this->useCase = new CrearPedidoUseCase($this->repositoryMock);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test: Crear pedido exitosamente
     */
    public function test_crear_pedido_exitosamente()
    {
        $this->repositoryMock
            ->shouldReceive('guardar')
            ->once()
            ->andReturnUsing(function ($pedido) {
                // Simular que la BD asigna un ID
                $pedido->setId(1);
            });

        $dto = new CrearPedidoDTO(
            clienteId: 1,
            descripcion: 'Pedido de prueba',
            prendas: [
                [
                    'prenda_id' => 1,
                    'descripcion' => 'Camiseta',
                    'cantidad' => 10,
                    'tallas' => ['DAMA' => ['S' => 5, 'M' => 5]],
                ]
            ],
            observaciones: 'Observación'
        );

        $response = $this->useCase->ejecutar($dto);

        $this->assertNotNull($response->id);
        $this->assertEquals(1, $response->id);
        $this->assertEquals(1, $response->clienteId);
        $this->assertEquals('PENDIENTE', $response->estado);
        $this->assertEquals('Pedido creado exitosamente', $response->mensaje);
    }
}
