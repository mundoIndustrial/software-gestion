<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;

class CotizacionVariantesTest extends TestCase
{
    /**
     * Test: Verificar que las variantes se guardan correctamente
     */
    public function test_variantes_se_guardan_correctamente()
    {
        // Crear una cotización
        $cotizacion = Cotizacion::create([
            'numero_cotizacion' => 'TEST-001',
            'usuario_id' => 1,
            'cliente_id' => 1,
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        // Crear una prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'CAMISA TEST',
            'descripcion' => 'Camisa de prueba',
            'cantidad' => 1,
        ]);

        // Crear una variante con tipo_manga_id
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'genero_id' => 2,
            'color' => 'Naranja',
            'tipo_manga_id' => 1,
            'tipo_broche_id' => 2,
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Prueba bolsillos',
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Prueba reflectivo',
            'obs_manga' => 'Prueba manga',
            'obs_broche' => 'Prueba broche',
            'descripcion_adicional' => 'Manga: Prueba manga | Bolsillos: Prueba bolsillos',
            'telas_multiples' => json_encode([
                [
                    'indice' => 0,
                    'color' => 'Naranja',
                    'tela' => 'DRILL BORNEO',
                    'referencia' => 'REF-DB-001'
                ]
            ]),
        ]);

        // Verificar que la variante se guardó
        $this->assertNotNull($variante->id);
        echo "\n✅ Variante creada con ID: {$variante->id}\n";

        // Recargar desde BD
        $varianteRecargada = PrendaVarianteCot::find($variante->id);
        
        // Verificar que los datos se guardaron correctamente
        $this->assertEquals(2, $varianteRecargada->genero_id);
        echo "✅ genero_id: {$varianteRecargada->genero_id}\n";
        
        $this->assertEquals('Naranja', $varianteRecargada->color);
        echo "✅ color: {$varianteRecargada->color}\n";
        
        $this->assertEquals(1, $varianteRecargada->tipo_manga_id);
        echo "✅ tipo_manga_id: {$varianteRecargada->tipo_manga_id}\n";
        
        $this->assertEquals(2, $varianteRecargada->tipo_broche_id);
        echo "✅ tipo_broche_id: {$varianteRecargada->tipo_broche_id}\n";
        
        $this->assertTrue($varianteRecargada->tiene_bolsillos);
        echo "✅ tiene_bolsillos: {$varianteRecargada->tiene_bolsillos}\n";
        
        // Verificar telas_multiples (debe ser array gracias al cast)
        $this->assertIsArray($varianteRecargada->telas_multiples);
        echo "✅ telas_multiples es array\n";
        
        $this->assertEquals('DRILL BORNEO', $varianteRecargada->telas_multiples[0]['tela']);
        echo "✅ tela: {$varianteRecargada->telas_multiples[0]['tela']}\n";
        
        $this->assertEquals('REF-DB-001', $varianteRecargada->telas_multiples[0]['referencia']);
        echo "✅ referencia: {$varianteRecargada->telas_multiples[0]['referencia']}\n";

        echo "\n✅ TODOS LOS TESTS PASARON\n";
    }

    /**
     * Test: Verificar relación con prenda
     */
    public function test_variante_tiene_relacion_con_prenda()
    {
        $cotizacion = Cotizacion::create([
            'numero_cotizacion' => 'TEST-002',
            'usuario_id' => 1,
            'cliente_id' => 1,
            'tipo' => 'P',
            'estado' => 'BORRADOR',
            'es_borrador' => true,
        ]);

        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'POLO TEST',
            'descripcion' => 'Polo de prueba',
            'cantidad' => 1,
        ]);

        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'genero_id' => 1,
            'color' => 'Azul',
            'tipo_manga_id' => 2,
        ]);

        // Verificar relación
        $this->assertEquals($prenda->id, $variante->prenda->id);
        echo "\n✅ Relación con prenda correcta\n";
    }
}
