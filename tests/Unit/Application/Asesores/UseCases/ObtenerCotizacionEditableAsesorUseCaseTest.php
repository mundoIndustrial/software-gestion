<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ObtenerCotizacionEditableAsesorUseCase;
use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;
use DomainException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ObtenerCotizacionEditableAsesorUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_retorna_cotizacion_si_pertenece_al_asesor(): void
    {
        $repo = m::mock(CotizacionDetalleRepositoryInterface::class);
        $useCase = new ObtenerCotizacionEditableAsesorUseCase($repo);

        $repo->shouldReceive('obtenerResumenCotizacion')
            ->once()
            ->with(11)
            ->andReturn([
                'id' => 11,
                'asesor_id' => 5,
                'tipo_codigo' => 'EPP',
            ]);

        $resultado = $useCase->ejecutar(11, 5);

        $this->assertSame(11, $resultado['id']);
        $this->assertSame('EPP', $resultado['tipo_codigo']);
    }

    public function test_falla_si_no_pertenece_al_asesor(): void
    {
        $repo = m::mock(CotizacionDetalleRepositoryInterface::class);
        $useCase = new ObtenerCotizacionEditableAsesorUseCase($repo);

        $repo->shouldReceive('obtenerResumenCotizacion')
            ->once()
            ->with(11)
            ->andReturn([
                'id' => 11,
                'asesor_id' => 9,
            ]);

        $this->expectException(DomainException::class);
        $useCase->ejecutar(11, 5);
    }
}
