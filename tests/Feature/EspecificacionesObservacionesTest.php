<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspecificacionesObservacionesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test: Verificar que las especificaciones con observaciones se guardan correctamente
     */
    public function test_especificaciones_con_observaciones_se_guardan()
    {
        $this->actingAs($this->user);

        // Datos de especificaciones con observaciones
        $especificaciones = [
            'disponibilidad' => [
                ['valor' => 'Bodega', 'observacion' => 'En stock disponible'],
                ['valor' => 'Cúcuta', 'observacion' => 'Disponible en 2 días']
            ],
            'forma_pago' => [
                ['valor' => 'Contado', 'observacion' => 'Descuento 5%']
            ],
            'regimen' => [
                ['valor' => 'Común', 'observacion' => '']
            ]
        ];

        // Datos mínimos para crear una cotización
        $datos = [
            'tipo_cotizacion' => 'P',
            'cliente' => 'Cliente Test',
            'tipo_venta' => 'M',
            'especificaciones' => json_encode($especificaciones),
            'prendas' => json_encode([]),
            'logo' => json_encode([]),
            'accion' => 'enviar'
        ];

        // Enviar POST a crear cotización
        $response = $this->postJson('/asesores/cotizaciones/store', $datos);

        // Verificar que la respuesta es exitosa
        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('success'));

        // Obtener la cotización creada
        $cotizacion = Cotizacion::latest()->first();
        $this->assertNotNull($cotizacion);

        // Decodificar las especificaciones guardadas
        $especificacionesGuardadas = json_decode($cotizacion->especificaciones, true);

        // Verificar que las especificaciones se guardaron correctamente
        $this->assertIsArray($especificacionesGuardadas);
        $this->assertArrayHasKey('disponibilidad', $especificacionesGuardadas);
        $this->assertArrayHasKey('forma_pago', $especificacionesGuardadas);
        $this->assertArrayHasKey('regimen', $especificacionesGuardadas);

        // Verificar disponibilidad
        $this->assertCount(2, $especificacionesGuardadas['disponibilidad']);
        $this->assertEquals('Bodega', $especificacionesGuardadas['disponibilidad'][0]['valor']);
        $this->assertEquals('En stock disponible', $especificacionesGuardadas['disponibilidad'][0]['observacion']);
        $this->assertEquals('Cúcuta', $especificacionesGuardadas['disponibilidad'][1]['valor']);
        $this->assertEquals('Disponible en 2 días', $especificacionesGuardadas['disponibilidad'][1]['observacion']);

        // Verificar forma de pago
        $this->assertCount(1, $especificacionesGuardadas['forma_pago']);
        $this->assertEquals('Contado', $especificacionesGuardadas['forma_pago'][0]['valor']);
        $this->assertEquals('Descuento 5%', $especificacionesGuardadas['forma_pago'][0]['observacion']);

        // Verificar régimen
        $this->assertCount(1, $especificacionesGuardadas['regimen']);
        $this->assertEquals('Común', $especificacionesGuardadas['regimen'][0]['valor']);
        $this->assertEquals('', $especificacionesGuardadas['regimen'][0]['observacion']);

        echo "\n✅ TEST PASADO: Especificaciones con observaciones se guardan correctamente\n";
    }

    /**
     * Test: Verificar que las especificaciones vacías se manejan correctamente
     */
    public function test_especificaciones_vacias_se_manejan()
    {
        $this->actingAs($this->user);

        $datos = [
            'tipo_cotizacion' => 'P',
            'cliente' => 'Cliente Test',
            'tipo_venta' => 'M',
            'especificaciones' => json_encode([]),
            'prendas' => json_encode([]),
            'logo' => json_encode([]),
            'accion' => 'enviar'
        ];

        $response = $this->postJson('/asesores/cotizaciones/store', $datos);

        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('success'));

        $cotizacion = Cotizacion::latest()->first();
        $especificacionesGuardadas = json_decode($cotizacion->especificaciones, true);

        // Verificar que se guardó como array vacío
        $this->assertIsArray($especificacionesGuardadas);
        $this->assertEmpty($especificacionesGuardadas);

        echo "\n✅ TEST PASADO: Especificaciones vacías se manejan correctamente\n";
    }

    /**
     * Test: Verificar que las observaciones con caracteres especiales se guardan
     */
    public function test_especificaciones_con_caracteres_especiales()
    {
        $this->actingAs($this->user);

        $especificaciones = [
            'disponibilidad' => [
                ['valor' => 'Bodega', 'observacion' => 'Disponible: "Inmediato" (24hrs) & envío gratis']
            ]
        ];

        $datos = [
            'tipo_cotizacion' => 'P',
            'cliente' => 'Cliente Test',
            'tipo_venta' => 'M',
            'especificaciones' => json_encode($especificaciones),
            'prendas' => json_encode([]),
            'logo' => json_encode([]),
            'accion' => 'enviar'
        ];

        $response = $this->postJson('/asesores/cotizaciones/store', $datos);

        $this->assertEquals(201, $response->status());

        $cotizacion = Cotizacion::latest()->first();
        $especificacionesGuardadas = json_decode($cotizacion->especificaciones, true);

        // Verificar que los caracteres especiales se preservaron
        $this->assertEquals(
            'Disponible: "Inmediato" (24hrs) & envío gratis',
            $especificacionesGuardadas['disponibilidad'][0]['observacion']
        );

        echo "\n✅ TEST PASADO: Especificaciones con caracteres especiales se guardan correctamente\n";
    }
}
