<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\ReflectivoCotizacion;
use App\Models\ReflectivofotoCotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ReflectivoGuardadoTest extends TestCase
{
    use WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear o obtener un usuario para autenticación
        $this->user = User::first() ?? User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test: Guardar reflectivo básico en tabla reflectivo_cotizacion
     */
    public function test_guardar_reflectivo_basico()
    {
        // Descripción única para este test
        $descripcionUnica = 'Descripción Test Básico ' . time();

        // Crear una cotización primero
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Crear reflectivo directamente en la BD
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([['ubicacion' => 'PECHO', 'descripcion' => 'Centro del pecho']]),
            'observaciones_generales' => json_encode([['texto' => 'Observación 1', 'tipo' => 'texto', 'valor' => '']]),
        ]);

        // Verificar que se guardó en reflectivo_cotizacion
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        
        $this->assertNotNull($reflectivoGuardado, 'El reflectivo no se guardó en la tabla reflectivo_cotizacion');
        $this->assertEquals($descripcionUnica, $reflectivoGuardado->descripcion);
        $this->assertEquals($cotizacion->id, $reflectivoGuardado->cotizacion_id);
        
        echo "\n✅ Test 1 PASADO: Reflectivo básico guardado en tabla reflectivo_cotizacion\n";
    }

    /**
     * Test: Guardar reflectivo con imágenes en tabla reflectivo_fotos_cotizacion
     */
    public function test_guardar_reflectivo_con_imagenes()
    {
        // Descripción única
        $descripcionUnica = 'Reflectivo con imágenes ' . time();

        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ]);

        // Crear 3 fotos
        for ($i = 1; $i <= 3; $i++) {
            ReflectivofotoCotizacion::create([
                'reflectivo_cotizacion_id' => $reflectivo->id,
                'ruta_original' => "storage/reflectivo/foto{$i}.jpg",
                'ruta_webp' => "storage/reflectivo/foto{$i}.webp",
                'orden' => $i,
            ]);
        }

        // Verificar que se guardó el reflectivo
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado, 'El reflectivo no se guardó');

        // Verificar que se guardaron las imágenes en reflectivo_fotos_cotizacion
        $fotos = $reflectivoGuardado->fotos;
        
        $this->assertCount(3, $fotos, 'No se guardaron las 3 imágenes');
        
        // Verificar que cada foto tiene orden
        foreach ($fotos as $index => $foto) {
            $this->assertNotNull($foto->ruta_original, "Foto {$index} no tiene ruta_original");
            $this->assertEquals($index + 1, $foto->orden, "Foto {$index} no tiene orden correcto");
        }

        echo "\n✅ Test 2 PASADO: Reflectivo con imágenes guardado correctamente\n";
    }

    /**
     * Test: Guardar reflectivo con ubicaciones
     */
    public function test_guardar_reflectivo_con_ubicaciones()
    {
        // Descripción única
        $descripcionUnica = 'Reflectivo con ubicaciones ' . time();

        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Ubicaciones
        $ubicaciones = [
            ['ubicacion' => 'PECHO', 'descripcion' => 'Centro del pecho'],
            ['ubicacion' => 'ESPALDA', 'descripcion' => 'Centro de la espalda']
        ];

        // Crear reflectivo con ubicaciones
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode($ubicaciones),
            'observaciones_generales' => json_encode([]),
        ]);

        // Verificar que se guardó
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar ubicaciones
        $ubicacionesGuardadas = json_decode($reflectivoGuardado->ubicacion, true);
        $this->assertIsArray($ubicacionesGuardadas);
        $this->assertCount(2, $ubicacionesGuardadas);

        echo "\n✅ Test 3 PASADO: Reflectivo con ubicaciones guardado correctamente\n";
    }

    /**
     * Test: Guardar reflectivo con observaciones generales
     */
    public function test_guardar_reflectivo_con_observaciones()
    {
        // Descripción única
        $descripcionUnica = 'Reflectivo con observaciones ' . time();

        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Observaciones
        $observaciones = [
            ['texto' => 'Observación Test 1', 'tipo' => 'texto', 'valor' => 'Valor 1'],
            ['texto' => 'Observación Test 2', 'tipo' => 'checkbox', 'valor' => true]
        ];

        // Crear reflectivo con observaciones
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([]),
            'observaciones_generales' => json_encode($observaciones),
        ]);

        // Verificar que se guardó
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar observaciones
        $observacionesGuardadas = json_decode($reflectivoGuardado->observaciones_generales, true);
        $this->assertIsArray($observacionesGuardadas);
        $this->assertCount(2, $observacionesGuardadas);
        $this->assertEquals('Observación Test 1', $observacionesGuardadas[0]['texto']);
        $this->assertEquals('texto', $observacionesGuardadas[0]['tipo']);

        echo "\n✅ Test 4 PASADO: Reflectivo con observaciones guardado correctamente\n";
    }

    /**
     * Test: Relación entre Cotizacion y ReflectivoCotizacion
     */
    public function test_relacion_cotizacion_reflectivo()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Descripción única
        $descripcionUnica = 'Test relación ' . time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ]);

        // Obtener el reflectivo guardado
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar que tiene cotizacion_id
        $this->assertNotNull($reflectivoGuardado->cotizacion_id);
        $this->assertEquals($cotizacion->id, $reflectivoGuardado->cotizacion_id);

        echo "\n✅ Test 5 PASADO: Relación Cotizacion-Reflectivo correcta\n";
    }

    /**
     * Test: Relación entre ReflectivoCotizacion y ReflectivofotoCotizacion
     */
    public function test_relacion_reflectivo_fotos()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Descripción única
        $descripcionUnica = 'Test relación fotos ' . time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ]);

        // Crear 2 fotos
        for ($i = 1; $i <= 2; $i++) {
            ReflectivofotoCotizacion::create([
                'reflectivo_cotizacion_id' => $reflectivo->id,
                'ruta_original' => "storage/reflectivo/foto{$i}.jpg",
                'ruta_webp' => "storage/reflectivo/foto{$i}.webp",
                'orden' => $i,
            ]);
        }

        // Obtener reflectivo
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar relación con fotos
        $fotos = $reflectivoGuardado->fotos;
        $this->assertCount(2, $fotos, 'El reflectivo no tiene 2 fotos');

        // Verificar que fotos están ordenadas
        $this->assertEquals(1, $fotos[0]->orden);
        $this->assertEquals(2, $fotos[1]->orden);

        echo "\n✅ Test 6 PASADO: Relación Reflectivo-Fotos correcta\n";
    }

    /**
     * Test: Máximo 3 imágenes
     */
    public function test_maximo_tres_imagenes()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Descripción única
        $descripcionUnica = 'Test máximo imágenes ' . time();

        // Crear reflectivo
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ]);

        // Crear 3 fotos (máximo permitido)
        for ($i = 1; $i <= 3; $i++) {
            ReflectivofotoCotizacion::create([
                'reflectivo_cotizacion_id' => $reflectivo->id,
                'ruta_original' => "storage/reflectivo/foto{$i}.jpg",
                'ruta_webp' => "storage/reflectivo/foto{$i}.webp",
                'orden' => $i,
            ]);
        }

        // Obtener reflectivo
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar que se guardaron exactamente 3 imágenes
        $fotos = $reflectivoGuardado->fotos;
        $this->assertCount(3, $fotos, 'No se guardaron exactamente 3 imágenes');

        echo "\n✅ Test 7 PASADO: Máximo 3 imágenes guardadas correctamente\n";
    }

    /**
     * Test: Información completa del reflectivo
     */
    public function test_informacion_completa_reflectivo()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'tipo' => 'RF',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Descripción única
        $descripcionUnica = 'Descripción completa del reflectivo ' . time();

        // Ubicaciones
        $ubicaciones = [
            ['ubicacion' => 'PECHO', 'descripcion' => 'Centro pecho'],
            ['ubicacion' => 'ESPALDA', 'descripcion' => 'Centro espalda']
        ];

        // Observaciones
        $observaciones = [
            ['texto' => 'Obs Test 1', 'tipo' => 'texto', 'valor' => 'Valor 1'],
            ['texto' => 'Obs Test 2', 'tipo' => 'checkbox', 'valor' => true]
        ];

        // Crear reflectivo con todos los datos
        $reflectivo = ReflectivoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => $descripcionUnica,
            'ubicacion' => json_encode($ubicaciones),
            'observaciones_generales' => json_encode($observaciones),
        ]);

        // Crear 1 foto
        ReflectivofotoCotizacion::create([
            'reflectivo_cotizacion_id' => $reflectivo->id,
            'ruta_original' => "storage/reflectivo/foto1.jpg",
            'ruta_webp' => "storage/reflectivo/foto1.webp",
            'orden' => 1,
        ]);

        // Obtener reflectivo
        $reflectivoGuardado = ReflectivoCotizacion::where('descripcion', $descripcionUnica)->first();
        $this->assertNotNull($reflectivoGuardado);

        // Verificar todos los datos
        $this->assertEquals($descripcionUnica, $reflectivoGuardado->descripcion);
        
        $ubicacionesGuardadas = json_decode($reflectivoGuardado->ubicacion, true);
        $this->assertCount(2, $ubicacionesGuardadas);
        
        $observacionesGuardadas = json_decode($reflectivoGuardado->observaciones_generales, true);
        $this->assertCount(2, $observacionesGuardadas);
        
        $fotos = $reflectivoGuardado->fotos;
        $this->assertCount(1, $fotos);

        echo "\n✅ Test 8 PASADO: Información completa del reflectivo guardada correctamente\n";
    }
}
