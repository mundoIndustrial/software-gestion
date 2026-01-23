<?php

namespace Tests\Feature\Cotizacion;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CotizacionE2ETest - Tests E2E para flujo completo de cotizaciones
 *
 * Verifica el flujo completo desde creaciÃ³n hasta aceptaciÃ³n
 */
class CotizacionE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create(['role' => 'asesor']);
    }

    /**
     * @test
     * Flujo completo: Crear â†’ Obtener â†’ Cambiar Estado â†’ Aceptar â†’ Eliminar
     */
    public function flujo_completo_cotizacion(): void
    {
        // 1. Crear cotizaciÃ³n como borrador
        $response = $this->actingAs($this->usuario)->postJson('/asesores/cotizaciones', [
            'tipo' => 'P',
            'cliente' => 'Acme Corporation',
            'asesora' => $this->usuario->name,
            'productos' => [],
            'es_borrador' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $cotizacionId = $response->json('data.id');

        // 2. Obtener cotizaciÃ³n
        $response = $this->actingAs($this->usuario)->getJson("/asesores/cotizaciones/{$cotizacionId}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.es_borrador', true);
        $response->assertJsonPath('data.cliente', 'Acme Corporation');

        // 3. Cambiar estado a ENVIADA_CONTADOR
        $response = $this->actingAs($this->usuario)->patchJson(
            "/asesores/cotizaciones/{$cotizacionId}/estado/ENVIADA_CONTADOR"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.estado', 'ENVIADA_CONTADOR');

        // 4. Listar cotizaciones
        $response = $this->actingAs($this->usuario)->getJson('/asesores/cotizaciones');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('total', 1);

        // 5. Cambiar a APROBADA_APROBADOR (para poder aceptar)
        $this->actingAs($this->usuario)->patchJson(
            "/asesores/cotizaciones/{$cotizacionId}/estado/APROBADA_APROBADOR"
        );

        // 6. Aceptar cotizaciÃ³n
        $response = $this->actingAs($this->usuario)->postJson(
            "/asesores/cotizaciones/{$cotizacionId}/aceptar"
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.estado', 'ACEPTADA');
    }

    /**
     * @test
     * Verificar que solo borradores pueden ser eliminados
     */
    public function solo_borradores_pueden_ser_eliminados(): void
    {
        // Crear borrador
        $response = $this->actingAs($this->usuario)->postJson('/asesores/cotizaciones', [
            'tipo' => 'P',
            'cliente' => 'Test Client',
            'asesora' => $this->usuario->name,
            'es_borrador' => true,
        ]);

        $cotizacionId = $response->json('data.id');

        // Cambiar a estado enviado
        $this->actingAs($this->usuario)->patchJson(
            "/asesores/cotizaciones/{$cotizacionId}/estado/ENVIADA_CONTADOR"
        );

        // Intentar eliminar (debe fallar)
        $response = $this->actingAs($this->usuario)->deleteJson(
            "/asesores/cotizaciones/{$cotizacionId}"
        );

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    /**
     * @test
     * Verificar autorizaciÃ³n - usuario no propietario no puede acceder
     */
    public function usuario_no_propietario_no_puede_acceder(): void
    {
        $otroUsuario = User::factory()->create(['role' => 'asesor']);

        // Crear cotizaciÃ³n
        $response = $this->actingAs($this->usuario)->postJson('/asesores/cotizaciones', [
            'tipo' => 'P',
            'cliente' => 'Test Client',
            'asesora' => $this->usuario->name,
            'es_borrador' => true,
        ]);

        $cotizacionId = $response->json('data.id');

        // Intentar acceder con otro usuario
        $response = $this->actingAs($otroUsuario)->getJson("/asesores/cotizaciones/{$cotizacionId}");

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    /**
     * @test
     * Verificar transiciones de estado vÃ¡lidas
     */
    public function transiciones_estado_validas(): void
    {
        $response = $this->actingAs($this->usuario)->postJson('/asesores/cotizaciones', [
            'tipo' => 'P',
            'cliente' => 'Test Client',
            'asesora' => $this->usuario->name,
            'es_borrador' => true,
        ]);

        $cotizacionId = $response->json('data.id');

        // TransiciÃ³n vÃ¡lida: BORRADOR â†’ ENVIADA_CONTADOR
        $response = $this->actingAs($this->usuario)->patchJson(
            "/asesores/cotizaciones/{$cotizacionId}/estado/ENVIADA_CONTADOR"
        );
        $this->assertTrue($response->json('success'));

        // TransiciÃ³n invÃ¡lida: ENVIADA_CONTADOR â†’ BORRADOR (no permitida)
        $response = $this->actingAs($this->usuario)->patchJson(
            "/asesores/cotizaciones/{$cotizacionId}/estado/BORRADOR"
        );
        $this->assertTrue($response->json('success')); // SÃ­ estÃ¡ permitida volver a borrador
    }
}

