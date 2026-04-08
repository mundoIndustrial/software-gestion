<?php

namespace Tests\Unit\Application\Operario\UseCases;

use App\Application\Operario\UseCases\CompletarReciboOperarioUseCase;
use App\Domain\Operario\Repositories\ConsecutivoReciboPedidoRepository;
use App\Domain\Operario\Repositories\ProcesoPrendaRepository;
use App\Domain\Operario\Services\ReciboOperarioWorkflow;
use Illuminate\Support\Facades\Auth;
use Mockery as m;
use Tests\TestCase;

class CompletarReciboOperarioUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function test_retorna_403_si_el_usuario_no_tiene_rol_autorizado(): void
    {
        $user = m::mock();
        $user->shouldReceive('hasRole')->andReturnFalse();

        Auth::shouldReceive('user')->once()->andReturn($user);

        $recibos = m::mock(ConsecutivoReciboPedidoRepository::class);
        $procesos = m::mock(ProcesoPrendaRepository::class);
        $workflow = m::mock(ReciboOperarioWorkflow::class);

        $useCase = new CompletarReciboOperarioUseCase($recibos, $procesos, $workflow);
        $result = $useCase->execute(123);

        $this->assertFalse($result->success);
        $this->assertSame('Rol no autorizado', $result->message);
        $this->assertSame(403, $result->statusCode);
    }
}

