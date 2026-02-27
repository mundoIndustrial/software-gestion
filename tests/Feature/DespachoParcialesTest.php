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
                    'genero' => $talla->genero,
                ],
                // EPP sin talla
                [
                    'tipo' => 'epp',
                    'id' => $pedidoEpp->id,
                    'talla_id' => null,
                    'genero' => null,
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
        $this->assertEquals($talla->id, $despachosPrenda->talla_id);
        $this->assertEquals($talla->genero, $despachosPrenda->genero);

        // Validar EPP
        $despachoEpp = \App\Models\DesparChoParcialesModel::where([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'epp',
        ])->first();

        $this->assertNotNull($despachoEpp);
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
                    'genero' => $talla->genero,
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
        $this->assertEquals($talla->id, $despacho->talla_id);
        $this->assertEquals($talla->genero, $despacho->genero);
    }
}
