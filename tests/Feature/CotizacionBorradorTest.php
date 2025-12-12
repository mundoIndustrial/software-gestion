<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaFotoCot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CotizacionBorradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_cotizacion_borrador_sin_numero()
    {
        // Crear una cotización en borrador
        $cotizacion = Cotizacion::create([
            'asesor_id' => 1,
            'cliente_id' => null,
            'numero_cotizacion' => null,  // Sin número para borrador
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
            'tipo_venta' => 'M',
            'cliente' => 'Cliente Test Borrador',
        ]);

        $this->assertNotNull($cotizacion->id);
        $this->assertNull($cotizacion->numero_cotizacion);
        $this->assertTrue($cotizacion->es_borrador);
        $this->assertEquals('BORRADOR', $cotizacion->estado);

        echo "\n✅ Cotización borrador creada sin número: ID {$cotizacion->id}\n";
    }

    public function test_guardar_cotizacion_borrador_con_prendas()
    {
        // Crear cotización borrador
        $cotizacion = Cotizacion::create([
            'asesor_id' => 1,
            'cliente_id' => null,
            'numero_cotizacion' => null,
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
            'tipo_venta' => 'M',
            'cliente' => 'Cliente Test',
        ]);

        // Agregar prenda
        $prenda = $cotizacion->prendasCotizaciones()->create([
            'nombre_producto' => 'Camisa Test',
            'descripcion' => 'Camisa de prueba',
            'cantidad' => 1,
        ]);

        $this->assertNotNull($prenda->id);
        $this->assertEquals('Camisa Test', $prenda->nombre_producto);

        echo "\n✅ Prenda agregada a cotización borrador: {$prenda->nombre_producto}\n";
    }

    public function test_guardar_cotizacion_borrador_con_imagenes()
    {
        Storage::fake('public');

        // Crear cotización borrador
        $cotizacion = Cotizacion::create([
            'asesor_id' => 1,
            'cliente_id' => null,
            'numero_cotizacion' => null,
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
            'tipo_venta' => 'M',
            'cliente' => 'Cliente Test',
        ]);

        // Agregar prenda
        $prenda = $cotizacion->prendasCotizaciones()->create([
            'nombre_producto' => 'Camisa Test',
            'descripcion' => 'Camisa de prueba',
            'cantidad' => 1,
        ]);

        // Agregar foto
        $foto = $prenda->fotos()->create([
            'ruta_original' => '/storage/cotizaciones/' . $cotizacion->id . '/prendas/test.webp',
            'ruta_webp' => '/storage/cotizaciones/' . $cotizacion->id . '/prendas/test.webp',
            'orden' => 1,
        ]);

        $this->assertNotNull($foto->id);
        $this->assertEquals(1, $prenda->fotos()->count());

        echo "\n✅ Foto agregada a prenda en cotización borrador\n";
    }

    public function test_cotizacion_borrador_tiene_todos_datos()
    {
        // Crear cotización borrador completa
        $cotizacion = Cotizacion::create([
            'asesor_id' => 1,
            'cliente_id' => null,
            'numero_cotizacion' => null,
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
            'tipo_venta' => 'M',
            'cliente' => 'Cliente Test',
            'especificaciones' => json_encode([
                'disponibilidad' => [
                    ['valor' => 'Bodega', 'observacion' => 'Disponible']
                ]
            ]),
        ]);

        // Agregar prenda
        $prenda = $cotizacion->prendasCotizaciones()->create([
            'nombre_producto' => 'Camisa',
            'descripcion' => 'Test',
            'cantidad' => 1,
        ]);

        // Agregar foto
        $prenda->fotos()->create([
            'ruta_original' => '/storage/test.webp',
            'ruta_webp' => '/storage/test.webp',
            'orden' => 1,
        ]);

        // Agregar talla
        $prenda->tallas()->create([
            'talla' => 'M',
            'cantidad' => 5,
        ]);

        // Verificar que todo está guardado
        $this->assertNull($cotizacion->numero_cotizacion);
        $this->assertTrue($cotizacion->es_borrador);
        $this->assertEquals(1, $cotizacion->prendasCotizaciones()->count());
        $this->assertEquals(1, $prenda->fotos()->count());
        $this->assertEquals(1, $prenda->tallas()->count());

        echo "\n✅ Cotización borrador completa con prendas, fotos y tallas\n";
    }
}
