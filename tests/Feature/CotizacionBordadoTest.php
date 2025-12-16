<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use Tests\TestCase;

class CotizacionBordadoTest extends TestCase
{

    protected $user;
    protected $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario asesor
        $this->user = User::factory()->create([
            'role' => 'asesor'
        ]);
        
        // Crear cliente
        $this->cliente = Cliente::create([
            'nombre' => 'MINCIVIL'
        ]);
    }

    /**
     * Test: Crear cotización de bordado como borrador
     */
    public function test_crear_cotizacion_bordado_borrador()
    {
        $this->actingAs($this->user);

        $datos = [
            'cliente' => 'MINCIVIL',
            'cliente_id' => $this->cliente->id,
            'descripcion' => 'prueba de logo',
            'asesora' => $this->user->name,
            'fecha' => '2025-12-16',
            'action' => 'borrador',
            'accion' => 'borrador',
            'observaciones_tecnicas' => 'prueba logo bordado',
            'tecnicas' => json_encode(['BORDADO', 'DTF']),
            'ubicaciones' => json_encode([
                ['tipo' => 'PRENDA', 'nombre' => 'PECHO'],
                ['tipo' => 'PRENDA', 'nombre' => 'ESPALDA']
            ]),
            'observaciones_generales' => json_encode([
                ['tipo' => 'PRENDA', 'observacion' => 'Obs 1'],
                ['tipo' => 'PRENDA', 'observacion' => 'Obs 2']
            ]),
        ];

        $response = $this->post('/cotizaciones-bordado', $datos);

        // Verificar que la respuesta es exitosa
        $this->assertEquals(201, $response->status());
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);

        // Obtener ID de la cotización creada
        $cotizacionId = $responseData['data']['id'];

        // Verificar que la cotización se creó
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion);
        $this->assertEquals($this->cliente->id, $cotizacion->cliente_id);
        $this->assertTrue($cotizacion->es_borrador);

        // Verificar que logo_cotizaciones se creó
        $logoCotizacion = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        $this->assertNotNull($logoCotizacion);
        
        // Verificar descripción
        $this->assertEquals('prueba de logo', $logoCotizacion->descripcion);
        
        // Verificar técnicas
        $tecnicas = is_string($logoCotizacion->tecnicas) 
            ? json_decode($logoCotizacion->tecnicas, true)
            : $logoCotizacion->tecnicas;
        $this->assertIsArray($tecnicas);
        $this->assertCount(2, $tecnicas);
        $this->assertContains('BORDADO', $tecnicas);
        $this->assertContains('DTF', $tecnicas);
        
        // Verificar ubicaciones
        $ubicaciones = is_string($logoCotizacion->ubicaciones)
            ? json_decode($logoCotizacion->ubicaciones, true)
            : $logoCotizacion->ubicaciones;
        $this->assertIsArray($ubicaciones);
        $this->assertCount(2, $ubicaciones);
        
        // Verificar observaciones generales
        $observaciones = is_string($logoCotizacion->observaciones_generales)
            ? json_decode($logoCotizacion->observaciones_generales, true)
            : $logoCotizacion->observaciones_generales;
        $this->assertIsArray($observaciones);
        $this->assertCount(2, $observaciones);
    }

    /**
     * Test: Actualizar cotización de bordado (borrador)
     */
    public function test_actualizar_cotizacion_bordado_borrador()
    {
        $this->actingAs($this->user);

        // Crear cotización inicial
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'cliente_id' => $this->cliente->id,
            'tipo_cotizacion_id' => 2,
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear logo_cotizacion inicial
        LogoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'descripción inicial',
            'tecnicas' => json_encode(['BORDADO']),
        ]);

        // Actualizar con nuevos datos
        $datos = [
            '_method' => 'PUT',
            'cliente' => 'MINCIVIL',
            'cliente_id' => $this->cliente->id,
            'descripcion' => 'descripción actualizada',
            'asesora' => $this->user->name,
            'fecha' => '2025-12-16',
            'action' => 'borrador',
            'accion' => 'borrador',
            'observaciones_tecnicas' => 'observaciones actualizadas',
            'tecnicas' => json_encode(['BORDADO', 'DTF', 'SUBLIMACION']),
            'ubicaciones' => json_encode([
                ['tipo' => 'PRENDA', 'nombre' => 'PECHO'],
            ]),
            'observaciones_generales' => json_encode([
                ['tipo' => 'PRENDA', 'observacion' => 'Nueva obs'],
            ]),
        ];

        $response = $this->put("/cotizaciones-bordado/{$cotizacion->id}/borrador", $datos);

        // Verificar que la respuesta es exitosa
        $this->assertEquals(200, $response->status());
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);

        // Verificar que la cotización se actualizó
        $cotizacion->refresh();
        $this->assertEquals($this->cliente->id, $cotizacion->cliente_id);

        // Verificar que logo_cotizaciones se actualizó (no se creó uno nuevo)
        $logoCotizaciones = LogoCotizacion::where('cotizacion_id', $cotizacion->id)->get();
        $this->assertCount(1, $logoCotizaciones, 'Debe haber exactamente 1 registro de logo_cotizaciones');

        $logoCotizacion = $logoCotizaciones->first();
        
        // Verificar descripción actualizada
        $this->assertEquals('descripción actualizada', $logoCotizacion->descripcion);
        
        // Verificar técnicas actualizadas
        $tecnicas = is_string($logoCotizacion->tecnicas)
            ? json_decode($logoCotizacion->tecnicas, true)
            : $logoCotizacion->tecnicas;
        $this->assertIsArray($tecnicas);
        $this->assertCount(3, $tecnicas);
        $this->assertContains('SUBLIMACION', $tecnicas);
    }
}
