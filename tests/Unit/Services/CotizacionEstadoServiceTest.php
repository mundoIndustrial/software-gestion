<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\User;
use App\Services\CotizacionEstadoService;
use App\Enums\EstadoCotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CotizacionEstadoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CotizacionEstadoService $service;
    private User $asesor;
    private User $contador;
    private User $aprobador;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CotizacionEstadoService::class);
        
        // Crear usuarios de prueba
        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);
        $this->contador = User::factory()->create(['name' => 'Contador Test']);
        $this->aprobador = User::factory()->create(['name' => 'Aprobador Test']);
    }

    /**
     * Test: Obtener siguiente número de cotización
     */
    public function test_obtener_siguiente_numero_cotizacion()
    {
        // Crear una cotización con número
        Cotizacion::factory()->create(['numero_cotizacion' => 100]);
        
        $siguiente = $this->service->obtenerSiguienteNumeroCotizacion();
        
        $this->assertEquals(101, $siguiente);
    }

    /**
     * Test: Obtener siguiente número cuando no hay cotizaciones
     */
    public function test_obtener_siguiente_numero_cotizacion_sin_registros()
    {
        $siguiente = $this->service->obtenerSiguienteNumeroCotizacion();
        
        $this->assertEquals(1, $siguiente);
    }

    /**
     * Test: Enviar cotización a contador
     */
    public function test_enviar_cotizacion_a_contador()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $this->actingAs($this->asesor);
        $resultado = $this->service->enviarACOntador($cotizacion);

        $this->assertTrue($resultado);
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR->value, $cotizacion->fresh()->estado);
    }

    /**
     * Test: Validar transición de BORRADOR a ENVIADA_CONTADOR
     */
    public function test_validar_transicion_borrador_a_enviada_contador()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $es_valida = $this->service->validarTransicion(
            $cotizacion,
            EstadoCotizacion::ENVIADA_CONTADOR
        );

        $this->assertTrue($es_valida);
    }

    /**
     * Test: Rechazar transición inválida
     */
    public function test_rechazar_transicion_invalida()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $es_valida = $this->service->validarTransicion(
            $cotizacion,
            EstadoCotizacion::APROBADA_COTIZACIONES // Saltando estados
        );

        $this->assertFalse($es_valida);
    }

    /**
     * Test: Aprobar como contador
     */
    public function test_aprobar_como_contador()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::ENVIADA_CONTADOR->value,
        ]);

        $this->actingAs($this->contador);
        $resultado = $this->service->aprobarComoContador($cotizacion);

        $this->assertTrue($resultado);
        $this->assertEquals(EstadoCotizacion::APROBADA_CONTADOR->value, $cotizacion->fresh()->estado);
    }

    /**
     * Test: Asignar número de cotización
     */
    public function test_asignar_numero_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create([
            'numero_cotizacion' => null,
            'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
        ]);

        $this->actingAs($this->contador);
        $this->service->asignarNumeroCotizacion($cotizacion);

        $this->assertNotNull($cotizacion->fresh()->numero_cotizacion);
        $this->assertEquals(1, $cotizacion->fresh()->numero_cotizacion);
    }

    /**
     * Test: Números únicos no duplicados
     */
    public function test_numeros_cotizacion_son_unicos()
    {
        $cotizacion1 = Cotizacion::factory()->create([
            'numero_cotizacion' => null,
            'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
        ]);

        $cotizacion2 = Cotizacion::factory()->create([
            'numero_cotizacion' => null,
            'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
        ]);

        $this->actingAs($this->contador);
        $this->service->asignarNumeroCotizacion($cotizacion1);
        $this->service->asignarNumeroCotizacion($cotizacion2);

        $num1 = $cotizacion1->fresh()->numero_cotizacion;
        $num2 = $cotizacion2->fresh()->numero_cotizacion;

        $this->assertNotEquals($num1, $num2);
        $this->assertEquals(1, $num1);
        $this->assertEquals(2, $num2);
    }

    /**
     * Test: Obtener historial de cambios
     */
    public function test_obtener_historial_cambios()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $this->actingAs($this->asesor);
        $this->service->enviarACOntador($cotizacion);

        $historial = $this->service->obtenerHistorial($cotizacion);

        $this->assertGreaterThan(0, $historial->count());
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR->value, $historial->first()->estado_nuevo);
    }

    /**
     * Test: Obtener estado actual
     */
    public function test_obtener_estado_actual()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
        ]);

        $estado = $this->service->obtenerEstadoActual($cotizacion);

        $this->assertEquals(EstadoCotizacion::APROBADA_COTIZACIONES->value, $estado);
    }

    /**
     * Test: Flujo completo BORRADOR → APROBADA_COTIZACIONES
     */
    public function test_flujo_completo_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        // Paso 1: Enviar a contador
        $this->actingAs($this->asesor);
        $this->service->enviarACOntador($cotizacion);
        $cotizacion->refresh();
        $this->assertEquals(EstadoCotizacion::ENVIADA_CONTADOR->value, $cotizacion->estado);

        // Paso 2: Aprobar como contador
        $this->actingAs($this->contador);
        $this->service->aprobarComoContador($cotizacion);
        $cotizacion->refresh();
        $this->assertEquals(EstadoCotizacion::APROBADA_CONTADOR->value, $cotizacion->estado);

        // Verificar que se asignó número
        $this->assertNotNull($cotizacion->numero_cotizacion);

        // Paso 3: Aprobar como aprobador
        $this->actingAs($this->aprobador);
        $this->service->aprobarComoAprobador($cotizacion);
        $cotizacion->refresh();
        $this->assertEquals(EstadoCotizacion::APROBADA_COTIZACIONES->value, $cotizacion->estado);

        // Verificar historial
        $historial = $this->service->obtenerHistorial($cotizacion);
        $this->assertGreaterThanOrEqual(3, $historial->count());
    }

    /**
     * Test: No permitir transición duplicada
     */
    public function test_no_permitir_transicion_desde_estado_invalido()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::FINALIZADA->value,
        ]);

        $this->expectException(\Exception::class);
        $this->service->aprobarComoContador($cotizacion);
    }
}
