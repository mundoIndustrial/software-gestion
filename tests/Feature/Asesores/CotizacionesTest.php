<?php

namespace Tests\Feature\Asesores;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use App\Models\User;
use App\Models\HistorialCotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CotizacionesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Asegurar que estamos usando la BD de testing
        config(['database.default' => 'mysql']);
        
        // Deshabilitar TODOS los middlewares para tests
        // Solo estamos probando la funcionalidad, no la autenticación
        $this->withoutMiddleware();
        
        // Crear usuario asesor
        $this->user = User::factory()->create([
            'name' => 'María López',
            'email' => 'maria@example.com'
        ]);
        
        // Autenticar el usuario para que Auth::user() funcione
        $this->actingAs($this->user);

        // Crear tipos de cotización (o usar existentes)
        TipoCotizacion::firstOrCreate(['codigo' => 'M'], ['nombre' => 'Muestra']);
        TipoCotizacion::firstOrCreate(['codigo' => 'D'], ['nombre' => 'Desarrollo']);
        TipoCotizacion::firstOrCreate(['codigo' => 'X'], ['nombre' => 'Especial']);
    }

    /**
     * Test: Crear una cotización en borradores
     */
    public function test_crear_cotizacion_en_borradores()
    {
        $datos = [
            'numero_cotizacion' => 'COT-001',
            'tipo_cotizacion' => 'M',
            'cliente' => 'Juan Pérez',
            'tipo' => 'borrador',
            'productos' => [
                [
                    'nombre_producto' => 'Camisa',
                    'descripcion' => 'Camisa de algodón',
                    'cantidad' => 100,
                    'tallas' => ['S', 'M', 'L'],
                    'disponibilidad' => 'Inmediata',
                    'forma_pago' => 'Contado',
                    'regimen' => 'Común',
                    'se_ha_vendido' => 'Sí',
                    'ultima_venta' => '2025-11-20',
                    'observacion' => 'Observación de prueba'
                ]
            ],
            'imagenes' => [],
            'tecnicas' => ['BORDADO'],
            'observaciones_tecnicas' => 'Observaciones técnicas',
            'ubicaciones' => ['CAMISA' => 'Pecho izquierdo'],
            'observaciones_generales' => ['Observación 1', 'Observación 2']
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datos);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('message', 'Cotización guardada en borradores');

        // Verificar que se creó la cotización
        $cotizacion = Cotizacion::where('numero_cotizacion', 'COT-001')->first();
        $this->assertNotNull($cotizacion);
        $this->assertTrue($cotizacion->es_borrador);
        $this->assertNull($cotizacion->fecha_envio);
        $this->assertNotNull($cotizacion->fecha_inicio);

        // Verificar que se guardó el tipo de cotización
        $this->assertNotNull($cotizacion->tipo_cotizacion_id);
        $this->assertEquals('M', $cotizacion->tipoCotizacion->codigo);

        // Verificar que se guardaron las prendas
        $this->assertCount(1, $cotizacion->prendasCotizaciones);
        $prenda = $cotizacion->prendasCotizaciones->first();
        $this->assertEquals('Camisa', $prenda->nombre_producto);
        $this->assertEquals(['S', 'M', 'L'], $prenda->tallas);

        // Verificar que se guardaron las especificaciones
        $this->assertNotNull($cotizacion->especificaciones);
        $this->assertEquals('Camisa', $cotizacion->especificaciones[0]['nombre_producto']);
        $this->assertEquals('Contado', $cotizacion->especificaciones[0]['forma_pago']);

        // Verificar que se registró en el historial
        $historial = HistorialCotizacion::where('cotizacion_id', $cotizacion->id)->first();
        $this->assertNotNull($historial);
        $this->assertEquals('creacion', $historial->tipo_cambio);
        $this->assertEquals('María López', $historial->usuario_nombre);
    }

    /**
     * Test: Editar un borrador (actualización)
     */
    public function test_editar_borrador()
    {
        $this->actingAs($this->user);

        // Crear cotización inicial
        $cotizacion = Cotizacion::create([
            'user_id' => $this->user->id,
            'numero_cotizacion' => 'COT-002',
            'tipo_cotizacion_id' => TipoCotizacion::where('codigo', 'M')->first()->id,
            'cliente' => 'Juan Pérez',
            'asesora' => 'María López',
            'es_borrador' => true,
            'estado' => 'enviada',
            'fecha_inicio' => now(),
            'especificaciones' => []
        ]);

        $fechaInicioOriginal = $cotizacion->fecha_inicio;

        // Esperar un segundo para que sea diferente
        sleep(1);

        // Editar la cotización
        $datosActualizados = [
            'cotizacion_id' => $cotizacion->id,
            'numero_cotizacion' => 'COT-002-ACTUALIZADO',
            'tipo_cotizacion' => 'D',
            'cliente' => 'Juan Pérez Actualizado',
            'tipo' => 'borrador',
            'productos' => [
                [
                    'nombre_producto' => 'Pantalón',
                    'descripcion' => 'Pantalón de denim',
                    'cantidad' => 50,
                    'tallas' => ['28', '30', '32'],
                    'disponibilidad' => '15 días',
                    'forma_pago' => 'Crédito',
                    'regimen' => 'Especial',
                    'se_ha_vendido' => 'No',
                    'ultima_venta' => null,
                    'observacion' => 'Nueva observación'
                ]
            ],
            'imagenes' => [],
            'tecnicas' => ['DTF'],
            'observaciones_tecnicas' => 'Nuevas observaciones técnicas',
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosActualizados);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('message', 'Borrador actualizado correctamente');

        // Recargar cotización
        $cotizacion->refresh();

        // Verificar que se actualizó el contenido
        $this->assertEquals('COT-002-ACTUALIZADO', $cotizacion->numero_cotizacion);
        $this->assertEquals('Juan Pérez Actualizado', $cotizacion->cliente);
        $this->assertEquals('D', $cotizacion->tipoCotizacion->codigo);

        // Verificar que fecha_inicio NO cambió
        $this->assertEquals($fechaInicioOriginal->toDateTimeString(), $cotizacion->fecha_inicio->toDateTimeString());

        // Verificar que se actualizaron las prendas
        $this->assertCount(1, $cotizacion->prendasCotizaciones);
        $prenda = $cotizacion->prendasCotizaciones->first();
        $this->assertEquals('Pantalón', $prenda->nombre_producto);

        // Verificar que se registró la actualización en el historial
        $historialActualizacion = HistorialCotizacion::where('cotizacion_id', $cotizacion->id)
            ->where('tipo_cambio', 'actualizacion')
            ->first();
        $this->assertNotNull($historialActualizacion);
        $this->assertEquals('María López', $historialActualizacion->usuario_nombre);
    }

    /**
     * Test: Enviar cotización (cambiar estado)
     */
    public function test_enviar_cotizacion()
    {
        $this->actingAs($this->user);

        // Crear cotización en borradores
        $cotizacion = Cotizacion::create([
            'user_id' => $this->user->id,
            'numero_cotizacion' => 'COT-003',
            'tipo_cotizacion_id' => TipoCotizacion::where('codigo', 'M')->first()->id,
            'cliente' => 'Juan Pérez',
            'asesora' => 'María López',
            'es_borrador' => true,
            'estado' => 'enviada',
            'fecha_inicio' => now(),
            'especificaciones' => []
        ]);

        $this->assertNull($cotizacion->fecha_envio);

        // Enviar cotización
        $response = $this->patchJson("/asesores/cotizaciones/{$cotizacion->id}/estado/enviada");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('message', 'Estado actualizado');

        // Recargar cotización
        $cotizacion->refresh();

        // Verificar que se guardó fecha_envio
        $this->assertNotNull($cotizacion->fecha_envio);
        $this->assertFalse($cotizacion->es_borrador);

        // Verificar que se registró el envío en el historial
        $historialEnvio = HistorialCotizacion::where('cotizacion_id', $cotizacion->id)
            ->where('tipo_cambio', 'envio')
            ->first();
        $this->assertNotNull($historialEnvio);
        $this->assertStringContainsString('Enviada', $historialEnvio->descripcion);
    }

    /**
     * Test: Flujo completo (crear → editar → enviar)
     */
    public function test_flujo_completo_cotizacion()
    {
        $this->actingAs($this->user);

        // PASO 1: Crear cotización
        $datosCreacion = [
            'numero_cotizacion' => 'COT-COMPLETA',
            'tipo_cotizacion' => 'M',
            'cliente' => 'Cliente Test',
            'tipo' => 'borrador',
            'productos' => [
                [
                    'nombre_producto' => 'Camisa',
                    'descripcion' => 'Camisa de prueba',
                    'cantidad' => 100,
                    'tallas' => ['S', 'M', 'L'],
                    'disponibilidad' => 'Inmediata',
                    'forma_pago' => 'Contado',
                    'regimen' => 'Común',
                    'se_ha_vendido' => 'Sí',
                    'ultima_venta' => '2025-11-20',
                    'observacion' => 'Observación inicial'
                ]
            ],
            'imagenes' => [],
            'tecnicas' => ['BORDADO'],
            'observaciones_tecnicas' => 'Obs técnicas',
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $responseCreacion = $this->postJson('/asesores/cotizaciones/guardar', $datosCreacion);
        $cotizacionId = $responseCreacion->json('cotizacion_id');
        $cotizacion = Cotizacion::find($cotizacionId);

        $this->assertTrue($cotizacion->es_borrador);
        $this->assertNull($cotizacion->fecha_envio);
        $this->assertNotNull($cotizacion->fecha_inicio);

        // PASO 2: Editar cotización
        sleep(1);
        $datosEdicion = $datosCreacion;
        $datosEdicion['cotizacion_id'] = $cotizacionId;
        $datosEdicion['cliente'] = 'Cliente Test Actualizado';
        $datosEdicion['tipo_cotizacion'] = 'D';

        $responseEdicion = $this->postJson('/asesores/cotizaciones/guardar', $datosEdicion);
        $responseEdicion->assertStatus(200);

        $cotizacion->refresh();
        $this->assertEquals('Cliente Test Actualizado', $cotizacion->cliente);
        $this->assertEquals('D', $cotizacion->tipoCotizacion->codigo);
        $this->assertTrue($cotizacion->es_borrador); // Sigue siendo borrador

        // PASO 3: Enviar cotización
        $responseEnvio = $this->patchJson("/asesores/cotizaciones/{$cotizacionId}/estado/enviada");
        $responseEnvio->assertStatus(200);

        $cotizacion->refresh();
        $this->assertFalse($cotizacion->es_borrador);
        $this->assertNotNull($cotizacion->fecha_envio);

        // Verificar historial completo
        $historial = HistorialCotizacion::where('cotizacion_id', $cotizacionId)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->assertCount(3, $historial);
        $this->assertEquals('creacion', $historial[0]->tipo_cambio);
        $this->assertEquals('actualizacion', $historial[1]->tipo_cambio);
        $this->assertEquals('envio', $historial[2]->tipo_cambio);
    }

    /**
     * Test: Verificar que fecha_inicio no cambia en ediciones
     */
    public function test_fecha_inicio_no_cambia_en_ediciones()
    {
        $this->actingAs($this->user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'user_id' => $this->user->id,
            'numero_cotizacion' => 'COT-FECHA',
            'tipo_cotizacion_id' => TipoCotizacion::where('codigo', 'M')->first()->id,
            'cliente' => 'Test',
            'asesora' => 'María López',
            'es_borrador' => true,
            'estado' => 'enviada',
            'fecha_inicio' => now()->subHours(2),
            'especificaciones' => []
        ]);

        $fechaInicial = $cotizacion->fecha_inicio;

        // Editar 3 veces
        for ($i = 1; $i <= 3; $i++) {
            sleep(1);
            
            $datos = [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => 'COT-FECHA',
                'tipo_cotizacion' => 'M',
                'cliente' => "Test Edición $i",
                'tipo' => 'borrador',
                'productos' => [],
                'imagenes' => [],
                'tecnicas' => [],
                'observaciones_tecnicas' => '',
                'ubicaciones' => [],
                'observaciones_generales' => []
            ];

            $this->postJson('/asesores/cotizaciones/guardar', $datos);
            $cotizacion->refresh();

            // Verificar que fecha_inicio no cambió
            $this->assertEquals($fechaInicial->toDateTimeString(), $cotizacion->fecha_inicio->toDateTimeString());
        }

        // Verificar que hay 3 registros de actualización en el historial
        $actualizaciones = HistorialCotizacion::where('cotizacion_id', $cotizacion->id)
            ->where('tipo_cambio', 'actualizacion')
            ->count();
        $this->assertEquals(3, $actualizaciones);
    }
}
