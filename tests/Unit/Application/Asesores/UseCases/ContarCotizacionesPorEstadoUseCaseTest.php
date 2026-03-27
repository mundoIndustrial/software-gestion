<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ContarCotizacionesPorEstadoUseCase;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ContarCotizacionesPorEstadoUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_cuenta_cotizaciones_por_estado(): void
    {
        $repo = m::mock(CotizacionRepositoryInterface::class);
        $useCase = new ContarCotizacionesPorEstadoUseCase($repo);

        $repo->shouldReceive('countByEstado')
            ->once()
            ->with('APROBADA_CONTADOR')
            ->andReturn(7);

        $resultado = $useCase->ejecutar('APROBADA_CONTADOR');

        $this->assertSame(7, $resultado);
    }
}

