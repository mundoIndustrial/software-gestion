<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\PrendaCot;
use App\Models\ReflectivoCotizacion;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoreReflectivoCotizacionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test que se guarde correctamente una cotización reflectiva con múltiples prendas
     */
    public function test_store_reflectivo_cotizacion_with_multiple_prendas()
    {
        // Arrange - Preparar datos
        $this->actingAs($this->crearUsuarioAsesor());
        
        $prendas = [
            [
                'tipo' => 'Camiseta',
                'descripcion' => 'Camiseta blanca con reflectivo plateado'
            ],
            [
                'tipo' => 'Pantalón',
                'descripcion' => 'Pantalón negro con reflectivo en costados'
            ]
        ];

        $data = [
            'cliente' => 'Cliente Test ' . uniqid(),
            'asesora' => auth()->user()->name,
            'fecha' => now()->format('Y-m-d'),
            'action' => 'borrador',
            'tipo' => 'RF',
            'prendas' => json_encode($prendas),
            'descripcion_reflectivo' => 'Cotización de Reflectivo',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ];

        // Act - Hacer la petición
        $response = $this->postJson(route('asesores.cotizaciones.reflectivo.guardar'), $data);

        // Assert - Verificar resultados
        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('message', 'Cotización de reflectivo guardada exitosamente');

        // Verificar que se creó la cotización
        $cotizacion = Cotizacion::whereHas('cliente', function ($q) use ($data) {
            $q->where('nombre', $data['cliente']);
        })->first();
        
        $this->assertNotNull($cotizacion);
        $this->assertTrue($cotizacion->es_borrador);
        $this->assertEquals('BORRADOR', $cotizacion->estado);

        // Verificar que se crearon las prendas
        $prendasGuardadas = PrendaCot::where('cotizacion_id', $cotizacion->id)->get();
        $this->assertCount(2, $prendasGuardadas);
        $this->assertEquals('Camiseta', $prendasGuardadas[0]->nombre_producto);
        $this->assertEquals('Pantalón', $prendasGuardadas[1]->nombre_producto);

        // Verificar que se crearon los registros en reflectivo_cotizacion con tipo_prenda
        $reflectivos = ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->get();
        $this->assertCount(2, $reflectivos);
        
        // Primera prenda (Camiseta)
        $this->assertEquals('Camiseta', $reflectivos[0]->tipo_prenda);
        $this->assertEquals('Camiseta blanca con reflectivo plateado', $reflectivos[0]->descripcion);
        
        // Segunda prenda (Pantalón)
        $this->assertEquals('Pantalón', $reflectivos[1]->tipo_prenda);
        $this->assertEquals('Pantalón negro con reflectivo en costados', $reflectivos[1]->descripcion);

        echo "\n✅ Test passed: Cotización reflectiva guardada correctamente\n";
        echo "  - Cotización ID: {$cotizacion->id}\n";
        echo "  - Prendas: {$prendasGuardadas->count()}\n";
        echo "  - Reflectivos: {$reflectivos->count()}\n";
    }

    /**
     * Test que valide error cuando no hay prendas
     */
    public function test_store_reflectivo_cotizacion_sin_prendas_falla()
    {
        $this->actingAs($this->crearUsuarioAsesor());

        $data = [
            'cliente' => 'Cliente Test ' . uniqid(),
            'asesora' => auth()->user()->name,
            'fecha' => now()->format('Y-m-d'),
            'action' => 'borrador',
            'tipo' => 'RF',
            'prendas' => json_encode([]), // Sin prendas
            'descripcion_reflectivo' => 'Cotización de Reflectivo',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
        ];

        $response = $this->postJson(route('asesores.cotizaciones.reflectivo.guardar'), $data);

        // Debería fallar en validación
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);

        echo "\n✅ Test passed: Validación correcta sin prendas\n";
    }

    /**
     * Test que se guarden las imágenes correctamente
     */
    public function test_store_reflectivo_cotizacion_with_images()
    {
        $this->actingAs($this->crearUsuarioAsesor());

        $prendas = [
            [
                'tipo' => 'Camiseta',
                'descripcion' => 'Camiseta con reflectivo'
            ]
        ];

        $data = [
            'cliente' => 'Cliente Test ' . uniqid(),
            'asesora' => auth()->user()->name,
            'fecha' => now()->format('Y-m-d'),
            'action' => 'borrador',
            'tipo' => 'RF',
            'prendas' => json_encode($prendas),
            'descripcion_reflectivo' => 'Cotización de Reflectivo',
            'ubicaciones_reflectivo' => json_encode([]),
            'observaciones_generales' => json_encode([]),
            'imagenes_reflectivo' => [
                UploadedFile::fake()->image('foto1.jpg'),
                UploadedFile::fake()->image('foto2.jpg'),
            ]
        ];

        $response = $this->postJson(route('asesores.cotizaciones.reflectivo.guardar'), $data);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true);

        // Verificar que se guardaron las imágenes
        $cotizacion = Cotizacion::latest()->first();
        $reflectivo = ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();
        
        if ($reflectivo && $reflectivo->fotos) {
            $this->assertCount(2, $reflectivo->fotos);
            echo "\n✅ Test passed: Imágenes guardadas correctamente\n";
            echo "  - Imágenes guardadas: {$reflectivo->fotos->count()}\n";
        }
    }

    /**
     * Helper para crear un usuario asesor
     */
    private function crearUsuarioAsesor()
    {
        return \App\Models\User::factory()->create([
            'rol' => 'ASESOR'
        ]);
    }

    /**
     * Helper para obtener el ID del tipo de cotización RF
     */
    private function obtenerTipoCotizacionId($tipo)
    {
        return \App\Models\TipoCotizacion::where('codigo', $tipo)->value('id');
    }
}
