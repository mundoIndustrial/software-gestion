<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EstadosCotizacionesYPedidosTest extends TestCase
{
    use RefreshDatabase;

    protected User $asesor;
    protected User $contador;
    protected User $aprobador;
    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create(['name' => 'Asesor']);
        $this->contador = User::factory()->create(['name' => 'Contador']);
        $this->aprobador = User::factory()->create(['name' => 'Aprobador']);
        $this->supervisor = User::factory()->create(['name' => 'Supervisor']);
    }

    /** @test */
    public function flujo_completo_cotizacion()
    {
        // 1. Crear cotización en BORRADOR
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
            'numero_cotizacion' => null,
        ]);

        $this->assertEquals(EstadoCotizacion::BORRADOR->value, $cotizacion->estado);
        $this->assertNull($cotizacion->numero_cotizacion);

        // 2. Asesor envía a contador
        $this->actingAs($this->asesor);
        $response = $this->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertStatus(200);
        $cotizacion->refresh();
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR->value, $cotizacion->estado);

        // 3. Verificar que se registró en historial
        $this->assertTrue($cotizacion->historialCambios()->count() > 0);
    }

    /** @test */
    public function puede_obtener_seguimiento_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
            'numero_cotizacion' => 1001,
        ]);

        $this->actingAs($this->asesor);
        $response = $this->get("/cotizaciones/{$cotizacion->id}/seguimiento");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'numero_cotizacion',
                'cliente',
                'estado',
                'estado_label',
                'estado_color',
                'historial'
            ]
        ]);
    }

    /** @test */
    public function puede_obtener_historial_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
        ]);

        // Crear algunos cambios en el historial
        $cotizacion->historialCambios()->create([
            'estado_anterior' => EstadoCotizacion::BORRADOR->value,
            'estado_nuevo' => EstadoCotizacion::ENVIADA_CONTADOR->value,
            'usuario_nombre' => $this->asesor->name,
            'rol_usuario' => 'asesor',
        ]);

        $this->actingAs($this->asesor);
        $response = $this->get("/cotizaciones/{$cotizacion->id}/historial");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'estado_anterior',
                    'estado_nuevo',
                    'usuario_nombre',
                    'fecha'
                ]
            ]
        ]);
    }

    /** @test */
    public function flujo_completo_pedido()
    {
        // 1. Crear pedido en PENDIENTE_SUPERVISOR
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            'numero_pedido' => null,
        ]);

        $this->assertEquals(EstadoPedido::PENDIENTE_SUPERVISOR->value, $pedido->estado);
        $this->assertNull($pedido->numero_pedido);

        // 2. Supervisor aprueba
        $this->actingAs($this->supervisor);
        $response = $this->post("/pedidos/{$pedido->id}/aprobar-supervisor");

        $response->assertStatus(200);
        $pedido->refresh();
        $this->assertEquals(EstadoPedido::APROBADO_SUPERVISOR->value, $pedido->estado);

        // 3. Verificar que se registró en historial
        $this->assertTrue($pedido->historialCambios()->count() > 0);
    }

    /** @test */
    public function puede_obtener_seguimiento_pedido()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::EN_PRODUCCION->value,
            'numero_pedido' => 501,
        ]);

        $this->actingAs($this->asesor);
        $response = $this->get("/pedidos/{$pedido->id}/seguimiento");

        $response->assertStatus(200);
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
                'historial'
            ]
        ]);
    }

    /** @test */
    public function puede_obtener_historial_pedido()
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => EstadoPedido::EN_PRODUCCION->value,
        ]);

        // Crear algunos cambios en el historial
        $pedido->historialCambios()->create([
            'estado_anterior' => EstadoPedido::PENDIENTE_SUPERVISOR->value,
            'estado_nuevo' => EstadoPedido::APROBADO_SUPERVISOR->value,
            'usuario_nombre' => $this->supervisor->name,
            'rol_usuario' => 'supervisor_pedidos',
        ]);

        $this->actingAs($this->asesor);
        $response = $this->get("/pedidos/{$pedido->id}/historial");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'estado_anterior',
                    'estado_nuevo',
                    'usuario_nombre',
                    'fecha'
                ]
            ]
        ]);
    }

    /** @test */
    public function validacion_transicion_invalida()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::FINALIZADA->value,
        ]);

        // Intentar enviar una cotización finalizada (no permitido)
        $this->actingAs($this->asesor);
        $response = $this->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertStatus(400);
    }

    /** @test */
    public function enum_cotizacion_transiciones()
    {
        // Validar transiciones permitidas
        $this->assertTrue(
            EstadoCotizacion::BORRADOR->puedePasar(EstadoCotizacion::ENVIADA_CONTADOR)
        );

        $this->assertFalse(
            EstadoCotizacion::BORRADOR->puedePasar(EstadoCotizacion::FINALIZADA)
        );
    }

    /** @test */
    public function enum_pedido_transiciones()
    {
        // Validar transiciones permitidas
        $this->assertTrue(
            EstadoPedido::PENDIENTE_SUPERVISOR->puedePasar(EstadoPedido::APROBADO_SUPERVISOR)
        );

        $this->assertFalse(
            EstadoPedido::PENDIENTE_SUPERVISOR->puedePasar(EstadoPedido::FINALIZADO)
        );
    }

    /** @test */
    public function historial_registra_cambios()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        // Enviar a contador
        $this->actingAs($this->asesor);
        $this->post("/cotizaciones/{$cotizacion->id}/enviar");

        // Verificar que el historial tiene el cambio
        $historial = $cotizacion->historialCambios()->get();

        $this->assertTrue($historial->isNotEmpty());
        $this->assertEquals(EstadoCotizacion::BORRADOR->value, $historial[0]->estado_anterior);
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR->value, $historial[0]->estado_nuevo);
    }

    /** @test */
    public function relaciones_funcionan_correctamente()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
        ]);

        // Crear historial
        $cotizacion->historialCambios()->create([
            'estado_anterior' => EstadoCotizacion::BORRADOR->value,
            'estado_nuevo' => EstadoCotizacion::ENVIADA_CONTADOR->value,
            'usuario_nombre' => $this->asesor->name,
            'rol_usuario' => 'asesor',
        ]);

        // Verificar relación
        $this->assertTrue($cotizacion->historialCambios()->exists());
        $this->assertEquals(1, $cotizacion->historialCambios()->count());
    }
}
