<?php

namespace Tests\Unit\Application\SupervisorPedidos\Services;

use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\Services\UpdateOrderWriteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Mockery as m;
use Tests\TestCase;

class UpdateOrderWriteServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_propaga_error_cuando_no_encuentra_pedido(): void
    {
        $service = m::mock(UpdateOrderWriteService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('findOrderForUpdate')
            ->once()
            ->with(404)
            ->andThrow(new ModelNotFoundException());

        $dto = new UpdateOrderRequest(orderId: 404, cliente: 'X');
        $request = Request::create('/fake', 'POST');

        $this->expectException(ModelNotFoundException::class);

        $service->update($dto, $request);
    }
}
