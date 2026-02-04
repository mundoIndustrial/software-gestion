<?php

namespace Tests\Feature\Bodega;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReciboPrenda;
use App\Models\Asesor;
use App\Models\Empresa;
use App\Models\Articulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class PedidosControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $bodeguero;
    private ReciboPrenda $reciboPrenda;

    /**
     * Setup test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol bodeguero
        $role = Role::create(['name' => 'bodeguero']);

        // Crear usuario bodeguero
        $this->bodeguero = User::factory()->create();
        $this->bodeguero->assignRole('bodeguero');

        // Crear datos relacionados
        $asesor = Asesor::factory()->create();
        $empresa = Empresa::factory()->create();
        $articulo = Articulo::factory()->create();

        // Crear recibo prenda
        $this->reciboPrenda = ReciboPrenda::create([
            'numero_pedido' => 'PED-001',
            'asesor_id' => $asesor->id,
            'empresa_id' => $empresa->id,
            'articulo_id' => $articulo->id,
            'cantidad' => 10,
            'observaciones' => null,
            'fecha_entrega' => Carbon::now()->addDay(),
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Test: Ver lista de pedidos
     */
    public function test_can_view_pedidos_list(): void
    {
        $response = $this->actingAs($this->bodeguero)
            ->get(route('bodega.pedidos'));

        $response->assertStatus(200);
        $response->assertViewHas('pedidosAgrupados');
        $response->assertViewHas('asesores');
    }

    /**
     * Test: No autenticado no puede ver pedidos
     */
    public function test_unauthenticated_cannot_view_pedidos(): void
    {
        $response = $this->get(route('bodega.pedidos'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Marcar como entregado
     */
    public function test_can_mark_as_entregado(): void
    {
        $this->actingAs($this->bodeguero);

        $response = $this->postJson(
            route('bodega.entregar', $this->reciboPrenda->id)
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->reciboPrenda->refresh();
        $this->assertEquals('entregado', $this->reciboPrenda->estado);
        $this->assertNotNull($this->reciboPrenda->fecha_entrega_real);
    }

    /**
     * Test: Actualizar observaciones
     */
    public function test_can_update_observaciones(): void
    {
        $this->actingAs($this->bodeguero);

        $response = $this->postJson(
            route('bodega.actualizar-observaciones'),
            [
                'id' => $this->reciboPrenda->id,
                'observaciones' => 'Observación de prueba',
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->reciboPrenda->refresh();
        $this->assertEquals('Observación de prueba', $this->reciboPrenda->observaciones);
    }

    /**
     * Test: Actualizar fecha de entrega
     */
    public function test_can_update_fecha_entrega(): void
    {
        $this->actingAs($this->bodeguero);

        $newDate = '2026-02-15';

        $response = $this->postJson(
            route('bodega.actualizar-fecha'),
            [
                'id' => $this->reciboPrenda->id,
                'fecha_entrega' => $newDate,
            ]
        );

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->reciboPrenda->refresh();
        $this->assertEquals($newDate, $this->reciboPrenda->fecha_entrega->format('Y-m-d'));
    }

    /**
     * Test: Validar fecha inválida
     */
    public function test_cannot_update_with_invalid_date(): void
    {
        $this->actingAs($this->bodeguero);

        $response = $this->postJson(
            route('bodega.actualizar-fecha'),
            [
                'id' => $this->reciboPrenda->id,
                'fecha_entrega' => 'invalid-date',
            ]
        );

        $response->assertStatus(422);
    }

    /**
     * Test: Validar observaciones muy largas
     */
    public function test_cannot_update_with_too_long_observaciones(): void
    {
        $this->actingAs($this->bodeguero);

        $response = $this->postJson(
            route('bodega.actualizar-observaciones'),
            [
                'id' => $this->reciboPrenda->id,
                'observaciones' => str_repeat('a', 501),
            ]
        );

        $response->assertStatus(422);
    }

    /**
     * Test: Validar ID inexistente
     */
    public function test_cannot_update_nonexistent_id(): void
    {
        $this->actingAs($this->bodeguero);

        $response = $this->postJson(
            route('bodega.actualizar-observaciones'),
            [
                'id' => 99999,
                'observaciones' => 'test',
            ]
        );

        $response->assertStatus(422);
    }

    /**
     * Test: Scopes del modelo
     */
    public function test_model_scopes(): void
    {
        // Crear un entregado
        ReciboPrenda::create([
            'numero_pedido' => 'PED-002',
            'asesor_id' => $this->reciboPrenda->asesor_id,
            'empresa_id' => $this->reciboPrenda->empresa_id,
            'articulo_id' => $this->reciboPrenda->articulo_id,
            'cantidad' => 5,
            'estado' => 'entregado',
        ]);

        // Crear un retrasado
        ReciboPrenda::create([
            'numero_pedido' => 'PED-003',
            'asesor_id' => $this->reciboPrenda->asesor_id,
            'empresa_id' => $this->reciboPrenda->empresa_id,
            'articulo_id' => $this->reciboPrenda->articulo_id,
            'cantidad' => 3,
            'fecha_entrega' => Carbon::now()->subDay(),
            'estado' => 'pendiente',
        ]);

        $this->assertEquals(1, ReciboPrenda::pendiente()->count());
        $this->assertEquals(1, ReciboPrenda::entregado()->count());
        $this->assertEquals(1, ReciboPrenda::retrasado()->count());
    }

    /**
     * Test: Mutadores del modelo
     */
    public function test_model_mutators(): void
    {
        $this->reciboPrenda->estado = 'entregado';
        $this->assertEquals('✓ ENTREGADO', $this->reciboPrenda->estado_etiqueta);
        $this->assertEquals('green', $this->reciboPrenda->estado_color);

        $this->reciboPrenda->estado = 'retrasado';
        $this->assertEquals('⚠ RETRASADO', $this->reciboPrenda->estado_etiqueta);

        $this->reciboPrenda->estado = 'pendiente';
        $this->assertEquals('⏳ PENDIENTE', $this->reciboPrenda->estado_etiqueta);
    }

    /**
     * Test: Método isRetrasado
     */
    public function test_is_retrasado_method(): void
    {
        $this->reciboPrenda->fecha_entrega = Carbon::now()->subDay();
        $this->reciboPrenda->estado = 'pendiente';

        $this->assertTrue($this->reciboPrenda->isRetrasado());

        $this->reciboPrenda->estado = 'entregado';
        $this->assertFalse($this->reciboPrenda->isRetrasado());
    }

    /**
     * Test: Método marcarEntregado
     */
    public function test_mark_entregado_method(): void
    {
        $result = $this->reciboPrenda->marcarEntregado($this->bodeguero);

        $this->assertTrue($result);
        $this->reciboPrenda->refresh();
        $this->assertEquals('entregado', $this->reciboPrenda->estado);
        $this->assertEquals($this->bodeguero->id, $this->reciboPrenda->usuario_bodeguero_id);
    }

    /**
     * Test: Método getResumen
     */
    public function test_get_resumen_method(): void
    {
        $resumen = $this->reciboPrenda->getResumen();

        $this->assertArrayHasKey('id', $resumen);
        $this->assertArrayHasKey('numero_pedido', $resumen);
        $this->assertArrayHasKey('asesor', $resumen);
        $this->assertArrayHasKey('estado', $resumen);
        $this->assertEquals($this->reciboPrenda->id, $resumen['id']);
    }

    /**
     * Test: Activity Log
     */
    public function test_activity_logging(): void
    {
        $this->actingAs($this->bodeguero);

        $this->postJson(
            route('bodega.entregar', $this->reciboPrenda->id)
        );

        // Verificar que se registró la actividad
        $activity = activity()
            ->forModel($this->reciboPrenda)
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('entregado', $activity->event);
        $this->assertEquals('bodega', $activity->log_name);
    }
}
