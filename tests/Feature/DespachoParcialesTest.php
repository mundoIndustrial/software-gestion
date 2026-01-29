<?php

namespace Tests\Feature;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PedidoEpp;
use App\Models\Epp;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * DespachoParcialesTest
 * 
 * Test para validar el guardado de despachos parciales por talla
 * 
 * Flujo a probar:
 * 1. Crear un pedido con prendas y tallas
 * 2. Agregar EPP al pedido
 * 3. POST /despacho/{pedido}/guardar con datos de despachos parciales
 * 4. Validar que los datos se guardaron exactamente como se enviaron
 * 5. Validar que NO hay cálculos automáticos
 */
class DespachoParcialesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Guardar despachos parciales sin validaciones matemáticas
     */
    public function test_guardar_despachos_parciales_sin_validaciones()
    {
        // Crear usuario
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        // Crear pedido
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-001',
            'cliente' => 'Cliente Test',
            'asesor_id' => $usuario->id,
            'forma_de_pago' => 'contado',
            'estado' => 'Pendiente',
        ]);

        // Crear prenda
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Camisa Drill',
            'descripcion' => 'Camisa drill azul',
        ]);

        // Crear talla
        $talla = PrendaPedidoTalla::create([
            'prenda_pedido_id' => $prenda->id,
            'talla' => 'M',
            'cantidad' => 100,
            'genero' => 'Unisex',
        ]);

        // Crear EPP
        $epp = Epp::create([
            'nombre_completo' => 'Casco Seguridad',
            'codigo' => 'CASCO-001',
        ]);

        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 50,
        ]);

        // Preparar datos de despacho
        $datosDespacho = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'cliente_empresa' => 'Receptor Test',
            'despachos' => [
                // Prenda con talla
                [
                    'tipo' => 'prenda',
                    'id' => $talla->id,  // ID de prenda_pedido_tallas
                    'talla_id' => $talla->id,
                    'pendiente_inicial' => 100,
                    'parcial_1' => 30,
                    'pendiente_1' => 70,
                    'parcial_2' => 40,
                    'pendiente_2' => 30,
                    'parcial_3' => 25,
                    'pendiente_3' => 5,
                ],
                // EPP sin talla
                [
                    'tipo' => 'epp',
                    'id' => $pedidoEpp->id,
                    'talla_id' => null,
                    'pendiente_inicial' => 50,
                    'parcial_1' => 15,
                    'pendiente_1' => 35,
                    'parcial_2' => 20,
                    'pendiente_2' => 15,
                    'parcial_3' => 15,
                    'pendiente_3' => 0,
                ],
            ],
        ];

        // Ejecutar POST
        $response = $this->postJson(
            route('despacho.guardar', $pedido->id),
            $datosDespacho
        );

        // Validar respuesta
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Validar que se guardó en la BD
        $despachosPrenda = \App\Models\DesparChoParcialesModel::where([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
        ])->first();

        $this->assertNotNull($despachosPrenda);
        $this->assertEquals(100, $despachosPrenda->pendiente_inicial);
        $this->assertEquals(30, $despachosPrenda->parcial_1);
        $this->assertEquals(70, $despachosPrenda->pendiente_1);
        $this->assertEquals(40, $despachosPrenda->parcial_2);
        $this->assertEquals(30, $despachosPrenda->pendiente_2);
        $this->assertEquals(25, $despachosPrenda->parcial_3);
        $this->assertEquals(5, $despachosPrenda->pendiente_3);
        $this->assertEquals($talla->id, $despachosPrenda->talla_id);

        // Validar EPP
        $despachoEpp = \App\Models\DesparChoParcialesModel::where([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'epp',
        ])->first();

        $this->assertNotNull($despachoEpp);
        $this->assertEquals(50, $despachoEpp->pendiente_inicial);
        $this->assertEquals(15, $despachoEpp->parcial_1);
        $this->assertEquals(35, $despachoEpp->pendiente_1);
        $this->assertEquals(20, $despachoEpp->parcial_2);
        $this->assertEquals(15, $despachoEpp->pendiente_2);
        $this->assertEquals(15, $despachoEpp->parcial_3);
        $this->assertEquals(0, $despachoEpp->pendiente_3);
        $this->assertNull($despachoEpp->talla_id);
    }

    /**
     * Test: Validar que los datos inconsistentes se guardan igual
     * (sin cálculos automáticos)
     */
    public function test_guardar_datos_inconsistentes_sin_validacion()
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-002',
            'cliente' => 'Cliente Test 2',
            'asesor_id' => $usuario->id,
            'forma_de_pago' => 'contado',
            'estado' => 'Pendiente',
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Pantalón Drill',
            'descripcion' => 'Pantalón drill negro',
        ]);

        $talla = PrendaPedidoTalla::create([
            'prenda_pedido_id' => $prenda->id,
            'talla' => 'L',
            'cantidad' => 50,
            'genero' => 'Hombre',
        ]);

        // Datos inconsistentes (no suman correctamente)
        $datosDespacho = [
            'fecha_hora' => now()->format('Y-m-d\TH:i'),
            'cliente_empresa' => 'Receptor Test',
            'despachos' => [
                [
                    'tipo' => 'prenda',
                    'id' => $talla->id,
                    'talla_id' => $talla->id,
                    'pendiente_inicial' => 100,  // Mayor que cantidad
                    'parcial_1' => 200,          // Mayor que pendiente
                    'pendiente_1' => 0,
                    'parcial_2' => 0,
                    'pendiente_2' => 0,
                    'parcial_3' => 0,
                    'pendiente_3' => 0,
                ],
            ],
        ];

        $response = $this->postJson(
            route('despacho.guardar', $pedido->id),
            $datosDespacho
        );

        // Debe guardar igual, sin validación
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $despacho = \App\Models\DesparChoParcialesModel::first();
        $this->assertEquals(100, $despacho->pendiente_inicial);
        $this->assertEquals(200, $despacho->parcial_1);
    }
}
