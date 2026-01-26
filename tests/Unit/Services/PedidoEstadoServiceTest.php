<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Services\PedidoEstadoService;
use App\Enums\EstadoPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PedidoEstadoServiceTest extends TestCase
{
    use RefreshDatabase;

    private PedidoEstadoService $service;
    private User $supervisor;
    private User $asesor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(PedidoEstadoService::class);
        
        $this->supervisor = User::factory()->create(['name' => 'Supervisor Test']);
        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);
    }

    /**
     * Test: Obtener siguiente nÃºmero de pedido
     */
    public function test_obtener_siguiente_numero_pedido()
    {
        PedidoProduccion::factory()->create(['numero_pedido' => 500]);
        
        $siguiente = $this->service->obtenerSiguienteNumeroPedido();
        
        $this->assertEquals(501, $siguiente);
    }

    /**
     * Test: Obtener siguiente nÃºmero cuando no hay pedidos
     */
    public function test_obtener_siguiente_numero_pedido_sin_registros()
    {
        $siguiente = $this->service->obtenerSiguienteNumeroPedido();
        
        $this->assertEquals(1, $siguiente);
    }

    /**
     * Test: Aprobar pedido como supervisor
     */
    public function test_aprobar_pedido_como_supervisor()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            'numero_pedido' => null,
        ]);

        $this->actingAs($this->supervisor);
        $resultado = $this->service->aprobarComoSupervisor($pedido);

        $this->assertTrue($resultado);
        $this->assertEquals(EstadoPedido::APROBADO_SUPERVISOR->value, $pedido->fresh()->estado);
    }

    /**
     * Test: Validar transición de PENDIENTE_SUPERVISOR a APROBADO_SUPERVISOR
     */
    public function test_validar_transicion_pendiente_a_aprobado()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        $es_valida = $this->service->validarTransicion(
            $pedido,
            EstadoPedido::APROBADO_SUPERVISOR
        );

        $this->assertTrue($es_valida);
    }

    /**
     * Test: Rechazar transición invÃ¡lida
     */
    public function test_rechazar_transicion_invalida()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        $es_valida = $this->service->validarTransicion(
            $pedido,
            EstadoPedido::FINALIZADO // Saltando estados
        );

        $this->assertFalse($es_valida);
    }

    /**
     * Test: Enviar a producción
     */
    public function test_enviar_a_produccion()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::APROBADO_SUPERVISOR->value,
        ]);

        $this->actingAs($this->supervisor);
        $resultado = $this->service->enviarAProduccion($pedido);

        $this->assertTrue($resultado);
        $this->assertEquals(EstadoPedido::EN_PRODUCCION->value, $pedido->fresh()->estado);
    }

    /**
     * Test: Asignar nÃºmero de pedido
     */
    public function test_asignar_numero_pedido()
    {
        $pedido = PedidoProduccion::factory()->create([
            'numero_pedido' => null,
            'estado' => EstadoPedido::APROBADO_SUPERVISOR->value,
        ]);

        $this->actingAs($this->supervisor);
        $this->service->asignarNumeroPedido($pedido);

        $this->assertNotNull($pedido->fresh()->numero_pedido);
        $this->assertEquals(1, $pedido->fresh()->numero_pedido);
    }

    /**
     * Test: NÃºmeros Ãºnicos no duplicados
     */
    public function test_numeros_pedido_son_unicos()
    {
        $pedido1 = PedidoProduccion::factory()->create([
            'numero_pedido' => null,
            'estado' => EstadoPedido::APROBADO_SUPERVISOR->value,
        ]);

        $pedido2 = PedidoProduccion::factory()->create([
            'numero_pedido' => null,
            'estado' => EstadoPedido::APROBADO_SUPERVISOR->value,
        ]);

        $this->actingAs($this->supervisor);
        $this->service->asignarNumeroPedido($pedido1);
        $this->service->asignarNumeroPedido($pedido2);

        $num1 = $pedido1->fresh()->numero_pedido;
        $num2 = $pedido2->fresh()->numero_pedido;

        $this->assertNotEquals($num1, $num2);
        $this->assertEquals(1, $num1);
        $this->assertEquals(2, $num2);
    }

    /**
     * Test: Obtener historial de cambios
     */
    public function test_obtener_historial_cambios()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
        ]);

        $this->actingAs($this->supervisor);
        $this->service->aprobarComoSupervisor($pedido);

        $historial = $this->service->obtenerHistorial($pedido);

        $this->assertGreaterThan(0, $historial->count());
        $this->assertEquals(EstadoPedido::APROBADO_SUPERVISOR->value, $historial->first()->estado_nuevo);
    }

    /**
     * Test: Obtener estado actual
     */
    public function test_obtener_estado_actual()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        $estado = $this->service->obtenerEstadoActual($pedido);

        $this->assertEquals(EstadoPedido::EN_PRODUCCION->value, $estado);
    }

    /**
     * Test: Marcar como finalizado
     */
    public function test_marcar_como_finalizado()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        $this->actingAs($this->supervisor);
        $resultado = $this->service->marcarComoFinalizado($pedido);

        $this->assertTrue($resultado);
        $this->assertEquals(EstadoPedido::FINALIZADO->value, $pedido->fresh()->estado);
    }

    /**
     * Test: Flujo completo PENDIENTE â†’ FINALIZADO
     */
    public function test_flujo_completo_pedido()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            'numero_pedido' => null,
        ]);

        // Paso 1: Aprobar como supervisor
        $this->actingAs($this->supervisor);
        $this->service->aprobarComoSupervisor($pedido);
        $pedido->refresh();
        $this->assertEquals(EstadoPedido::APROBADO_SUPERVISOR->value, $pedido->estado);

        // Paso 2: Enviar a producción
        $this->service->enviarAProduccion($pedido);
        $pedido->refresh();
        $this->assertEquals(EstadoPedido::EN_PRODUCCION->value, $pedido->estado);
        $this->assertNotNull($pedido->numero_pedido);

        // Paso 3: Marcar como finalizado
        $this->service->marcarComoFinalizado($pedido);
        $pedido->refresh();
        $this->assertEquals(EstadoPedido::FINALIZADO->value, $pedido->estado);

        // Verificar historial
        $historial = $this->service->obtenerHistorial($pedido);
        $this->assertGreaterThanOrEqual(3, $historial->count());
    }

    /**
     * Test: No permitir transición desde estado final
     */
    public function test_no_permitir_transicion_desde_estado_final()
    {
        $pedido = PedidoProduccion::factory()->create([
            'estado' => EstadoPedido::FINALIZADO->value,
        ]);

        $this->expectException(\Exception::class);
        $this->service->marcarComoFinalizado($pedido);
    }
}

