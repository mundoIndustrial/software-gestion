<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\DesparChoParcialesModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class DeshacerEntregadoDespachoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);
    }

    /** @test */
    public function puede_deshacer_marcado_como_entregado()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12345
        ]);

        // Crear despacho parcial marcado como entregado
        $despacho = DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'pendiente_inicial' => 10,
            'parcial_1' => 10,
            'entregado' => true,
            'fecha_entrega' => now(),
            'usuario_id' => $this->usuario->id
        ]);

        // Verificar estado inicial
        $this->assertTrue($despacho->entregado);
        $this->assertNotNull($despacho->fecha_entrega);

        // Deshacer el marcado como entregado
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
        ]);

        // Verificar respuesta
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Marcado como entregado deshecho correctamente',
        ]);

        // Verificar que el estado fue actualizado en la base de datos
        $despachoActualizado = $despacho->fresh();
        $this->assertFalse($despachoActualizado->entregado);
        $this->assertNull($despachoActualizado->fecha_entrega);
        $this->assertEquals($this->usuario->id, $despachoActualizado->usuario_id);
    }

    /** @test */
    public function no_puede_deshacer_si_no_esta_entregado()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12346
        ]);

        // Crear despacho parcial NO entregado
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
            'entregado' => false,
        ]);

        // Intentar deshacer el marcado como entregado
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => 1,
        ]);

        // Verificar respuesta de error
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'No se encontró registro de entrega para deshacer',
        ]);
    }

    /** @test */
    public function puede_deshacer_entregado_epp()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12347
        ]);

        // Crear EPP marcado como entregado
        $despacho = DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'epp',
            'item_id' => 5,
            'talla_id' => null, // EPP no tiene talla
            'pendiente_inicial' => 5,
            'parcial_1' => 5,
            'entregado' => true,
            'fecha_entrega' => now(),
            'usuario_id' => $this->usuario->id
        ]);

        // Deshacer el marcado como entregado
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'epp',
            'item_id' => 5,
            'talla_id' => null,
        ]);

        // Verificar respuesta
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verificar que el estado fue actualizado
        $despachoActualizado = $despacho->fresh();
        $this->assertFalse($despachoActualizado->entregado);
        $this->assertNull($despachoActualizado->fecha_entrega);
    }

    /** @test */
    public function requiere_autenticacion_para_deshacer()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12348
        ]);

        // Crear despacho entregado
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'entregado' => true,
        ]);

        // Cerrar sesión
        $this->actingAs(null);

        // Intentar deshacer sin autenticación
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'prenda',
            'item_id' => 1,
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    /** @test */
    public function valida_datos_de_entrada()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12349
        ]);

        // Intentar deshacer con datos inválidos
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'tipo_invalido', // Debe ser prenda o epp
            'item_id' => 'no_numero', // Debe ser integer
        ]);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['tipo_item', 'item_id']);
    }

    /** @test */
    public function maneja_errores_inesperados()
    {
        // Crear pedido
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'En Ejecución',
            'numero_pedido' => 12350
        ]);

        // Mock para simular error en base de datos
        $this->mock(\App\Models\DesparChoParcialesModel::class)
            ->shouldReceive('where')
            ->andThrow(new \Exception('Error de base de datos simulado'));

        // Intentar deshacer
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'prenda',
            'item_id' => 1,
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /** @test */
    public function actualiza_estado_del_pedido_cuando_se_deshace_ultima_entrega()
    {
        // Crear pedido en estado "Entregado"
        $pedido = PedidoProduccion::factory()->create([
            'estado' => 'Entregado',
            'numero_pedido' => 12351
        ]);

        // Crear un único despacho entregado
        DesparChoParcialesModel::factory()->create([
            'pedido_id' => $pedido->id,
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'entregado' => true,
            'fecha_entrega' => now(),
        ]);

        // Deshacer el marcado como entregado
        $response = $this->postJson(route('despacho.deshacer-entregado', $pedido->id), [
            'tipo_item' => 'prenda',
            'item_id' => 1,
        ]);

        $response->assertStatus(200);

        // Verificar que el observer cambió el estado del pedido
        // (Esto depende de que el observer esté funcionando)
        $pedidoActualizado = $pedido->fresh();
        
        // El estado debería cambiar de "Entregado" a otro estado
        // dependiendo de la lógica del observer
        $this->assertNotEquals('Entregado', $pedidoActualizado->estado);
    }
}
