<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\PrendaTelaCot;
use App\Models\User;
use Tests\TestCase;

class CotizacionTipoPBTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['rol' => 'asesor']);
    }

    /**
     * Test: Crear cotización tipo PB con todos los campos sincronizados
     */
    public function test_crear_cotizacion_tipo_pb_con_campos_sincronizados()
    {
        $this->actingAs($this->user);

        $datosEnvio = [
            'tipo' => 'enviada',
            'cliente' => 'CLIENTE PB TEST',
            'tipo_venta' => 'M',
            'tipo_cotizacion' => 'PB',
            'especificaciones' => [
                'disponibilidad' => ['Bodega'],
                'forma_pago' => ['Contado'],
                'regimen' => ['Común'],
                'se_ha_vendido' => ['✓'],
                'ultima_venta' => ['✓'],
                'flete' => ['✓'],
            ],
            // Campos nuevos sincronizados
            'imagenes' => [
                'https://example.com/imagen1.jpg',
                'https://example.com/imagen2.jpg',
            ],
            'tecnicas' => ['Bordado', 'Estampado'],
            'observaciones_tecnicas' => 'Bordado en pecho, estampado en espalda',
            'ubicaciones' => [
                ['seccion' => 'Pecho', 'posicion' => 'Centro'],
                ['seccion' => 'Espalda', 'posicion' => 'Centro'],
            ],
            'observaciones_generales' => [
                ['texto' => 'Observación importante sobre la cotización', 'tipo' => 'texto'],
            ],
            'productos' => [
                [
                    'nombre' => 'POLO PIQUE',
                    'descripcion' => 'Polo de piqué premium',
                    'cantidad' => 1,
                    'tallas' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'fotos_desde_prendaConIndice' => [
                        'foto_polo_1.png',
                        'foto_polo_2.png',
                    ],
                    'telas' => [
                        [
                            'color_id' => 1,
                            'tela_id' => 1,
                        ],
                    ],
                    'variantes' => [
                        'genero_id' => 2,
                        'tipo_manga_id' => 1,
                        'tipo_broche_id' => 2,
                        'tiene_bolsillos' => false,
                        'tiene_reflectivo' => true,
                        'descripcion_adicional' => 'Manga: Corta | Bolsillos: No | Reflectivo: Sí',
                        'telas_multiples' => [
                            [
                                'color' => 'Azul',
                                'referencia' => 'REF-PIQUE-001',
                            ],
                        ],
                    ],
                ],
            ],
            'logo' => [],
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datosEnvio);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $cotizacionId = $data['id'];

        // ✅ Verificar que la cotización se creó
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion, 'Cotización debe existir');
        $this->assertEquals('PB', $cotizacion->tipo_cotizacion_id, 'Tipo debe ser PB');
        $this->assertFalse($cotizacion->es_borrador, 'Debe ser ENVIADA');

        // ✅ Verificar campos nuevos sincronizados en cotizaciones
        $this->assertNotNull($cotizacion->imagenes, 'Campo imagenes debe existir');
        $this->assertIsArray($cotizacion->imagenes, 'imagenes debe ser array');
        $this->assertCount(2, $cotizacion->imagenes, 'Debe haber 2 imágenes');

        $this->assertNotNull($cotizacion->tecnicas, 'Campo tecnicas debe existir');
        $this->assertIsArray($cotizacion->tecnicas, 'tecnicas debe ser array');
        $this->assertCount(2, $cotizacion->tecnicas, 'Debe haber 2 técnicas');

        $this->assertNotNull($cotizacion->observaciones_tecnicas, 'Campo observaciones_tecnicas debe existir');
        $this->assertStringContainsString('Bordado', $cotizacion->observaciones_tecnicas);

        $this->assertNotNull($cotizacion->ubicaciones, 'Campo ubicaciones debe existir');
        $this->assertIsArray($cotizacion->ubicaciones, 'ubicaciones debe ser array');
        $this->assertCount(2, $cotizacion->ubicaciones, 'Debe haber 2 ubicaciones');

        $this->assertNotNull($cotizacion->observaciones_generales, 'Campo observaciones_generales debe existir');
        $this->assertIsArray($cotizacion->observaciones_generales, 'observaciones_generales debe ser array');

        // ✅ Verificar que se guardó la prenda
        $prendas = $cotizacion->prendas()->get();
        $this->assertCount(1, $prendas, 'Debe haber 1 prenda');
        $prenda = $prendas[0];
        $this->assertEquals('POLO PIQUE', $prenda->nombre_producto);

        // ✅ Verificar que se guardaron fotos
        $fotos = $prenda->fotos()->get();
        $this->assertCount(2, $fotos, 'Debe haber 2 fotos');

        // ✅ Verificar que se guardaron telas con campos sincronizados
        $telas = $prenda->telas()->get();
        $this->assertCount(1, $telas, 'Debe haber 1 tela');
        
        $tela = $telas[0];
        $this->assertNotNull($tela->color_id, 'color_id debe existir');
        $this->assertNotNull($tela->tela_id, 'tela_id debe existir');
        // Verificar que el modelo tiene las relaciones correctas
        $this->assertTrue(method_exists($tela, 'color'), 'Debe tener relación color()');
        $this->assertTrue(method_exists($tela, 'tela'), 'Debe tener relación tela()');

        // ✅ Verificar que se guardaron tallas
        $tallas = $prenda->tallas()->get();
        $this->assertCount(5, $tallas, 'Debe haber 5 tallas');

        // ✅ Verificar que se guardaron variantes con campo telas_multiples
        $variantes = $prenda->variantes()->get();
        $this->assertCount(1, $variantes, 'Debe haber 1 variante');
        
        $variante = $variantes[0];
        $this->assertEquals(2, $variante->genero_id);
        $this->assertEquals(1, $variante->tipo_manga_id);
        $this->assertEquals(2, $variante->tipo_broche_id);
        $this->assertFalse($variante->tiene_bolsillos);
        $this->assertTrue($variante->tiene_reflectivo);

        // ✅ Verificar campo telas_multiples sincronizado
        $this->assertNotNull($variante->telas_multiples, 'Campo telas_multiples debe existir');
        $this->assertIsArray($variante->telas_multiples, 'telas_multiples debe ser array');
        $this->assertCount(1, $variante->telas_multiples, 'Debe haber 1 tela múltiple');
        $this->assertEquals('Azul', $variante->telas_multiples[0]['color']);
        $this->assertEquals('REF-PIQUE-001', $variante->telas_multiples[0]['referencia']);

        echo "\n✅ TEST COTIZACIÓN TIPO PB - TODOS LOS CAMPOS SINCRONIZADOS FUNCIONAN CORRECTAMENTE\n";
    }

    /**
     * Test: Verificar que los campos sincronizados se pueden actualizar
     */
    public function test_actualizar_cotizacion_tipo_pb_campos_sincronizados()
    {
        $this->actingAs($this->user);

        // Crear cotización inicial
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'numero_cotizacion' => 'COT-TEST-001',
            'tipo_cotizacion_id' => 3, // PB
            'tipo_venta' => 'M',
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'especificaciones' => [],
        ]);

        // Actualizar con nuevos campos sincronizados
        $cotizacion->update([
            'imagenes' => ['img1.jpg', 'img2.jpg'],
            'tecnicas' => ['Bordado'],
            'observaciones_tecnicas' => 'Actualizado: Bordado en pecho',
            'ubicaciones' => [['seccion' => 'Pecho']],
            'observaciones_generales' => [['texto' => 'Actualizado']],
        ]);

        // Verificar que se actualizó correctamente
        $cotizacion->refresh();
        $this->assertCount(2, $cotizacion->imagenes);
        $this->assertCount(1, $cotizacion->tecnicas);
        $this->assertStringContainsString('Actualizado', $cotizacion->observaciones_tecnicas);
        $this->assertCount(1, $cotizacion->ubicaciones);
        $this->assertCount(1, $cotizacion->observaciones_generales);

        echo "\n✅ TEST ACTUALIZACIÓN COTIZACIÓN TIPO PB - CAMPOS SINCRONIZADOS SE ACTUALIZAN CORRECTAMENTE\n";
    }

    /**
     * Test: Verificar que PrendaTelaCot tiene relaciones correctas
     */
    public function test_prenda_tela_cot_relaciones_sincronizadas()
    {
        // Crear una prenda
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'numero_cotizacion' => 'COT-TEST-002',
            'tipo_cotizacion_id' => 3, // PB
            'tipo_venta' => 'M',
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        $prenda = $cotizacion->prendas()->create([
            'nombre_producto' => 'PRENDA TEST',
            'descripcion' => 'Test',
            'cantidad' => 1,
        ]);

        // Crear una variante
        $variante = $prenda->variantes()->create([
            'genero_id' => 1,
            'color' => 'Rojo',
            'tipo_manga_id' => 1,
        ]);

        // Crear una tela con los campos sincronizados
        $tela = $prenda->telas()->create([
            'variante_prenda_cot_id' => $variante->id,
            'color_id' => 1,
            'tela_id' => 1,
        ]);

        // ✅ Verificar que la tela se creó con los campos correctos
        $this->assertNotNull($tela->id);
        $this->assertEquals($prenda->id, $tela->prenda_cot_id);
        $this->assertEquals($variante->id, $tela->variante_prenda_cot_id);
        $this->assertEquals(1, $tela->color_id);
        $this->assertEquals(1, $tela->tela_id);

        // ✅ Verificar relaciones
        $this->assertNotNull($tela->prenda, 'Relación prenda debe existir');
        $this->assertNotNull($tela->variante, 'Relación variante debe existir');
        $this->assertEquals($prenda->id, $tela->prenda->id);
        $this->assertEquals($variante->id, $tela->variante->id);

        echo "\n✅ TEST PRENDA TELA COT - RELACIONES SINCRONIZADAS FUNCIONAN CORRECTAMENTE\n";
    }

    /**
     * Test: Verificar que historial_cambios_cotizaciones funciona
     */
    public function test_historial_cambios_cotizaciones_tabla_existe()
    {
        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $this->user->id,
            'numero_cotizacion' => 'COT-TEST-003',
            'tipo_cotizacion_id' => 3, // PB
            'tipo_venta' => 'M',
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear registro en historial
        \App\Models\HistorialCambiosCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'estado_anterior' => 'BORRADOR',
            'estado_nuevo' => 'ENVIADA',
            'usuario_id' => $this->user->id,
            'usuario_nombre' => $this->user->name,
            'rol_usuario' => 'asesor',
            'razon_cambio' => 'Cambio de estado manual',
            'ip_address' => '127.0.0.1',
            'datos_adicionales' => ['cambio' => 'manual'],
        ]);

        // Verificar que se creó correctamente
        $historial = \App\Models\HistorialCambiosCotizacion::where('cotizacion_id', $cotizacion->id)->first();
        $this->assertNotNull($historial, 'Historial debe existir');
        $this->assertEquals('BORRADOR', $historial->estado_anterior);
        $this->assertEquals('ENVIADA', $historial->estado_nuevo);
        $this->assertEquals($cotizacion->id, $historial->cotizacion_id);

        echo "\n✅ TEST HISTORIAL CAMBIOS COTIZACIONES - TABLA FUNCIONA CORRECTAMENTE\n";
    }
}
