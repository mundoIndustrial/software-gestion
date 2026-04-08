<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Application\SupervisorPedidos\UseCases\GetNotificationsUseCase;
use Illuminate\Auth\AuthManager;
use Mockery as m;
use Tests\TestCase;

class GetNotificationsUseCaseTest extends TestCase
{
    private AuthManager $auth;
    private PedidoProduccionReadService $readService;
    private GetNotificationsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = m::mock(AuthManager::class);
        $this->readService = m::mock(PedidoProduccionReadService::class);
        $this->useCase = new GetNotificationsUseCase($this->auth, $this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_retorna_estado_falso_si_no_hay_usuario(): void
    {
        $this->auth->shouldReceive('user')->once()->andReturn(null);

        $response = $this->useCase->execute()->toArray();

        $this->assertFalse($response['success']);
        $this->assertSame(0, $response['totalGeneral']);
    }

    public function test_retorna_notificaciones_desde_read_service(): void
    {
        $this->auth->shouldReceive('user')->once()->andReturn((object) ['id' => 9]);
        $this->readService
            ->shouldReceive('getSupervisorNotificationsData')
            ->once()
            ->with(9)
            ->andReturn([
                'notifications' => collect([['id' => 1]]),
                'news' => collect([['id' => 2]]),
                'totalPending' => 1,
                'totalOrdersNotViewed' => 1,
                'totalNews' => 1,
                'totalNewsNotViewed' => 0,
                'totalGeneral' => 1,
            ]);

        $response = $this->useCase->execute()->toArray();

        $this->assertTrue($response['success']);
        $this->assertSame(1, $response['totalGeneral']);
        $this->assertCount(1, $response['notificaciones']);
    }
}

