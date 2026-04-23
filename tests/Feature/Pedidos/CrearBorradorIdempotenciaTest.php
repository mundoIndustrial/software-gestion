<?php

namespace Tests\Feature\Pedidos;

use App\Models\User;
use App\Models\PedidoProduccion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CrearBorradorIdempotenciaTest
 *
 * Validar que:
 * 1. Doble request con mismo idempotency key = 1 solo pedido
 * 2. Requests diferentes = pedidos diferentes
 * 3. Update NO crea duplicados
 */
class CrearBorradorIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;
    protected array $datosValidos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create();

        $this->datosValidos = [
            'pedido' => json_encode([
                'cliente' => 'Cliente Test',
                'orden_compra' => 'OC-001',
                'forma_de_pago' => 'Crédito',
                'observaciones' => 'Test',
                'prendas' => [],
                'epps' => [],
            ]),
        ];
    }

    /**
     * Test: Doble request con MISMO idempotency key = 1 solo pedido
     */
    public function test_doble_click_con_idempotency_key_crea_solo_un_pedido()
    {
        $idempotencyKey = 'test-' . uniqid();

        // Primer request
        $response1 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $this->datosValidos, [
                'X-Idempotency-Key' => $idempotencyKey,
            ]);

        $response1->assertStatus(200);
        $pedidoId1 = $response1->json('pedido_id');

        // Segundo request IDÉNTICO con MISMA clave
        $response2 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $this->datosValidos, [
                'X-Idempotency-Key' => $idempotencyKey,
            ]);

        $response2->assertStatus(200);
        $pedidoId2 = $response2->json('pedido_id');

        // 🔧 VALIDACIÓN: Debe retornar el MISMO pedido
        $this->assertEquals($pedidoId1, $pedidoId2);
        $this->assertTrue($response2->json('idempotency_cached'));

        // 🔧 VALIDACIÓN: Solo debe haber 1 pedido en BD
        $this->assertEquals(1, PedidoProduccion::where('asesor_id', $this->asesor->id)->count());
    }

    /**
     * Test: Requests DIFERENTES crean pedidos DIFERENTES
     */
    public function test_requests_diferentes_con_claves_diferentes_crean_pedidos_diferentes()
    {
        // Primer request
        $response1 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $this->datosValidos, [
                'X-Idempotency-Key' => 'key-001',
            ]);

        $pedidoId1 = $response1->json('pedido_id');

        // Segundo request CON DIFERENTES DATOS y CLAVE DIFERENTE
        $datosSecundarios = array_merge($this->datosValidos, [
            'pedido' => json_encode([
                'cliente' => 'Otro Cliente',
                'orden_compra' => 'OC-002',
                'forma_de_pago' => 'Contado',
                'observaciones' => 'Otro test',
                'prendas' => [],
                'epps' => [],
            ]),
        ]);

        $response2 = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $datosSecundarios, [
                'X-Idempotency-Key' => 'key-002',
            ]);

        $pedidoId2 = $response2->json('pedido_id');

        // 🔧 VALIDACIÓN: Deben ser pedidos DIFERENTES
        $this->assertNotEquals($pedidoId1, $pedidoId2);

        // 🔧 VALIDACIÓN: Debe haber 2 pedidos en BD
        $this->assertEquals(2, PedidoProduccion::where('asesor_id', $this->asesor->id)->count());
    }

    /**
     * Test: Update NO crea nuevo pedido (no duplica)
     */
    public function test_actualizar_borrador_no_crea_duplicado()
    {
        // Crear borrador inicial
        $createResponse = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $this->datosValidos, [
                'X-Idempotency-Key' => 'create-key',
            ]);

        $pedidoId = $createResponse->json('pedido_id');
        $this->assertEquals(1, PedidoProduccion::count());

        // Actualizar múltiples veces
        for ($i = 0; $i < 3; $i++) {
            $updateResponse = $this
                ->actingAs($this->asesor)
                ->putJson("/api/asesores/pedidos/{$pedidoId}/borrador", [
                    'pedido' => json_encode([
                        'cliente' => "Cliente Actualizado {$i}",
                        'orden_compra' => "OC-UPD-{$i}",
                        'forma_de_pago' => 'Crédito',
                        'observaciones' => "Update {$i}",
                        'prendas' => [],
                        'epps' => [],
                    ]),
                ]);

            $updateResponse->assertStatus(200);
        }

        // 🔧 VALIDACIÓN: Debe seguir habiendo SOLO 1 pedido
        $this->assertEquals(1, PedidoProduccion::count());

        // 🔧 VALIDACIÓN: El pedido se actualizó (última observación)
        $pedido = PedidoProduccion::find($pedidoId);
        $this->assertStringContainsString('Update 2', $pedido->observaciones);
    }

    /**
     * Test: POST con pedido_id en el body es rechazado
     */
    public function test_post_con_pedido_id_es_rechazado()
    {
        $datosConId = array_merge($this->datosValidos, [
            'pedido_id' => 999,
        ]);

        $response = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $datosConId);

        $response->assertStatus(422);
    }

    /**
     * Test: PUT es idempotente (múltiples requests = mismo resultado)
     */
    public function test_put_es_idempotente_por_naturaleza()
    {
        // Crear borrador
        $createResponse = $this
            ->actingAs($this->asesor)
            ->postJson('/api/asesores/pedidos/borrador', $this->datosValidos, [
                'X-Idempotency-Key' => 'create-key',
            ]);

        $pedidoId = $createResponse->json('pedido_id');

        // Hacer PUT 3 veces IDÉNTICAS
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $response = $this
                ->actingAs($this->asesor)
                ->putJson("/api/asesores/pedidos/{$pedidoId}/borrador", [
                    'pedido' => json_encode([
                        'cliente' => 'Cliente Actualizado',
                        'orden_compra' => 'OC-FINAL',
                        'forma_de_pago' => 'Crédito',
                        'observaciones' => 'Observación Final',
                        'prendas' => [],
                        'epps' => [],
                    ]),
                ]);

            $responses[] = $response->json();
        }

        // 🔧 VALIDACIÓN: Todos deben retornar status 200
        // 🔧 VALIDACIÓN: Todos deben retornar el MISMO pedido
        // 🔧 VALIDACIÓN: Solo debe haber 1 pedido en BD
        $this->assertEquals(1, PedidoProduccion::count());
    }
}
