<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Enums\EstadoPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PedidoEstadoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;
    private User $asesor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->supervisor = User::factory()->create();
        $this->asesor = User::factory()->create();
    }

    /**
     * Test: Endpoint aprobar pedido como supervisor
     */
    public function test_aprobar_pedido_endpoint()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            'numero_pedido' => null,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("/pedidos/{$pedido->id}/aprobar-supervisor");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(
            EstadoPedido::APROBADO_SUPERVISOR->value,
            $pedido->fresh()->estado
        );
    }

    /**
     * Test: Endpoint obtener historial pedido
     */
    public function test_obtener_historial_pedido_endpoint()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        // Hacer un cambio de estado
        $this->actingAs($this->supervisor)
            ->post("/pedidos/{$pedido->id}/aprobar-supervisor");

        // Obtener historial
        $response = $this->actingAs($this->asesor)
            ->get("/pedidos/{$pedido->id}/historial");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'estado_anterior',
                    'estado_nuevo',
                    'usuario_nombre',
                    'fecha',
                ]
            ]
        ]);
    }

    /**
     * Test: Endpoint obtener seguimiento pedido
     */
    public function test_obtener_seguimiento_pedido_endpoint()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'numero_cotizacion' => 1001,
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/pedidos/{$pedido->id}/seguimiento");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'numero_pedido',
                'numero_cotizacion',
                'cliente',
                'estado',
                'estado_label',
                'estado_color',
                'estado_icono',
                'historial',
            ]
        ]);

        $response->assertJsonPath('data.estado', EstadoPedido::EN_PRODUCCION->value);
    }

    /**
     * Test: Asesor puede ver su pedido
     */
    public function test_asesor_puede_ver_su_pedido()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/pedidos/{$pedido->id}/seguimiento");

        $response->assertSuccessful();
    }

    /**
     * Test: No permitir ver pedido de otro asesor
     */
    public function test_no_permitir_ver_pedido_otro_asesor()
    {
        $otroAsesor = User::factory()->create();
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $otroAsesor->id,
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/pedidos/{$pedido->id}/seguimiento");

        $response->assertForbidden();
    }

    /**
     * Test: Endpoint requiere autenticación
     */
    public function test_endpoint_requiere_autenticacion()
    {
        $pedido = PedidoProduccion::factory()->create();

        $response = $this->post("/pedidos/{$pedido->id}/aprobar-supervisor");

        $response->assertUnauthorized();
    }

    /**
     * Test: Transición inválida devuelve error
     */
    public function test_transicion_invalida_devuelve_error()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::FINALIZADO->value,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("/pedidos/{$pedido->id}/aprobar-supervisor");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test: Número de pedido en seguimiento antes de ser asignado
     */
    public function test_numero_pedido_por_asignar()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'numero_pedido' => null,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/pedidos/{$pedido->id}/seguimiento");

        $response->assertSuccessful();
        $response->assertJsonPath('data.numero_pedido', 'Por asignar');
    }
}
