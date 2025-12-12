<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\ReflectivoCotizacion;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReflectivoCotizacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test: Guardar reflectivo con descripción y ubicación
     */
    public function test_guardar_reflectivo_con_descripcion_y_ubicacion()
    {
        // Obtener una cotización existente
        $cotizacion = Cotizacion::first();
        
        if (!$cotizacion) {
            $this->markTestSkipped('No hay cotizaciones en la base de datos');
        }

        $timestamp = time();
        
        // Datos del reflectivo
        $datosReflectivo = [
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Reflectivo de seguridad para prendas - Test {$timestamp}",
            'ubicacion' => 'PECHO',
            'observaciones_generales' => json_encode([
                ['tipo' => 'texto', 'valor' => 'Observación importante'],
            ]),
        ];

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create($datosReflectivo);

        // Verificar que se guardó correctamente
        $this->assertDatabaseHas('reflectivo_cotizacion', [
            'id' => $reflectivo->id,
            'cotizacion_id' => $cotizacion->id,
            'ubicacion' => 'PECHO',
        ]);

        // Verificar relación
        $this->assertEquals($cotizacion->id, $reflectivo->cotizacion_id);
        $this->assertTrue($reflectivo->cotizacion()->exists());
        
        echo "\n✅ Test 1 PASSED: Reflectivo guardado correctamente\n";
    }

    /**
     * Test: Guardar reflectivo con imágenes (máximo 3)
     */
    public function test_guardar_reflectivo_con_imagenes()
    {
        // Obtener una cotización existente
        $cotizacion = Cotizacion::first();
        
        if (!$cotizacion) {
            $this->markTestSkipped('No hay cotizaciones en la base de datos');
        }

        $timestamp = time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Reflectivo con imágenes - Test {$timestamp}",
            'ubicacion' => 'ESPALDA',
        ]);

        // Crear 3 imágenes
        for ($i = 1; $i <= 3; $i++) {
            $reflectivo->fotos()->create([
                'ruta_original' => "cotizaciones/{$cotizacion->id}/reflectivo/imagen_{$i}.jpg",
                'ruta_webp' => "cotizaciones/{$cotizacion->id}/reflectivo/imagen_{$i}.webp",
                'orden' => $i,
            ]);
        }

        // Verificar que se guardaron las 3 imágenes
        $this->assertEquals(3, $reflectivo->fotos()->count());

        // Verificar orden de las imágenes
        $fotos = $reflectivo->fotos()->orderBy('orden')->get();
        foreach ($fotos as $index => $foto) {
            $this->assertEquals($index + 1, $foto->orden);
        }

        // Verificar en base de datos
        $this->assertDatabaseHas('reflectivo_fotos_cotizacion', [
            'reflectivo_cotizacion_id' => $reflectivo->id,
            'orden' => 1,
        ]);
        
        echo "\n✅ Test 2 PASSED: Reflectivo con imágenes guardado correctamente\n";
    }

    /**
     * Test: Relación inversa - Cotización tiene reflectivo
     */
    public function test_cotizacion_tiene_reflectivo()
    {
        // Obtener una cotización existente
        $cotizacion = Cotizacion::first();
        
        if (!$cotizacion) {
            $this->markTestSkipped('No hay cotizaciones en la base de datos');
        }

        $timestamp = time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Reflectivo de prueba - Test {$timestamp}",
            'ubicacion' => 'CUELLO',
        ]);

        // Recargar cotización para obtener la relación actualizada
        $cotizacion->refresh();

        // Verificar relación desde cotización
        $this->assertTrue($cotizacion->reflectivo()->exists());
        $this->assertEquals($reflectivo->cotizacion_id, $cotizacion->id);
        $this->assertNotNull($cotizacion->reflectivo);
        
        echo "\n✅ Test 3 PASSED: Relación cotización-reflectivo correcta\n";
    }

    /**
     * Test: Guardar observaciones generales en JSON
     */
    public function test_guardar_observaciones_generales_json()
    {
        // Obtener una cotización existente
        $cotizacion = Cotizacion::first();
        
        if (!$cotizacion) {
            $this->markTestSkipped('No hay cotizaciones en la base de datos');
        }

        $timestamp = time();

        // Observaciones como array
        $observaciones = [
            ['tipo' => 'texto', 'valor' => 'Primera observación'],
            ['tipo' => 'checkbox', 'valor' => 'Segunda observación'],
        ];

        // Crear reflectivo con observaciones
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Reflectivo con observaciones - Test {$timestamp}",
            'ubicacion' => 'COSTADO',
            'observaciones_generales' => json_encode($observaciones),
        ]);

        // Recargar para verificar que se guardó correctamente
        $reflectivo->refresh();

        // Verificar que se guardó en la base de datos
        $this->assertDatabaseHas('reflectivo_cotizacion', [
            'id' => $reflectivo->id,
            'cotizacion_id' => $cotizacion->id,
        ]);

        // Decodificar manualmente para verificar contenido
        $obsDecodificadas = json_decode($reflectivo->observaciones_generales, true);
        $this->assertIsArray($obsDecodificadas);
        $this->assertCount(2, $obsDecodificadas);
        $this->assertEquals('Primera observación', $obsDecodificadas[0]['valor']);
        $this->assertEquals('Segunda observación', $obsDecodificadas[1]['valor']);
        
        echo "\n✅ Test 4 PASSED: Observaciones generales guardadas correctamente en JSON\n";
    }

    /**
     * Test: Actualizar reflectivo existente
     */
    public function test_actualizar_reflectivo_existente()
    {
        // Obtener una cotización existente
        $cotizacion = Cotizacion::first();
        
        if (!$cotizacion) {
            $this->markTestSkipped('No hay cotizaciones en la base de datos');
        }

        $timestamp = time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => "Descripción original - Test {$timestamp}",
            'ubicacion' => 'PECHO',
        ]);

        // Actualizar
        $reflectivo->update([
            'descripcion' => "Descripción actualizada - Test {$timestamp}",
            'ubicacion' => 'ESPALDA',
        ]);

        // Verificar actualización
        $this->assertDatabaseHas('reflectivo_cotizacion', [
            'id' => $reflectivo->id,
            'ubicacion' => 'ESPALDA',
        ]);
        
        echo "\n✅ Test 5 PASSED: Reflectivo actualizado correctamente\n";
    }
}
