<?php

namespace Tests\Feature\Insumos;

use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InsumosMaterialesEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_guardar_materiales_detallados_crea_solo_los_recibidos(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 920001,
            'cliente' => 'Cliente Test Endpoints',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Camiseta Test',
            'descripcion' => 'Prenda para test de guardado',
            'de_bodega' => false,
        ]);

        $response = $this->postJson("/insumos/materiales/{$pedido->numero_pedido}/guardar", [
            'prenda_id' => $prenda->id,
            'materiales' => [
                [
                    'nombre' => 'Tela Azul',
                    'fecha_orden' => '2026-03-01',
                    'fecha_pedido' => '2026-03-02',
                    'fecha_pago' => '2026-03-03',
                    'fecha_llegada' => '2026-03-04',
                    'fecha_despacho' => '2026-03-05',
                    'observaciones' => 'Material principal',
                    'recibido' => true,
                ],
                [
                    'nombre' => 'Boton Negro',
                    'recibido' => false,
                ],
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('materiales_orden_insumos', [
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Tela Azul',
            'prenda_id' => $prenda->id,
            'recibido' => 1,
        ]);

        $this->assertDatabaseMissing('materiales_orden_insumos', [
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Boton Negro',
            'prenda_id' => $prenda->id,
        ]);
    }

    public function test_eliminar_material_por_nombre_lo_remueve_del_pedido(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 920002,
            'cliente' => 'Cliente Test Eliminar',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        MaterialesOrdenInsumos::create([
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Cierre Frontal',
            'recibido' => true,
        ]);

        $response = $this->postJson("/insumos/materiales/{$pedido->numero_pedido}/eliminar", [
            'nombre_material' => 'Cierre Frontal',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Material eliminado correctamente',
        ]);

        $this->assertDatabaseMissing('materiales_orden_insumos', [
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Cierre Frontal',
        ]);
    }

    public function test_obtener_materiales_por_pedido_y_prenda_filtra_correctamente(): void
    {
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 920003,
            'cliente' => 'Cliente Test Obtener',
            'estado' => 'PENDIENTE_INSUMOS',
            'area' => 'INSUMOS',
        ]);

        $prendaA = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Chaqueta A',
            'descripcion' => 'Prenda A',
            'de_bodega' => false,
        ]);

        $prendaB = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => 'Chaqueta B',
            'descripcion' => 'Prenda B',
            'de_bodega' => false,
        ]);

        MaterialesOrdenInsumos::create([
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Tela A',
            'prenda_id' => $prendaA->id,
            'recibido' => true,
        ]);

        MaterialesOrdenInsumos::create([
            'numero_pedido' => (string) $pedido->numero_pedido,
            'nombre_material' => 'Tela B',
            'prenda_id' => $prendaB->id,
            'recibido' => true,
        ]);

        $response = $this->getJson("/insumos/api/materiales/{$pedido->numero_pedido}?prenda_id={$prendaA->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'nombre_prenda' => 'Chaqueta A',
        ]);
        $response->assertJsonCount(1, 'materiales');
        $response->assertJsonPath('materiales.0.nombre_material', 'Tela A');
        $response->assertJsonPath('materiales.0.prenda_id', $prendaA->id);
    }
}
