<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test: Crear cotización ENVIADA con todos los datos
     */
    public function test_crear_cotizacion_enviada_con_datos_completos()
    {
        $this->actingAs($this->user);

        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => 'CLIENTE TEST',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'PB',
            'especificaciones' => [
                'disponibilidad' => ['Bodega'],
                'forma_pago' => ['Contado'],
                'regimen' => ['Común'],
                'se_ha_vendido' => ['✓'],
                'ultima_venta' => ['✓'],
                'flete' => ['✓'],
            ],
            'productos' => [
                [
                    'nombre' => 'CAMISA DRILL',
                    'descripcion' => 'Camisa de drill naranja',
                    'cantidad' => 1,
                    'tallas' => ['S', 'M', 'L', 'XL'],
                    'fotos_desde_prendaConIndice' => [
                        'foto1.png',
                        'foto2.png',
                        'foto3.png',
                    ],
                    'telas' => [
                        [
                            'color' => 'Naranja',
                            'tela' => 'DRILL BORNEO',
                            'referencia' => 'REF-DB-001',
                        ],
                    ],
                    'variantes' => [
                        'genero_id' => 2,
                        'tipo_manga_id' => 1,
                        'tipo_broche_id' => 2,
                        'tiene_bolsillos' => true,
                        'tiene_reflectivo' => true,
                        'descripcion_adicional' => 'Manga: Larga | Bolsillos: Sí | Broche: Botón | Reflectivo: Sí',
                    ],
                ],
            ],
            'logo' => [],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'usuario_id',
                    'cliente_id',
                    'tipo_venta',
                    'es_borrador',
                    'estado',
                ],
            ]);

        $data = $response->json('data');

        // Verificaciones
        $this->assertFalse($data['es_borrador'], 'Debe ser ENVIADA (es_borrador=false)');
        $this->assertNotNull($data['cliente_id'], 'Cliente ID no debe ser NULL');
        $this->assertEquals('M', $data['tipo_venta'], 'Tipo venta debe ser M');

        // Verificar que se guardó en BD
        $cotizacion = \App\Models\Cotizacion::find($data['id']);
        $this->assertNotNull($cotizacion);
        $this->assertFalse($cotizacion->es_borrador);
        $this->assertNotNull($cotizacion->cliente_id);
        $this->assertEquals('M', $cotizacion->tipo_venta);
        $this->assertNotEmpty($cotizacion->especificaciones);

        // Verificar que se guardó la prenda
        $prendas = $cotizacion->prendas()->get();
        $this->assertCount(1, $prendas);
        $this->assertEquals('CAMISA DRILL', $prendas[0]->nombre_producto);

        // Verificar que se guardaron fotos
        $fotos = $prendas[0]->fotos()->get();
        $this->assertCount(3, $fotos, 'Debe haber 3 fotos guardadas');

        // Verificar que se guardaron telas
        $telas = $prendas[0]->telas()->get();
        $this->assertCount(1, $telas, 'Debe haber 1 tela guardada');
        $this->assertEquals('Naranja', $telas[0]->color);

        // Verificar que se guardaron tallas
        $tallas = $prendas[0]->tallas()->get();
        $this->assertCount(4, $tallas, 'Debe haber 4 tallas guardadas');

        // Verificar que se guardaron variantes
        $variantes = $prendas[0]->variantes()->get();
        $this->assertCount(1, $variantes, 'Debe haber 1 variante guardada');
        $this->assertEquals(2, $variantes[0]->genero_id);
        $this->assertEquals(1, $variantes[0]->tipo_manga_id);
        $this->assertEquals(2, $variantes[0]->tipo_broche_id);
        $this->assertTrue($variantes[0]->tiene_bolsillos);
        $this->assertTrue($variantes[0]->tiene_reflectivo);
    }

    /**
     * Test: Crear cotización BORRADOR
     */
    public function test_crear_cotizacion_borrador()
    {
        $this->actingAs($this->user);

        $datosEnvio = [
            'tipo' => 'borrador',
            'cliente' => 'CLIENTE BORRADOR',
            'tipo_venta' => 'D',
            'productos' => [],
            'logo' => [],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $data = $response->json('data');

        // Verificaciones
        $this->assertTrue($data['es_borrador'], 'Debe ser BORRADOR (es_borrador=true)');
        $this->assertEquals('D', $data['tipo_venta']);

        // Verificar en BD
        $cotizacion = \App\Models\Cotizacion::find($data['id']);
        $this->assertTrue($cotizacion->es_borrador);
    }

    /**
     * Test: Cliente se crea automáticamente si no existe
     */
    public function test_cliente_se_crea_automaticamente()
    {
        $this->actingAs($this->user);

        $nombreClienteNuevo = 'CLIENTE NUEVO ' . time();

        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => $nombreClienteNuevo,
            'tipo_venta' => 'M',
            'productos' => [],
            'logo' => [],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);

        $response->assertStatus(201);

        $data = $response->json('data');

        // Verificar que el cliente se creó
        $cliente = Cliente::where('nombre', $nombreClienteNuevo)->first();
        $this->assertNotNull($cliente);
        $this->assertEquals($cliente->id, $data['cliente_id']);
    }

    /**
     * Test: Especificaciones se guardan correctamente
     */
    public function test_especificaciones_se_guardan()
    {
        $this->actingAs($this->user);

        $especificaciones = [
            'disponibilidad' => ['Bodega'],
            'forma_pago' => ['Crédito'],
            'regimen' => ['Simplificado'],
            'se_ha_vendido' => ['✓'],
            'ultima_venta' => ['✓'],
            'flete' => ['✓'],
        ];

        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => 'CLIENTE SPEC',
            'tipo_venta' => 'M',
            'especificaciones' => $especificaciones,
            'productos' => [],
            'logo' => [],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);

        $response->assertStatus(201);

        $data = $response->json('data');
        $cotizacion = \App\Models\Cotizacion::find($data['id']);

        // Verificar que las especificaciones se guardaron
        $this->assertNotEmpty($cotizacion->especificaciones);
        $this->assertIsArray($cotizacion->especificaciones);
        $this->assertArrayHasKey('disponibilidad', $cotizacion->especificaciones);
        $this->assertEquals(['Bodega'], $cotizacion->especificaciones['disponibilidad']);
    }
}
