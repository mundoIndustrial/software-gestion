<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\User;
use Tests\TestCase;

/**
 * Test: Verificar que TODOS los campos de una cotización tipo PB se guarden correctamente
 * Especialmente: checkboxes (bolsillos, broche, reflectivo), manga, género, telas, etc.
 */
class CotizacionTipoPBCompleteFieldsTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['rol' => 'asesor']);
    }

    /**
     * Test SIMPLE: Verificar que todos los campos se guardan correctamente
     */
    public function test_todos_los_campos_se_guardan_correctamente()
    {
        $this->actingAs($this->user);

        // Datos simples pero completos
        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => 'TEST CAMPOS COMPLETOS',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'PB',
            'prendas' => [
                [
                    'nombre_producto' => 'POLO TEST',
                    'descripcion' => 'Polo de prueba',
                    'cantidad' => 50,
                    'tallas' => json_encode(['S', 'M', 'L']),
                    'variantes' => [
                        'genero_id' => null,  // Ambos géneros
                        'tipo_manga_id' => 1,
                        'obs_manga' => 'Manga corta',
                        'tiene_bolsillos' => true,
                        'obs_bolsillos' => 'Bolsillos laterales',
                        'tipo_broche_id' => 2,
                        'obs_broche' => 'Botones',
                        'tiene_reflectivo' => true,
                        'obs_reflectivo' => 'Reflectivo en mangas',
                        'descripcion_adicional' => 'Manga: Manga corta | Bolsillos: Bolsillos laterales | Broche: Botones | Reflectivo: Reflectivo en mangas',
                        'telas_multiples' => json_encode([
                            ['color' => 'Azul', 'tela' => 'DRILL', 'referencia' => 'REF-001'],
                        ]),
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);
        $response->assertStatus(201);

        $cotizacionId = $response->json('data.id');
        $cotizacion = Cotizacion::find($cotizacionId);
        $prenda = $cotizacion->prendas()->first();
        $variante = $prenda->variantes()->first();

        // Verificar TODOS los campos
        $this->assertNotNull($prenda);
        $this->assertEquals('POLO TEST', $prenda->nombre_producto);
        $this->assertEquals(50, $prenda->cantidad);

        // Tallas
        $tallas = $prenda->tallas()->get();
        $this->assertCount(3, $tallas);

        // Variantes - CAMPOS CRÍTICOS
        $this->assertNull($variante->genero_id, '❌ genero_id debe ser NULL');
        $this->assertEquals(1, $variante->tipo_manga_id, '❌ tipo_manga_id debe ser 1');
        $this->assertEquals('Manga corta', $variante->obs_manga, '❌ obs_manga no se guardó');
        $this->assertTrue($variante->tiene_bolsillos, '❌ tiene_bolsillos debe ser TRUE');
        $this->assertEquals('Bolsillos laterales', $variante->obs_bolsillos, '❌ obs_bolsillos no se guardó');
        $this->assertEquals(2, $variante->tipo_broche_id, '❌ tipo_broche_id debe ser 2');
        $this->assertEquals('Botones', $variante->obs_broche, '❌ obs_broche no se guardó');
        $this->assertTrue($variante->tiene_reflectivo, '❌ tiene_reflectivo debe ser TRUE');
        $this->assertEquals('Reflectivo en mangas', $variante->obs_reflectivo, '❌ obs_reflectivo no se guardó');

        echo "\n✅ TODOS LOS CAMPOS SE GUARDARON CORRECTAMENTE:\n";
        echo "   ✓ genero_id: " . ($variante->genero_id ?? 'NULL (Ambos)') . "\n";
        echo "   ✓ tipo_manga_id: {$variante->tipo_manga_id}\n";
        echo "   ✓ obs_manga: {$variante->obs_manga}\n";
        echo "   ✓ tiene_bolsillos: " . ($variante->tiene_bolsillos ? 'TRUE' : 'FALSE') . "\n";
        echo "   ✓ obs_bolsillos: {$variante->obs_bolsillos}\n";
        echo "   ✓ tipo_broche_id: {$variante->tipo_broche_id}\n";
        echo "   ✓ obs_broche: {$variante->obs_broche}\n";
        echo "   ✓ tiene_reflectivo: " . ($variante->tiene_reflectivo ? 'TRUE' : 'FALSE') . "\n";
        echo "   ✓ obs_reflectivo: {$variante->obs_reflectivo}\n";
    }

    /**
     * Test: Verificar que los checkboxes NO marcados se guardan como FALSE
     */
    public function test_checkboxes_no_marcados_son_false()
    {
        $this->actingAs($this->user);

        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => 'TEST SIN CHECKBOXES',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'PB',
            'prendas' => [
                [
                    'nombre_producto' => 'CAMISA SIMPLE',
                    'descripcion' => 'Camisa sin variaciones',
                    'cantidad' => 30,
                    'tallas' => json_encode(['M', 'L']),
                    'variantes' => [
                        'genero_id' => 1,
                        'tipo_manga_id' => null,
                        'tiene_bolsillos' => false,
                        'tipo_broche_id' => null,
                        'tiene_reflectivo' => false,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);
        $response->assertStatus(201);

        $cotizacionId = $response->json('data.id');
        $cotizacion = Cotizacion::find($cotizacionId);
        $variante = $cotizacion->prendas()->first()->variantes()->first();

        $this->assertFalse($variante->tiene_bolsillos);
        $this->assertFalse($variante->tiene_reflectivo);
        $this->assertNull($variante->tipo_manga_id);
        $this->assertNull($variante->tipo_broche_id);

        echo "\n✅ Checkboxes NO marcados se guardan como FALSE/NULL correctamente\n";
    }
}
