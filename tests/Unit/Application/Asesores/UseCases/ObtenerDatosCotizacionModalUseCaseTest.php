<?php

namespace Tests\Unit\Application\Asesores\UseCases;

use App\Application\Asesores\UseCases\ObtenerDatosCotizacionModalUseCase;
use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ObtenerDatosCotizacionModalUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_retorna_null_si_no_existe_cotizacion(): void
    {
        $repo = m::mock(CotizacionDetalleRepositoryInterface::class);
        $useCase = new ObtenerDatosCotizacionModalUseCase($repo);

        $repo->shouldReceive('obtenerCotizacionParaModal')
            ->once()
            ->with(99)
            ->andReturn(null);

        $resultado = $useCase->ejecutar(99);

        $this->assertNull($resultado);
    }

    public function test_mapea_datos_para_modal(): void
    {
        $repo = m::mock(CotizacionDetalleRepositoryInterface::class);
        $useCase = new ObtenerDatosCotizacionModalUseCase($repo);

        $prenda = new class {
            public int $id = 1;
            public string $nombre_producto = 'Camisa';
            public int $cantidad = 12;
            public string $descripcion = 'Descripcion base';
            public Collection $fotos;
            public Collection $telas;
            public Collection $telaFotos;
            public Collection $tallas;
            public Collection $variantes;

            public function __construct()
            {
                $this->fotos = new Collection([(object) ['url' => '/storage/foto-1.webp']]);
                $this->telas = new Collection([
                    (object) [
                        'id' => 5,
                        'color' => 'Azul',
                        'tela' => (object) ['nombre' => 'Drill', 'referencia' => 'DR-10'],
                        'url' => '/storage/tela-1.webp',
                        'ruta_webp' => 'storage/tela-1.webp',
                    ],
                ]);
                $this->telaFotos = new Collection([(object) ['url' => '/storage/tela-foto-1.webp']]);
                $this->tallas = new Collection([(object) ['id' => 8, 'talla' => 'M', 'cantidad' => 6]]);
                $this->variantes = new Collection([
                    (object) [
                        'id' => 9,
                        'tipo_prenda' => 'Camisa',
                        'es_jean_pantalon' => false,
                        'tipo_jean_pantalon' => null,
                        'genero_id' => 1,
                        'color' => 'Azul',
                        'tiene_bolsillos' => true,
                        'aplica_manga' => true,
                        'tipo_manga' => 'Larga',
                        'aplica_broche' => false,
                        'tipo_broche_id' => null,
                        'tiene_reflectivo' => false,
                        'descripcion_adicional' => null,
                        'telas_multiples' => null,
                    ],
                ]);
            }

            public function generarDescripcionDetallada(int $index): string
            {
                return "Formato {$index}";
            }
        };

        $cotizacion = (object) [
            'id' => 50,
            'numero_cotizacion' => 'COT-50',
            'asesor' => (object) ['name' => 'Asesora 1'],
            'empresa_solicitante' => 'Empresa X',
            'cliente' => (object) ['nombre' => 'Cliente Y'],
            'created_at' => '2026-03-27 10:00:00',
            'estado' => 'PENDIENTE',
            'prendas' => new Collection([$prenda]),
        ];

        $repo->shouldReceive('obtenerCotizacionParaModal')
            ->once()
            ->with(50)
            ->andReturn($cotizacion);

        $resultado = $useCase->ejecutar(50);

        $this->assertSame(50, $resultado['cotizacion']['id']);
        $this->assertSame('COT-50', $resultado['cotizacion']['numero_cotizacion']);
        $this->assertCount(1, $resultado['prendas_cotizaciones']);
        $this->assertSame('Formato 1', $resultado['prendas_cotizaciones'][0]['descripcion_formateada']);
    }
}

