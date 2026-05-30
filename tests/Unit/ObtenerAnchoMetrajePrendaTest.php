<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Application\Pedidos\UseCases\ObtenerAnchoMetrajePrendaUseCase;
use App\Models\PedidoProduccion;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ObtenerAnchoMetrajePrendaTest extends TestCase
{
    use RefreshDatabase;

    private ObtenerAnchoMetrajePrendaUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(ObtenerAnchoMetrajePrendaUseCase::class);
    }

    public function test_devuelve_vacio_sin_numero_recibo()
    {
        // Arrange: Crear pedido directamente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'TEST-001',
            'cliente_id' => 1,
            'estado' => 'ACTIVO',
        ]);
        
        // Act: Llamar sin numero_recibo
        $resultado = $this->useCase->ejecutar($pedido->id, 999);

        // Assert: Debe devolver vacío
        $this->assertNull($resultado->ancho);
        $this->assertNull($resultado->metraje);
        $this->assertNull($resultado->tipoModo);
        $this->assertEmpty($resultado->data);
    }

    public function test_devuelve_ancho_cuando_numero_recibo_coincide()
    {
        // Arrange: Crear pedido directamente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'TEST-002',
            'cliente_id' => 1,
            'estado' => 'ACTIVO',
        ]);
        
        // Crear ancho general CON numero_recibo específico
        PedidoAnchoGeneral::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => 505,
            'ancho' => '1.60',
            'metraje' => null,
            'tipo_modo' => 'normal',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Act: Llamar CON numero_recibo = 505
        $resultado = $this->useCase->ejecutar($pedido->id, 100, 505);

        // Assert: Debe devolver el ancho
        $this->assertEquals('1.60', $resultado->ancho);
        $this->assertNull($resultado->metraje);
        $this->assertEquals('normal', $resultado->tipoModo);
    }

    public function test_no_devuelve_ancho_con_numero_recibo_diferente()
    {
        // Arrange: Crear pedido directamente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'TEST-003',
            'cliente_id' => 1,
            'estado' => 'ACTIVO',
        ]);
        
        // Crear ancho general CON numero_recibo 505
        PedidoAnchoGeneral::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => 505,
            'ancho' => '1.60',
            'metraje' => null,
            'tipo_modo' => 'normal',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Act: Llamar CON numero_recibo = 506 (diferente)
        $resultado = $this->useCase->ejecutar($pedido->id, 100, 506);

        // Assert: Debe devolver vacío
        $this->assertNull($resultado->ancho);
        $this->assertNull($resultado->metraje);
        $this->assertEmpty($resultado->data);
    }

    public function test_devuelve_metrajes_por_color_filtrados()
    {
        // Arrange: Crear pedido directamente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'TEST-004',
            'cliente_id' => 1,
            'estado' => 'ACTIVO',
        ]);
        
        // Crear metrajes por color CON numero_recibo 505
        PedidoMetrajeColor::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => 505,
            'color' => 'AZUL',
            'metraje' => '2.50',
            'tipo_modo' => 'color',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Crear metraje CON numero_recibo 506 (diferente)
        PedidoMetrajeColor::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => 506,
            'color' => 'ROJO',
            'metraje' => '1.50',
            'tipo_modo' => 'color',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Act: Llamar CON numero_recibo = 505
        $resultado = $this->useCase->ejecutar($pedido->id, 100, 505);

        // Assert: Debe devolver solo el metraje AZUL (numero_recibo 505)
        $this->assertCount(1, $resultado->data);
        $this->assertEquals('AZUL', $resultado->data[0]['color']);
        $this->assertEquals('2.50', $resultado->data[0]['metraje']);
    }

    public function test_ignora_registros_sin_numero_recibo()
    {
        // Arrange: Crear pedido directamente
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'TEST-005',
            'cliente_id' => 1,
            'estado' => 'ACTIVO',
        ]);
        
        // Crear ancho CON numero_recibo NULL (antiguo)
        PedidoAnchoGeneral::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => null,  // NULL
            'ancho' => '1.50',
            'metraje' => null,
            'tipo_modo' => 'normal',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Crear ancho CON numero_recibo específico
        PedidoAnchoGeneral::create([
            'pedido_produccion_id' => $pedido->id,
            'prenda_pedido_id' => 100,
            'prenda_bodega_id' => null,
            'numero_recibo' => 505,
            'ancho' => '1.60',
            'metraje' => null,
            'tipo_modo' => 'normal',
            'creado_por' => 1,
            'actualizado_por' => 1,
        ]);

        // Act: Llamar CON numero_recibo = 505
        $resultado = $this->useCase->ejecutar($pedido->id, 100, 505);

        // Assert: Debe ignorar el NULL y devolver 1.60
        $this->assertEquals('1.60', $resultado->ancho);
        $this->assertNotEquals('1.50', $resultado->ancho);
    }
}
