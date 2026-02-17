<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\DesparChoParcialesModel;
use App\Models\User;
use App\Domain\Pedidos\Despacho\Services\DespachoEstadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class DespachoEstadoAutomaticoTest extends TestCase
{
    use RefreshDatabase;

    private DespachoEstadoService $despachoEstadoService;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->despachoEstadoService = new DespachoEstadoService();
        $this->usuario = User::factory()->create();
    }

    /** @test */
    public function puede_verificar_si_pedido_esta_completamente_despachado()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12345
        ]);

        // Crear despachos parciales no entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 5,
            'parcial_2' => 0,
            'parcial_3' => 0,
            'entregado' => false
        ]);

        $this->assertFalse(
            $this->despachoEstadoService->estaPedidoCompletamenteDespachado($pedido->id),
            'El pedido no debe estar completamente despachado cuando hay ítems pendientes'
        );
    }

    /** @test */
    public function puede_verificar_si_pedido_esta_completamente_despachado_con_todos_entregados()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12346
        ]);

        // Crear despachos parciales todos entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 5,
            'parcial_2' => 5,
            'parcial_3' => 0,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'epp',
            'item_id' => 2,
            'talla_id' => null,
            'pendiente_inicial' => 5,
            'parcial_1' => 5,
            'parcial_2' => 0,
            'parcial_3' => 0,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        $this->assertTrue(
            $this->despachoEstadoService->estaPedidoCompletamenteDespachado($pedido->id),
            'El pedido debe estar completamente despachado cuando todos los ítems están entregados'
        );
    }

    /** @test */
    public function puede_cambiar_estado_a_entregado_cuando_esta_completamente_despachado()
    {
        // Crear pedido en estado "En Ejecución"
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12347
        ]);

        // Crear despachos parciales todos entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 10,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        // Ejecutar el servicio
        $cambiado = $this->despachoEstadoService->cambiarEstadoAEntregadoSiCorresponde($pedido->id);

        $this->assertTrue($cambiado, 'Debe retornar true cuando cambia el estado');

        // Verificar que el estado cambió
        $pedidoActualizado = $pedido->fresh();
        $this->assertEquals('Entregado', $pedidoActualizado->estado);
    }

    /** @test */
    public function no_cambia_estado_si_no_esta_completamente_despachado()
    {
        // Crear pedido en estado "En Ejecución"
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12348
        ]);

        // Crear despachos parciales no todos entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 5,
            'entregado' => false
        ]);

        // Ejecutar el servicio
        $cambiado = $this->despachoEstadoService->cambiarEstadoAEntregadoSiCorresponde($pedido->id);

        $this->assertFalse($cambiado, 'Debe retornar false cuando no cambia el estado');

        // Verificar que el estado NO cambió
        $pedidoActualizado = $pedido->fresh();
        $this->assertEquals('En Ejecución', $pedidoActualizado->estado);
    }

    /** @test */
    public function no_cambia_estado_si_ya_esta_entregado()
    {
        // Crear pedido ya en estado "Entregado"
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'Entregado',
            'numero_pedido' => 12349
        ]);

        // Crear despachos parciales todos entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 10,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        // Ejecutar el servicio
        $cambiado = $this->despachoEstadoService->cambiarEstadoAEntregadoSiCorresponde($pedido->id);

        $this->assertFalse($cambiado, 'Debe retornar false cuando el pedido ya está entregado');

        // Verificar que el estado se mantiene igual
        $pedidoActualizado = $pedido->fresh();
        $this->assertEquals('Entregado', $pedidoActualizado->estado);
    }

    /** @test */
    public function puede_obtener_estadisticas_de_despacho()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12350
        ]);

        // Crear mezcla de despachos entregados y no entregados
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 2,
            'talla_id' => 2,
            'entregado' => false
        ]);

        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'epp',
            'item_id' => 3,
            'talla_id' => null,
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        $estadisticas = $this->despachoEstadoService->obtenerEstadisticasDespacho($pedido->id);

        $this->assertEquals($pedido->id, $estadisticas['pedido_id']);
        $this->assertEquals(3, $estadisticas['total_items']);
        $this->assertEquals(2, $estadisticas['items_entregados']);
        $this->assertEquals(1, $estadisticas['items_pendientes']);
        $this->assertEquals(66.67, $estadisticas['porcentaje_entregado']);
        $this->assertFalse($estadisticas['esta_completamente_despachado']);
        
        // Verificar detalle por tipo
        $this->assertArrayHasKey('prenda', $estadisticas['detalle_por_tipo']);
        $this->assertArrayHasKey('epp', $estadisticas['detalle_por_tipo']);
        $this->assertEquals(2, $estadisticas['detalle_por_tipo']['prenda']['total']);
        $this->assertEquals(1, $estadisticas['detalle_por_tipo']['epp']['total']);
    }

    /** @test */
    public function puede_procesar_multiples_pedidos()
    {
        // Crear múltiples pedidos
        $pedido1 = PedidoProduccion::factory()->create(['estado' => 'En Ejecución', 'numero_pedido' => 12351]);
        $pedido2 = PedidoProduccion::factory()->create(['estado' => 'En Ejecución', 'numero_pedido' => 12352]);
        $pedido3 = PedidoProduccion::factory()->create(['estado' => 'Entregado', 'numero_pedido' => 12353]);

        // Pedido 1: completamente despachado
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido1->id,
            'tipo_item' => 'prenda',
            'entregado' => true,
            'fecha_entrega' => now()
        ]);

        // Pedido 2: parcialmente despachado
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido2->id,
            'tipo_item' => 'prenda',
            'entregado' => false
        ]);

        $resultados = $this->despachoEstadoService->procesarMultiplesPedidos([
            $pedido1->id,
            $pedido2->id,
            $pedido3->id
        ]);

        $this->assertEquals(3, $resultados['total_procesados']);
        $this->assertEquals(1, $resultados['cambiados_a_entregado']);
        $this->assertEquals(1, $resultados['ya_estaban_entregados']);
        $this->assertEquals(1, $resultados['no_completos']);
        $this->assertEquals(0, $resultados['errores']);

        // Verificar que el pedido 1 cambió a entregado
        $pedido1Actualizado = $pedido1->fresh();
        $this->assertEquals('Entregado', $pedido1Actualizado->estado);

        // Verificar que el pedido 2 no cambió
        $pedido2Actualizado = $pedido2->fresh();
        $this->assertEquals('En Ejecución', $pedido2Actualizado->estado);
    }

    /** @test */
    public function observer_actualiza_estado_automaticamente_al_marcar_como_entregado()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12354
        ]);

        // Crear despacho parcial no entregado
        $despacho = DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 10,
            'entregado' => false
        ]);

        // Verificar estado inicial
        $this->assertEquals('En Ejecución', $pedido->fresh()->estado);

        // Marcar como entregado (esto debería disparar el observer)
        $despacho->entregado = true;
        $despacho->fecha_entrega = now();
        $despacho->save();

        // Verificar que el estado cambió a "Entregado"
        $pedidoActualizado = $pedido->fresh();
        $this->assertEquals('Entregado', $pedidoActualizado->estado);
    }

    /** @test */
    public function maneja_pedido_inexistente()
    {
        $this->assertFalse(
            $this->despachoEstadoService->estaPedidoCompletamenteDespachado(999999),
            'Debe retornar false para pedido inexistente'
        );

        $this->assertFalse(
            $this->despachoEstadoService->cambiarEstadoAEntregadoSiCorresponde(999999),
            'Debe retornar false para pedido inexistente'
        );
    }
}
