<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\PrendaCotizacionFriendly;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CotizacionesFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Cotizacion $cotizacion;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario
        $this->usuario = User::factory()->create();
        $this->actingAs($this->usuario);
    }

    /**
     * Test: Crear cotización como borrador
     */
    public function test_crear_cotizacion_borrador(): void
    {
        $datos = [
            'cliente' => 'Cliente Test',
            'tipo' => 'borrador',
            'productos' => [
                [
                    'nombre_producto' => 'POLO HOMBRE',
                    'cantidad' => 10,
                    'tallas' => ['S', 'M', 'L'],
                    'variantes' => [
                        'color' => 'Rojo',
                        'tela' => 'Algodón 100%'
                    ]
                ]
            ],
            'tecnicas' => ['Bordado'],
            'ubicaciones' => [['seccion' => 'Pecho']],
            'especificaciones' => ['forma_pago' => 'Contado'],
            'observaciones' => ['Revisar calidad']
        ];

        // Contar cotizaciones antes
        $countAntes = Cotizacion::count();

        // Actuar
        $response = $this->postJson('/api/cotizaciones/guardar', $datos);

        // Afirmar
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertGreater(Cotizacion::count(), $countAntes);
    }

    /**
     * Test: Aceptar cotización crea pedido
     */
    public function test_aceptar_cotizacion_crea_pedido(): void
    {
        // Crear cotización
        $cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'enviada',
            'es_borrador' => false,
            'productos' => [
                ['nombre_producto' => 'POLO', 'cantidad' => 5]
            ],
            'especificaciones' => ['forma_pago' => 'Contado']
        ]);

        $countAntes = PedidoProduccion::count();

        // Actuar
        $response = $this->postJson("/api/cotizaciones/{$cotizacion->id}/aceptar");

        // Afirmar
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertGreater(PedidoProduccion::count(), $countAntes);
    }

    /**
     * Test: Usuario no autorizado no puede aceptar cotización
     */
    public function test_usuario_no_autorizado_no_puede_aceptar_cotizacion(): void
    {
        $otroUsuario = User::factory()->create();

        $cotizacion = Cotizacion::create([
            'user_id' => $otroUsuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'enviada',
            'es_borrador' => false,
            'productos' => [],
            'especificaciones' => []
        ]);

        // Actuar
        $response = $this->postJson("/api/cotizaciones/{$cotizacion->id}/aceptar");

        // Afirmar
        $response->assertStatus(403);
    }

    /**
     * Test: Cambiar estado de cotización
     */
    public function test_cambiar_estado_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'borrador',
            'es_borrador' => true,
            'productos' => [],
            'especificaciones' => []
        ]);

        $this->assertEquals('borrador', $cotizacion->estado);

        // Actuar
        $response = $this->postJson("/api/cotizaciones/{$cotizacion->id}/cambiar-estado", [
            'estado' => 'enviada'
        ]);

        // Afirmar
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        $cotizacion->refresh();
        $this->assertEquals('enviada', $cotizacion->estado);
    }

    /**
     * Test: Eliminar cotización borrador
     */
    public function test_eliminar_cotizacion_borrador(): void
    {
        $cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'borrador',
            'es_borrador' => true,
            'productos' => [],
            'especificaciones' => []
        ]);

        $this->assertDatabaseHas('cotizaciones', ['id' => $cotizacion->id]);

        // Actuar
        $response = $this->deleteJson("/api/cotizaciones/{$cotizacion->id}");

        // Afirmar
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('cotizaciones', ['id' => $cotizacion->id]);
    }

    /**
     * Test: No se puede eliminar cotización enviada
     */
    public function test_no_se_puede_eliminar_cotizacion_enviada(): void
    {
        $cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'enviada',
            'es_borrador' => false,
            'productos' => [],
            'especificaciones' => []
        ]);

        // Actuar
        $response = $this->deleteJson("/api/cotizaciones/{$cotizacion->id}");

        // Afirmar
        $response->assertStatus(403);
        $this->assertDatabaseHas('cotizaciones', ['id' => $cotizacion->id]);
    }

    /**
     * Test: Ver lista de cotizaciones
     */
    public function test_ver_lista_cotizaciones(): void
    {
        Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente 1',
            'estado' => 'enviada',
            'es_borrador' => false,
            'productos' => [],
            'especificaciones' => []
        ]);

        Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente 2',
            'estado' => 'borrador',
            'es_borrador' => true,
            'productos' => [],
            'especificaciones' => []
        ]);

        // Actuar
        $response = $this->getJson('/api/cotizaciones');

        // Afirmar
        $response->assertStatus(200);
        $response->assertViewHas('cotizaciones');
        $response->assertViewHas('borradores');
    }

    /**
     * Test: Ver detalle de cotización
     */
    public function test_ver_detalle_cotizacion(): void
    {
        $cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'estado' => 'enviada',
            'es_borrador' => false,
            'productos' => [],
            'especificaciones' => []
        ]);

        // Actuar
        $response = $this->getJson("/api/cotizaciones/{$cotizacion->id}");

        // Afirmar
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $cotizacion->id,
            'cliente' => 'Cliente Test'
        ]);
    }
}
