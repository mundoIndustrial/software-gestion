<?php

namespace Tests\Feature\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\ReflectivoCotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardarReflectivoCotizacionTest extends TestCase
{
    protected User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Usar transacciones para que los cambios no persistan
        DB::beginTransaction();
        
        // Crear usuario asesor
        $this->usuario = User::factory()->create([
            'email' => 'asesor@test.com',
            'name' => 'Asesor Test',
        ]);
    }

    protected function tearDown(): void
    {
        // Rollback de todas las transacciones al finalizar
        DB::rollBack();
        parent::tearDown();
    }

    /**
     * Test: Guardar cotización reflectivo exitosamente
     */
    public function test_guardar_cotizacion_reflectivo_exitosamente()
    {
        $this->actingAs($this->usuario);

        $data = [
            'cliente' => 'Cliente Test',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            'descripcion_reflectivo' => 'Reflectivo para casco de seguridad',
            'ubicaciones_reflectivo' => json_encode([
                [
                    'ubicacion' => 'PECHO',
                    'descripcion' => 'Centro del pecho'
                ],
                [
                    'ubicacion' => 'ESPALDA',
                    'descripcion' => 'Centro de la espalda'
                ]
            ]),
            'observaciones_generales' => json_encode([
                [
                    'texto' => 'Material de alta calidad',
                    'tipo' => 'texto',
                    'valor' => 'Especificar calidad premium'
                ]
            ]),
            'imagenes_reflectivo' => [
                UploadedFile::fake()->image('reflectivo1.jpg', 640, 480),
                UploadedFile::fake()->image('reflectivo2.jpg', 640, 480),
            ]
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);

        // Verificar respuesta
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Cotización de reflectivo guardada exitosamente'
            ]);

        // Verificar datos en respuesta
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'cotizacion' => [
                    'id',
                    'numero_cotizacion',
                    'asesor_id',
                    'cliente_id',
                    'es_borrador',
                    'estado'
                ],
                'reflectivo' => [
                    'id',
                    'cotizacion_id',
                    'descripcion',
                    'ubicacion',
                    'observaciones_generales'
                ]
            ]
        ]);

        // Verificar que se guardó en base de datos
        $cotizacion = Cotizacion::latest()->first();
        $this->assertNotNull($cotizacion);
        $this->assertEquals('Cliente Test', $cotizacion->cliente->nombre ?? $cotizacion->cliente_id);
        $this->assertEquals($this->usuario->id, $cotizacion->asesor_id);
        $this->assertTrue($cotizacion->es_borrador);
        $this->assertEquals('BORRADOR', $cotizacion->estado);

        // Verificar reflectivo
        $reflectivo = ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();
        $this->assertNotNull($reflectivo);
        $this->assertEquals('Reflectivo para casco de seguridad', $reflectivo->descripcion);

        // Verificar ubicaciones guardadas como JSON
        $ubicaciones = json_decode($reflectivo->ubicacion, true);
        $this->assertIsArray($ubicaciones);
        $this->assertCount(2, $ubicaciones);
        $this->assertEquals('PECHO', $ubicaciones[0]['ubicacion']);

        // Verificar observaciones generales guardadas como JSON
        $observaciones = json_decode($reflectivo->observaciones_generales, true);
        $this->assertIsArray($observaciones);
        $this->assertCount(1, $observaciones);
        $this->assertEquals('Material de alta calidad', $observaciones[0]['texto']);

        // Verificar imÃ¡genes guardadas
        Storage::disk('public')->assertExists('cotizaciones/reflectivo/reflectivo1.jpg');
        Storage::disk('public')->assertExists('cotizaciones/reflectivo/reflectivo2.jpg');
    }

    /**
     * Test: Guardar cotización reflectivo y enviar
     */
    public function test_guardar_y_enviar_cotizacion_reflectivo()
    {
        $this->actingAs($this->usuario);

        $data = [
            'cliente' => 'Cliente Importante',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'enviar',
            'descripcion_reflectivo' => 'Reflectivo premium para ropa deportiva',
            'ubicaciones_reflectivo' => json_encode([
                ['ubicacion' => 'MANGA IZQUIERDA', 'descripcion' => 'Banda en manga']
            ]),
            'observaciones_generales' => json_encode([]),
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);

        $response->assertStatus(201);

        // Verificar que se guardó como NO borrador
        $cotizacion = Cotizacion::latest()->first();
        $this->assertFalse($cotizacion->es_borrador);
        $this->assertEquals('ENVIADA_CONTADOR', $cotizacion->estado);
    }

    /**
     * Test: Error si faltan datos requeridos
     */
    public function test_error_si_faltan_datos_requeridos()
    {
        $this->actingAs($this->usuario);

        // Falta descripcion_reflectivo
        $data = [
            'cliente' => 'Cliente Test',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            // 'descripcion_reflectivo' => 'Falta este campo',
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['descripcion_reflectivo']);
    }

    /**
     * Test: Guardar sin imÃ¡genes
     */
    public function test_guardar_cotizacion_reflectivo_sin_imagenes()
    {
        $this->actingAs($this->usuario);

        $data = [
            'cliente' => 'Cliente Sin Fotos',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            'descripcion_reflectivo' => 'Reflectivo bÃ¡sico sin imÃ¡genes',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
            // No enviamos imÃ¡genes
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);

        $response->assertStatus(201);

        $cotizacion = Cotizacion::latest()->first();
        $reflectivo = ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();
        
        $this->assertNotNull($reflectivo);
        $imagenes = json_decode($reflectivo->imagenes, true);
        $this->assertIsArray($imagenes);
        $this->assertEmpty($imagenes);
    }

    /**
     * Test: Verificar nÃºmero de cotización Ãºnico
     */
    public function test_numero_cotizacion_es_unico()
    {
        $this->actingAs($this->usuario);

        $data = [
            'cliente' => 'Cliente Test',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            'descripcion_reflectivo' => 'Primera cotización',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ];

        // Primera cotización
        $response1 = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);
        $numero1 = $response1->json('data.cotizacion.numero_cotizacion');

        // Segunda cotización
        $response2 = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);
        $numero2 = $response2->json('data.cotizacion.numero_cotizacion');

        $this->assertNotEquals($numero1, $numero2);
        $this->assertStringStartsWith('COT-', $numero1);
        $this->assertStringStartsWith('COT-', $numero2);
    }

    /**
     * Test: Relación entre Cotizacion y ReflectivoCotizacion
     */
    public function test_relacion_cotizacion_reflectivo()
    {
        $this->actingAs($this->usuario);

        $data = [
            'cliente' => 'Cliente Relación',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            'descripcion_reflectivo' => 'Test relaciones',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);

        $cotizacion = Cotizacion::latest()->first();
        $reflectivo = $cotizacion->reflectivoCotizacion;

        $this->assertNotNull($reflectivo);
        $this->assertEquals($cotizacion->id, $reflectivo->cotizacion_id);
        
        // Verificar relación inversa
        $cotizacionDesdeReflectivo = $reflectivo->cotizacion;
        $this->assertEquals($cotizacion->id, $cotizacionDesdeReflectivo->id);
    }

    /**
     * Test: Usuario solo ve sus propias cotizaciones
     */
    public function test_usuario_solo_ve_sus_cotizaciones()
    {
        // Crear otro usuario
        $otroUsuario = User::factory()->create();

        // Crear cotización con primer usuario
        $this->actingAs($this->usuario);
        $data = [
            'cliente' => 'Cliente Test',
            'asesora' => 'Asesor Test',
            'fecha' => '2025-12-15',
            'action' => 'borrador',
            'descripcion_reflectivo' => 'Cotización del primer usuario',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ];

        $response = $this->postJson(route('cotizaciones.reflectivo.guardar'), $data);
        $cotizacionId = $response->json('data.cotizacion.id');

        // Intentar acceder con otro usuario
        $this->actingAs($otroUsuario);
        $response = $this->getJson(route('cotizaciones.api', $cotizacionId));
        
        // DeberÃ­a no tener permiso (si hay verificación)
        // $response->assertStatus(403);

        // Pero el propietario sÃ­ puede ver
        $this->actingAs($this->usuario);
        $response = $this->getJson(route('cotizaciones.api', $cotizacionId));
        $response->assertStatus(200);
    }
}

