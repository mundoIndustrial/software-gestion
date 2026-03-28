<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\Services\UpdateOrderWriteService;
use App\Application\SupervisorPedidos\UseCases\UpdateOrderUseCase;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use Mockery as m;
use Tests\TestCase;

class UpdateOrderUseCaseTest extends TestCase
{
    private UpdateOrderWriteService $writeService;
    private UpdateOrderUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeService = m::mock(UpdateOrderWriteService::class);
        $this->useCase = new UpdateOrderUseCase($this->writeService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_actualiza_pedido_con_exito(): void
    {
        $dto = new UpdateOrderRequest(orderId: 10, cliente: 'Cliente X');
        $httpRequest = Request::create('/fake', 'POST');
        $updated = new PedidoProduccion();
        $updated->forceFill(['id' => 10]);

        $this->writeService
            ->shouldReceive('update')
            ->once()
            ->with($dto, $httpRequest)
            ->andReturn($updated);

        $response = $this->useCase->execute($dto, $httpRequest);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('Pedido actualizado correctamente', $response->getMessage());
        $this->assertSame($updated, $response->getOrden());
    }

    public function test_retorna_error_controlado_si_falla_actualizacion(): void
    {
        $dto = new UpdateOrderRequest(orderId: 11, cliente: 'Cliente Y');
        $httpRequest = Request::create('/fake', 'POST');

        $this->writeService
            ->shouldReceive('update')
            ->once()
            ->with($dto, $httpRequest)
            ->andThrow(new \RuntimeException('fallo'));

        $response = $this->useCase->execute($dto, $httpRequest);

        $this->assertFalse($response->isSuccess());
        $this->assertStringContainsString('Error al actualizar el pedido', $response->getMessage());
    }
}
