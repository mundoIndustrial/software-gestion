<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadRequest;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Application\SupervisorPedidos\UseCases\MarkAllNotificationsAsReadUseCase;
use Mockery as m;
use Tests\TestCase;

class MarkAllNotificationsAsReadUseCaseTest extends TestCase
{
    private PedidoProduccionReadService $readService;
    private MarkAllNotificationsAsReadUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new MarkAllNotificationsAsReadUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_marca_todas_las_notificaciones_como_leidas(): void
    {
        $request = new MarkNotificationsAsReadRequest(10);

        $this->readService
            ->shouldReceive('markAllNotificationsAsRead')
            ->once()
            ->with(10)
            ->andReturn(4);

        $response = $this->useCase->execute($request);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(4, $response->getNotificationsMarked());
    }
}

