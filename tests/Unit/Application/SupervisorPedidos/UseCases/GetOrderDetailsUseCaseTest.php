<?php

namespace Tests\Unit\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\Services\GetOrderDetailsReadService;
use App\Application\SupervisorPedidos\UseCases\GetOrderDetailsUseCase;
use Mockery as m;
use Tests\TestCase;

class GetOrderDetailsUseCaseTest extends TestCase
{
    private GetOrderDetailsReadService $readService;
    private GetOrderDetailsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->readService = m::mock(GetOrderDetailsReadService::class);
        $this->useCase = new GetOrderDetailsUseCase($this->readService);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_retorna_detalle_desde_read_service(): void
    {
        $request = new GetOrderDetailsRequest(100);
        $expected = ['id' => 100, 'cliente' => 'ACME'];

        $this->readService
            ->shouldReceive('getDetails')
            ->once()
            ->with($request)
            ->andReturn($expected);

        $response = $this->useCase->execute($request);

        $this->assertSame($expected, $response->getOrderData());
    }
}

