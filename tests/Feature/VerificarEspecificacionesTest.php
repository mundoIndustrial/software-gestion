<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cotizacion;
use Tests\TestCase;

class VerificarEspecificacionesTest extends TestCase
{
    public function test_especificaciones_se_guardan_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización con especificaciones
        $especificaciones = [
            [
                'nombre' => 'Material',
                'valor' => 'Algodón 100%',
            ],
            [
                'nombre' => 'Peso',
                'valor' => '250 gramos',
            ],
            [
                'nombre' => 'Ancho',
                'valor' => '1.5 metros',
            ],
            [
                'nombre' => 'Resistencia',
                'valor' => 'Alta',
            ],
        ];

        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-ESP-001',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'especificaciones' => json_encode($especificaciones),
        ]);

        // Verificar que la cotización se guardó
        $this->assertDatabaseHas('cotizaciones', [
            'id' => $cotizacion->id,
            'numero_cotizacion' => 'COT-ESP-001',
        ]);

        // Recuperar la cotización
        $cotizacionRecuperada = Cotizacion::find($cotizacion->id);

        // Verificar que las especificaciones se guardaron correctamente
        $this->assertNotNull($cotizacionRecuperada->especificaciones);
        
        // Decodificar si viene como string JSON
        $especificacionesGuardadas = $cotizacionRecuperada->especificaciones;
        if (is_string($especificacionesGuardadas)) {
            $especificacionesGuardadas = json_decode($especificacionesGuardadas, true);
        }
        
        $this->assertIsArray($especificacionesGuardadas);
        $this->assertEquals(4, count($especificacionesGuardadas));

        // Verificar cada especificación
        $this->assertEquals('Material', $especificacionesGuardadas[0]['nombre']);
        $this->assertEquals('Algodón 100%', $especificacionesGuardadas[0]['valor']);

        $this->assertEquals('Peso', $especificacionesGuardadas[1]['nombre']);
        $this->assertEquals('250 gramos', $especificacionesGuardadas[1]['valor']);

        $this->assertEquals('Ancho', $especificacionesGuardadas[2]['nombre']);
        $this->assertEquals('1.5 metros', $especificacionesGuardadas[2]['valor']);

        $this->assertEquals('Resistencia', $especificacionesGuardadas[3]['nombre']);
        $this->assertEquals('Alta', $especificacionesGuardadas[3]['valor']);
    }

    public function test_especificaciones_vacias_se_guardan_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización sin especificaciones
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-ESP-002',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'especificaciones' => json_encode([]),
        ]);

        // Recuperar la cotización
        $cotizacionRecuperada = Cotizacion::find($cotizacion->id);

        // Verificar que las especificaciones están vacías
        $this->assertNotNull($cotizacionRecuperada->especificaciones);
        
        // Decodificar si viene como string JSON
        $especificacionesGuardadas = $cotizacionRecuperada->especificaciones;
        if (is_string($especificacionesGuardadas)) {
            $especificacionesGuardadas = json_decode($especificacionesGuardadas, true);
        }
        
        $this->assertIsArray($especificacionesGuardadas);
        $this->assertEquals(0, count($especificacionesGuardadas));
    }

    public function test_especificaciones_con_multiples_campos_se_guardan_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización con especificaciones complejas
        $especificaciones = [
            [
                'nombre' => 'Composición',
                'valor' => 'Algodón 80%, Poliéster 20%',
                'descripcion' => 'Mezcla de fibras',
                'cantidad' => 100,
            ],
            [
                'nombre' => 'Acabado',
                'valor' => 'Satinado',
                'descripcion' => 'Acabado especial',
                'cantidad' => 50,
            ],
        ];

        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-ESP-003',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'especificaciones' => json_encode($especificaciones),
        ]);

        // Recuperar la cotización
        $cotizacionRecuperada = Cotizacion::find($cotizacion->id);

        // Verificar que todas las especificaciones se guardaron con todos sus campos
        // Decodificar si viene como string JSON
        $especificacionesGuardadas = $cotizacionRecuperada->especificaciones;
        if (is_string($especificacionesGuardadas)) {
            $especificacionesGuardadas = json_decode($especificacionesGuardadas, true);
        }
        
        $this->assertEquals(2, count($especificacionesGuardadas));

        // Verificar primera especificación
        $this->assertEquals('Composición', $especificacionesGuardadas[0]['nombre']);
        $this->assertEquals('Algodón 80%, Poliéster 20%', $especificacionesGuardadas[0]['valor']);
        $this->assertEquals('Mezcla de fibras', $especificacionesGuardadas[0]['descripcion']);
        $this->assertEquals(100, $especificacionesGuardadas[0]['cantidad']);

        // Verificar segunda especificación
        $this->assertEquals('Acabado', $especificacionesGuardadas[1]['nombre']);
        $this->assertEquals('Satinado', $especificacionesGuardadas[1]['valor']);
        $this->assertEquals('Acabado especial', $especificacionesGuardadas[1]['descripcion']);
        $this->assertEquals(50, $especificacionesGuardadas[1]['cantidad']);
    }
}
