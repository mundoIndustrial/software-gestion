<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\User;
use App\Enums\EstadoCotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CotizacionEstadoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $asesor;
    private User $contador;
    private User $aprobador;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->asesor = User::factory()->create();
        $this->contador = User::factory()->create();
        $this->aprobador = User::factory()->create();
    }

    /**
     * Test: Endpoint enviar cotización
     */
    public function test_enviar_cotizacion_endpoint()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(
            EstadoCotizacion::ENVIADA_CONTADOR->value,
            $cotizacion->fresh()->estado
        );
    }

    /**
     * Test: No permitir enviar cotización de otro usuario
     */
    public function test_no_permitir_enviar_cotizacion_otro_usuario()
    {
        $otroAsesor = User::factory()->create();
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $otroAsesor->id,
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertForbidden();
    }

    /**
     * Test: Endpoint aprobar como contador
     */
    public function test_aprobar_contador_endpoint()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::ENVIADA_CONTADOR->value,
        ]);

        $response = $this->actingAs($this->contador)
            ->post("/cotizaciones/{$cotizacion->id}/aprobar-contador");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(
            EstadoCotizacion::APROBADA_CONTADOR->value,
            $cotizacion->fresh()->estado
        );
    }

    /**
     * Test: Endpoint aprobar como aprobador
     */
    public function test_aprobar_aprobador_endpoint()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
        ]);

        $response = $this->actingAs($this->aprobador)
            ->post("/cotizaciones/{$cotizacion->id}/aprobar-aprobador");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertEquals(
            EstadoCotizacion::APROBADA_COTIZACIONES->value,
            $cotizacion->fresh()->estado
        );
    }

    /**
     * Test: Endpoint obtener historial
     */
    public function test_obtener_historial_endpoint()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::BORRADOR->value,
        ]);

        // Hacer un cambio de estado
        $this->actingAs($this->asesor)
            ->post("/cotizaciones/{$cotizacion->id}/enviar");

        // Obtener historial
        $response = $this->actingAs($this->asesor)
            ->get("/cotizaciones/{$cotizacion->id}/historial");

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
     * Test: Endpoint obtener seguimiento
     */
    public function test_obtener_seguimiento_endpoint()
    {
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $this->asesor->id,
            'numero_cotizacion' => 1001,
            'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/cotizaciones/{$cotizacion->id}/seguimiento");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'numero_cotizacion',
                'cliente',
                'estado',
                'estado_label',
                'estado_color',
                'estado_icono',
                'historial',
            ]
        ]);

        $response->assertJsonPath('data.estado', EstadoCotizacion::APROBADA_COTIZACIONES->value);
    }

    /**
     * Test: No permitir ver seguimiento de cotización ajena
     */
    public function test_no_permitir_ver_seguimiento_cotizacion_ajena()
    {
        $otroAsesor = User::factory()->create();
        $cotizacion = Cotizacion::factory()->create([
            'user_id' => $otroAsesor->id,
            'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->get("/cotizaciones/{$cotizacion->id}/seguimiento");

        $response->assertForbidden();
    }

    /**
     * Test: Endpoint requiere autenticación
     */
    public function test_endpoint_requiere_autenticacion()
    {
        $cotizacion = Cotizacion::factory()->create();

        $response = $this->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertUnauthorized();
    }

    /**
     * Test: Transición inválida devuelve error
     */
    public function test_transicion_invalida_devuelve_error()
    {
        $cotizacion = Cotizacion::factory()->create([
            'estado' => EstadoCotizacion::FINALIZADA->value,
        ]);

        $response = $this->actingAs($this->asesor)
            ->post("/cotizaciones/{$cotizacion->id}/enviar");

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }
}
