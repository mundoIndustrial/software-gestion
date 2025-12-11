<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\User;
use Tests\TestCase;

class EspecificacionesGuardadoTest extends TestCase
{
    /**
     * Test: Verificar que las especificaciones con observaciones se guardan correctamente
     * Sin RefreshDatabase - usa la BD actual
     */
    public function test_especificaciones_con_observaciones_se_guardan_en_bd()
    {
        // Obtener un usuario existente o crear uno
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

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
                ['valor' => 'Común', 'observacion' => 'IVA incluido']
            ]
        ];

        // Datos mínimos para crear una cotización
        $datos = [
            'tipo_cotizacion' => 'P',
            'cliente' => 'Test Cliente Especificaciones',
            'tipo_venta' => 'M',
            'especificaciones' => json_encode($especificaciones),
            'prendas' => json_encode([]),
            'logo' => json_encode([]),
            'accion' => 'enviar'
        ];

        // Enviar POST a crear cotización
        $response = $this->postJson('/asesores/cotizaciones/guardar', $datos);

        // Verificar que la respuesta es exitosa
        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->json('success'));

        // Obtener la cotización creada
        $cotizacion = Cotizacion::where('cliente_id', '!=', null)
            ->latest()
            ->first();

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

        // Verificar forma de pago
        $this->assertEquals('Contado', $especificacionesGuardadas['forma_pago'][0]['valor']);
        $this->assertEquals('Descuento 5%', $especificacionesGuardadas['forma_pago'][0]['observacion']);

        echo "\n✅ TEST PASADO: Especificaciones con observaciones se guardan correctamente en BD\n";
        echo "   Cotización ID: " . $cotizacion->id . "\n";
        echo "   Especificaciones guardadas: " . json_encode($especificacionesGuardadas, JSON_PRETTY_PRINT) . "\n";
    }
}
