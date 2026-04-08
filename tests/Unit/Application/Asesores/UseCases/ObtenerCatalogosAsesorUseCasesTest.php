<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ObtenerCatalogoColoresAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerCatalogoTelasAsesorUseCase;
use App\Application\Services\ColorGeneroMangaBrocheService;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ObtenerCatalogosAsesorUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_obtener_catalogo_telas(): void
    {
        $service = m::mock(ColorGeneroMangaBrocheService::class);
        $service->shouldReceive('obtenerTelas')
            ->once()
            ->andReturn([['id' => 1, 'nombre' => 'Drill', 'referencia' => 'DR-1']]);

        $useCase = new ObtenerCatalogoTelasAsesorUseCase($service);
        $resultado = $useCase->ejecutar();

        $this->assertCount(1, $resultado);
        $this->assertSame('Drill', $resultado[0]['nombre']);
    }

    public function test_obtener_catalogo_colores(): void
    {
        $service = m::mock(ColorGeneroMangaBrocheService::class);
        $service->shouldReceive('obtenerColores')
            ->once()
            ->andReturn([['id' => 2, 'nombre' => 'Azul', 'codigo' => '#0000FF']]);

        $useCase = new ObtenerCatalogoColoresAsesorUseCase($service);
        $resultado = $useCase->ejecutar();

        $this->assertCount(1, $resultado);
        $this->assertSame('Azul', $resultado[0]['nombre']);
    }
}

