<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ObtenerValoresFiltrosCotizacionesAsesorUseCase;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use Mockery as m;
use Tests\TestCase;

class ObtenerValoresFiltrosCotizacionesAsesorUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_calcula_valores_unicos_para_filtros(): void
    {
        $repository = m::mock(CotizacionRepositoryInterface::class);
        $handler = new ListarCotizacionesHandler($repository);
        $useCase = new ObtenerValoresFiltrosCotizacionesAsesorUseCase($handler);

        $repository->shouldReceive('findByUserId')
            ->once()
            ->andReturn([
                [
                    'id' => 101,
                    'usuario_id' => 5,
                    'created_at' => '2026-03-27 10:00:00',
                    'fecha_inicio' => '2026-03-27 10:00:00',
                    'numero_cotizacion' => 'COT-001',
                    'cliente' => 'Cliente A',
                    'tipo' => 'PL',
                    'estado' => 'PENDIENTE',
                    'es_borrador' => false,
                ],
                [
                    'id' => 102,
                    'usuario_id' => 5,
                    'created_at' => '2026-03-27 15:00:00',
                    'fecha_inicio' => '2026-03-27 15:00:00',
                    'numero_cotizacion' => 'COT-002',
                    'cliente' => 'Cliente B',
                    'tipo' => 'L',
                    'estado' => 'APROBADA_CONTADOR',
                    'es_borrador' => false,
                ],
            ]);

        $resultado = $useCase->ejecutar(5);

        $this->assertSame(2, $resultado['total']);
        $this->assertSame(['27/03/2026'], $resultado['fechas']);
        $this->assertSame(['COT-001', 'COT-002'], $resultado['codigos']);
        $this->assertSame(['Cliente A', 'Cliente B'], $resultado['clientes']);
        $this->assertSame(['Combinada', 'Logo'], $resultado['tipos']);
        $this->assertSame(['PENDIENTE', 'APROBADA_CONTADOR'], $resultado['estados']);
    }
}
