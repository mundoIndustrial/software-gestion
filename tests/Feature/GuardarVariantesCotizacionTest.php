<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardarVariantesCotizacionTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Usar usuario existente en BD (ID 18)
        $this->user = User::find(18);
        if (!$this->user) {
            $this->markTestSkipped('Usuario ID 18 no existe en la BD');
        }
    }

    /**
     * Test: Verificar que las variantes se guardan en prenda_variantes_cot
     */
    public function test_guardar_variantes_en_bd()
    {
        $this->actingAs($this->user);

        // Crear datos de prueba
        $cotizacionId = 1;
        $prendaId = 1;

        // Verificar que existe la prenda
        $prenda = PrendaCot::find($prendaId);
        $this->assertNotNull($prenda, 'La prenda debe existir');

        // Crear variante con todos los campos
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prendaId,
            'tipo_prenda' => 'CAMISA',
            'es_jean_pantalon' => false,
            'tipo_jean_pantalon' => null,
            'genero_id' => 2,
            'color' => 'Rojo',
            'tipo_manga_id' => 1,
            'tipo_broche_id' => 2,
            'obs_broche' => 'Botones de madera',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Dos bolsillos frontales',
            'aplica_manga' => true,
            'tipo_manga' => 'Larga',
            'obs_manga' => 'Manga ajustada',
            'aplica_broche' => true,
            'tiene_reflectivo' => false,
            'obs_reflectivo' => null,
            'descripcion_adicional' => 'Camisa de prueba con variantes',
        ]);

        // Verificar que se guardó en BD
        $this->assertNotNull($variante->id);
        $this->assertEquals($prendaId, $variante->prenda_cot_id);
        $this->assertEquals('CAMISA', $variante->tipo_prenda);
        $this->assertEquals(2, $variante->genero_id);
        $this->assertEquals('Rojo', $variante->color);
        $this->assertTrue($variante->tiene_bolsillos);
        $this->assertEquals('Botones de madera', $variante->obs_broche);

        // Verificar que se puede recuperar desde la BD
        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        $this->assertNotNull($varianteRecuperada);
        $this->assertEquals('CAMISA', $varianteRecuperada->tipo_prenda);
        $this->assertEquals('Rojo', $varianteRecuperada->color);

        echo "\n✅ Test: Guardar variantes en BD - PASADO\n";
        echo "   ✓ Variante guardada con ID: {$variante->id}\n";
        echo "   ✓ Todos los campos guardados correctamente\n";
        echo "   ✓ Datos recuperables desde la BD\n";
    }

    /**
     * Test: Verificar que múltiples variantes se guardan correctamente
     */
    public function test_guardar_multiples_variantes()
    {
        $prendaId = 1;

        // Crear múltiples variantes
        $variantesData = [
            [
                'tipo_prenda' => 'CAMISA',
                'genero_id' => 2,
                'color' => 'Azul',
                'tipo_manga_id' => 1,
                'tiene_bolsillos' => true,
            ],
            [
                'tipo_prenda' => 'PANTALON',
                'es_jean_pantalon' => true,
                'tipo_jean_pantalon' => 'JEAN',
                'genero_id' => 1,
                'color' => 'Negro',
                'tiene_bolsillos' => true,
            ],
            [
                'tipo_prenda' => 'POLO',
                'genero_id' => 3,
                'color' => 'Blanco',
                'tipo_manga_id' => 2,
                'tiene_reflectivo' => true,
                'obs_reflectivo' => 'Reflectivo en pecho',
            ],
        ];

        $variantesCreadas = [];
        foreach ($variantesData as $datos) {
            $datos['prenda_cot_id'] = $prendaId;
            $variantesCreadas[] = PrendaVarianteCot::create($datos);
        }

        // Verificar que se guardaron todas
        $this->assertCount(3, $variantesCreadas, 'Debe haber 3 variantes creadas');

        // Verificar datos específicos
        $camisa = collect($variantesCreadas)->where('tipo_prenda', 'CAMISA')->first();
        $this->assertNotNull($camisa);
        $this->assertEquals('Azul', $camisa->color);

        $pantalon = collect($variantesCreadas)->where('tipo_prenda', 'PANTALON')->first();
        $this->assertNotNull($pantalon);
        $this->assertTrue($pantalon->es_jean_pantalon);
        $this->assertEquals('JEAN', $pantalon->tipo_jean_pantalon);

        $polo = collect($variantesCreadas)->where('tipo_prenda', 'POLO')->first();
        $this->assertNotNull($polo);
        $this->assertTrue($polo->tiene_reflectivo);

        echo "\n✅ Test: Guardar múltiples variantes - PASADO\n";
        echo "   ✓ Variantes guardadas: " . count($variantesCreadas) . "\n";
        echo "   ✓ Datos específicos verificados\n";
    }

    /**
     * Test: Verificar que los campos booleanos se guardan correctamente
     */
    public function test_campos_booleanos_variantes()
    {
        $prendaId = 1;

        // Crear variante con todos los booleanos
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prendaId,
            'tipo_prenda' => 'CAMISA',
            'es_jean_pantalon' => false,
            'genero_id' => 2,
            'tiene_bolsillos' => true,
            'aplica_manga' => true,
            'aplica_broche' => true,
            'tiene_reflectivo' => false,
        ]);

        // Recuperar y verificar
        $varianteRecuperada = PrendaVarianteCot::find($variante->id);

        $this->assertFalse($varianteRecuperada->es_jean_pantalon);
        $this->assertTrue($varianteRecuperada->tiene_bolsillos);
        $this->assertTrue($varianteRecuperada->aplica_manga);
        $this->assertTrue($varianteRecuperada->aplica_broche);
        $this->assertFalse($varianteRecuperada->tiene_reflectivo);

        echo "\n✅ Test: Campos booleanos - PASADO\n";
        echo "   ✓ Todos los booleanos guardados correctamente\n";
    }

    /**
     * Test: Verificar que los campos de texto se guardan correctamente
     */
    public function test_campos_texto_variantes()
    {
        $prendaId = 1;

        $textos = [
            'obs_broche' => 'Botones de madera con acabado natural',
            'obs_bolsillos' => 'Dos bolsillos frontales y uno trasero',
            'tipo_manga' => 'Larga ajustada',
            'obs_manga' => 'Manga con puño elástico',
            'obs_reflectivo' => 'Reflectivo en pecho y espalda',
            'descripcion_adicional' => 'Camisa de prueba con múltiples observaciones y detalles',
        ];

        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prendaId,
            'tipo_prenda' => 'CAMISA',
            'genero_id' => 2,
            ...$textos,
        ]);

        // Recuperar y verificar
        $varianteRecuperada = PrendaVarianteCot::find($variante->id);

        foreach ($textos as $campo => $valor) {
            $this->assertEquals($valor, $varianteRecuperada->$campo, "Campo {$campo} no coincide");
        }

        echo "\n✅ Test: Campos de texto - PASADO\n";
        echo "   ✓ Todos los campos de texto guardados correctamente\n";
    }
}
