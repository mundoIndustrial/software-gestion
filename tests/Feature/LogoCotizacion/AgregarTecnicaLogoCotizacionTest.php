<?php

namespace Tests\Feature\LogoCotizacion;

use App\Domain\LogoCotizacion\Entities\TecnicaLogoCotizacion;
use App\Domain\LogoCotizacion\Entities\PrendaTecnica;
use App\Domain\LogoCotizacion\ValueObjects\TipoTecnica;
use App\Domain\LogoCotizacion\ValueObjects\UbicacionPrenda;
use App\Domain\LogoCotizacion\ValueObjects\Talla;
use App\Models\LogoCotizacion;
use App\Models\TipoLogoCotizacion;
use Tests\TestCase;

class AgregarTecnicaLogoCotizacionTest extends TestCase
{
    protected $logoCotizacion;
    protected $tipoTecnica;

    public function setUp(): void
    {
        parent::setUp();
        
        // Crear cotización de prueba
        $this->logoCotizacion = LogoCotizacion::factory()->create();
        
        // Obtener tipo de técnica (BORDADO)
        $this->tipoTecnica = TipoLogoCotizacion::where('codigo', 'BORDADO')->first();
    }

    /** @test */
    public function puede_crear_tecnica_logo_cotizacion()
    {
        // Arrange
        $tipoTecnica = TipoTecnica::bordado();
        
        // Act
        $tecnica = TecnicaLogoCotizacion::crear(
            $this->logoCotizacion->id,
            $tipoTecnica,
            'Observaciones de prueba'
        );
        
        // Assert
        $this->assertInstanceOf(TecnicaLogoCotizacion::class, $tecnica);
        $this->assertEquals($this->logoCotizacion->id, $tecnica->obtenerLogoCotizacionId());
        $this->assertTrue($tipoTecnica->esIgual($tecnica->obtenerTipoTecnica()));
    }

    /** @test */
    public function puede_agregar_prenda_a_tecnica()
    {
        // Arrange
        $tecnica = TecnicaLogoCotizacion::crear(
            $this->logoCotizacion->id,
            TipoTecnica::bordado(),
            'Observaciones'
        );
        
        // Act
        $prenda = PrendaTecnica::crear(
            'CAMISETA',
            'Bordado frontal',
            [UbicacionPrenda::pecho()],
            [Talla::medium()]
        );
        
        $tecnica->agregarPrenda($prenda);
        
        // Assert
        $this->assertCount(1, $tecnica->obtenerPrendas());
        $this->assertEquals('CAMISETA', $tecnica->obtenerPrendas()[0]->obtenerNombre());
    }

    /** @test */
    public function puede_agregar_multiples_prendas()
    {
        // Arrange
        $tecnica = TecnicaLogoCotizacion::crear(
            $this->logoCotizacion->id,
            TipoTecnica::estampado(),
            'Estampado en pecho'
        );
        
        // Act
        $prendas = [
            PrendaTecnica::crear('CAMISETA', 'Estampado frontal', 
                [UbicacionPrenda::pecho()], [Talla::large()]),
            PrendaTecnica::crear('SUDADERA', 'Estampado espalda', 
                [UbicacionPrenda::espalda()], [Talla::xlarge()]),
        ];
        
        foreach ($prendas as $prenda) {
            $tecnica->agregarPrenda($prenda);
        }
        
        // Assert
        $this->assertCount(2, $tecnica->obtenerPrendas());
    }

    /** @test */
    public function valida_que_prenda_tenga_nombre()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        PrendaTecnica::crear('', 'Descripción', [UbicacionPrenda::pecho()], [Talla::medium()]);
    }

    /** @test */
    public function valida_que_prenda_tenga_ubicaciones()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        PrendaTecnica::crear('CAMISETA', 'Descripción', [], [Talla::medium()]);
    }

    /** @test */
    public function valida_que_prenda_tenga_tallas()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        PrendaTecnica::crear('CAMISETA', 'Descripción', [UbicacionPrenda::pecho()], []);
    }

    /** @test */
    public function tipo_tecnica_bordado_es_valido()
    {
        // Act
        $tipo = TipoTecnica::bordado();
        
        // Assert
        $this->assertEquals('BORDADO', $tipo->obtenerCodigo());
    }

    /** @test */
    public function tipo_tecnica_estampado_es_valido()
    {
        // Act
        $tipo = TipoTecnica::estampado();
        
        // Assert
        $this->assertEquals('ESTAMPADO', $tipo->obtenerCodigo());
    }

    /** @test */
    public function tipo_tecnica_sublimado_es_valido()
    {
        // Act
        $tipo = TipoTecnica::sublimado();
        
        // Assert
        $this->assertEquals('SUBLIMADO', $tipo->obtenerCodigo());
    }

    /** @test */
    public function tipo_tecnica_dtf_es_valido()
    {
        // Act
        $tipo = TipoTecnica::dtf();
        
        // Assert
        $this->assertEquals('DTF', $tipo->obtenerCodigo());
    }

    /** @test */
    public function ubicacion_prenda_pecho_es_valida()
    {
        // Act
        $ubicacion = UbicacionPrenda::pecho();
        
        // Assert
        $this->assertEquals('PECHO', $ubicacion->obtenerValor());
    }

    /** @test */
    public function talla_medium_es_valida()
    {
        // Act
        $talla = Talla::medium();
        
        // Assert
        $this->assertEquals('M', $talla->obtenerValor());
    }

    /** @test */
    public function puede_comparar_tipos_tecnicos()
    {
        // Act
        $tipo1 = TipoTecnica::bordado();
        $tipo2 = TipoTecnica::bordado();
        $tipo3 = TipoTecnica::estampado();
        
        // Assert
        $this->assertTrue($tipo1->esIgual($tipo2));
        $this->assertFalse($tipo1->esIgual($tipo3));
    }

    /** @test */
    public function puede_obtener_todas_las_ubicaciones()
    {
        // Act
        $ubicaciones = [
            UbicacionPrenda::pecho(),
            UbicacionPrenda::espalda(),
            UbicacionPrenda::manga(),
        ];
        
        // Assert
        $this->assertCount(3, $ubicaciones);
        $this->assertEquals('PECHO', $ubicaciones[0]->obtenerValor());
        $this->assertEquals('ESPALDA', $ubicaciones[1]->obtenerValor());
        $this->assertEquals('MANGA', $ubicaciones[2]->obtenerValor());
    }

    /** @test */
    public function puede_obtener_todas_las_tallas()
    {
        // Act
        $tallas = [
            Talla::extraSmall(),
            Talla::small(),
            Talla::medium(),
            Talla::large(),
            Talla::xlarge(),
            Talla::xxlarge(),
        ];
        
        // Assert
        $this->assertCount(6, $tallas);
    }
}
