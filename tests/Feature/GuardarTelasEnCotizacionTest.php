<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaTelaCot;
use App\Models\PrendaTelaFotoCot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardarTelasEnCotizacionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario para autenticar
        $this->user = User::factory()->create([
            'rol' => 'asesor',
            'email' => 'asesor@test.com'
        ]);
    }

    /**
     * ✅ Test: Guardar cotización PB (Combinada) con múltiples telas y fotos
     * 
     * Verifica:
     * 1. ✅ Cotización se crea en tabla `cotizaciones`
     * 2. ✅ Prendas se guardan en tabla `prendas_cot`
     * 3. ✅ Telas se guardan en tabla `prenda_telas_cot` ← CRÍTICO
     * 4. ✅ Fotos de telas se guardan en tabla `prenda_tela_fotos_cot` ← CRÍTICO
     * 5. ✅ Relaciones funcionan correctamente
     */
    public function test_guardar_cotizacion_pb_con_multiples_telas_y_fotos()
    {
        $this->actingAs($this->user);

        // 📋 PREPARAR DATOS DEL REQUEST
        $requestData = [
            'cliente' => 'Cliente Test PB',
            'forma_de_pago' => 'Crédito',
            'area' => 'Ventas',
            'tipo_cotizacion' => 'PB',
            'productos_friendly' => [
                [
                    'nombre_producto' => 'Camisa Drill PB',
                    'descripcion' => 'Camisa en drill con múltiples telas',
                    'cantidad' => 10,
                    'genero' => 'Unisex',
                    'variantes' => [
                        'telas_multiples' => [
                            [
                                'color' => 'Blanco',
                                'tela' => 'Drill',
                                'color_id' => 1,
                                'tela_id' => 5,
                                'referencia' => 'DRL-BLN-001'
                            ],
                            [
                                'color' => 'Azul',
                                'tela' => 'Drill',
                                'color_id' => 2,
                                'tela_id' => 5,
                                'referencia' => 'DRL-AZL-002'
                            ],
                            [
                                'color' => 'Negro',
                                'tela' => 'Drill',
                                'color_id' => 3,
                                'tela_id' => 5,
                                'referencia' => 'DRL-NGR-003'
                            ]
                        ]
                    ],
                    'tallas' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                    'cantidades' => [
                        'XS' => 2,
                        'S' => 2,
                        'M' => 2,
                        'L' => 2,
                        'XL' => 1,
                        'XXL' => 1
                    ],
                    // ✅ Las fotos de telas vienen en este formato
                    'telas' => [
                        [
                            'color_id' => 1,
                            'tela_id' => 5,
                            'referencia' => 'DRL-BLN-001',
                            'fotos' => [
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...',
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...'
                            ]
                        ],
                        [
                            'color_id' => 2,
                            'tela_id' => 5,
                            'referencia' => 'DRL-AZL-002',
                            'fotos' => [
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...'
                            ]
                        ],
                        [
                            'color_id' => 3,
                            'tela_id' => 5,
                            'referencia' => 'DRL-NGR-003',
                            'fotos' => [
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...',
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...',
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...'
                            ]
                        ]
                    ],
                    'fotos_desde_prendaConIndice' => [
                        'data:image/webp;base64,UklGRiYAAABXRUJQ...'
                    ]
                ],
                [
                    'nombre_producto' => 'Pantalón Drill PB',
                    'descripcion' => 'Pantalón en drill',
                    'cantidad' => 10,
                    'genero' => 'Unisex',
                    'variantes' => [
                        'telas_multiples' => [
                            [
                                'color' => 'Gris',
                                'tela' => 'Drill',
                                'color_id' => 4,
                                'tela_id' => 5,
                                'referencia' => 'DRL-GRS-004'
                            ]
                        ]
                    ],
                    'tallas' => ['28', '30', '32', '34', '36', '38', '40'],
                    'cantidades' => [
                        '28' => 1,
                        '30' => 1,
                        '32' => 2,
                        '34' => 2,
                        '36' => 2,
                        '38' => 1,
                        '40' => 1
                    ],
                    'telas' => [
                        [
                            'color_id' => 4,
                            'tela_id' => 5,
                            'referencia' => 'DRL-GRS-004',
                            'fotos' => [
                                'data:image/webp;base64,UklGRiYAAABXRUJQ...'
                            ]
                        ]
                    ],
                    'fotos_desde_prendaConIndice' => []
                ]
            ]
        ];

        // 🚀 EJECUTAR POST A /asesores/cotizaciones/guardar
        $response = $this->postJson('/asesores/cotizaciones/guardar', $requestData);

        // ✅ VERIFICAR RESPUESTA
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'borrador_id'
                 ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        // 📍 VERIFICACIÓN 1: ✅ COTIZACIÓN SE CREÓ
        $cotizacionId = $responseData['borrador_id'];
        $cotizacion = Cotizacion::findOrFail($cotizacionId);
        
        $this->assertEquals('Cliente Test PB', $cotizacion->cliente);
        $this->assertEquals('PB', $cotizacion->tipo_cotizacion);
        $this->assertTrue($cotizacion->es_borrador);
        echo "\n✅ Cotización creada correctamente (ID: $cotizacionId)\n";

        // 📍 VERIFICACIÓN 2: ✅ PRENDAS SE GUARDARON EN prendas_cot
        $prendas = $cotizacion->prendas;
        $this->assertCount(2, $prendas, 'Debe haber 2 prendas');
        echo "✅ Se guardaron 2 prendas en prendas_cot\n";

        // 📍 VERIFICACIÓN 3: ✅ TELAS SE GUARDARON EN prenda_telas_cot (CRÍTICO)
        $prendaCamisa = $prendas->first();
        $prendasTelas = $prendaCamisa->telas;
        
        $this->assertNotNull($prendasTelas, 'La prenda debe tener relación con telas');
        $this->assertCount(3, $prendasTelas, '⚠️ CAMISA: Debe tener 3 telas guardadas');
        
        // Verificar que cada tela tiene los datos correctos
        foreach ($prendasTelas as $index => $tela) {
            $this->assertNotNull($tela->color_id, "Tela $index debe tener color_id");
            $this->assertNotNull($tela->tela_id, "Tela $index debe tener tela_id");
            echo "✅ Tela $index guardada (color_id: {$tela->color_id}, tela_id: {$tela->tela_id})\n";
        }

        $prenaPantalon = $prendas->last();
        $pantaloDeTelas = $prenaPantalon->telas;
        $this->assertCount(1, $pantaloDeTelas, '⚠️ PANTALÓN: Debe tener 1 tela guardada');
        echo "✅ Pantalón tiene 1 tela guardada\n";

        // 📍 VERIFICACIÓN 4: ✅ FOTOS DE TELAS SE GUARDARON
        $tela1 = $prendasTelas->first();
        $fotosTelauno = $tela1->fotos;
        
        $this->assertNotNull($fotosTelauno, 'La tela debe tener relación con fotos');
        // En el test anterior falló aquí
        $this->assertTrue(count($fotosTelauno) > 0, 
            '⚠️ CRÍTICO: La primera tela debe tener fotos guardadas (actualmente: ' . count($fotosTelauno) . ')');
        
        echo "✅ Tela 1 tiene " . count($fotosTelauno) . " foto(s) guardada(s)\n";

        // 📍 VERIFICACIÓN 5: ✅ TALLAS SE GUARDARON
        $tallas = $prendaCamisa->tallas;
        $this->assertTrue(count($tallas) > 0, 'Debe haber tallas guardadas');
        echo "✅ Se guardaron " . count($tallas) . " tallas para la camisa\n";

        // 📍 VERIFICACIÓN 6: ✅ VARIANTES SE GUARDARON
        $variantes = $prendaCamisa->variantes;
        $this->assertTrue(count($variantes) > 0, 'Debe haber variantes guardadas');
        $primeraVariante = $variantes->first();
        $this->assertEquals('SÍ', $primeraVariante->telas_multiples, 
            'Variante debe marcar telas_multiples = SÍ');
        echo "✅ Se guardaron variantes con telas_multiples = SÍ\n";

        // 📍 VERIFICACIÓN 7: ✅ FOTOS DE PRENDA SE GUARDARON
        $fotosPrenda = $prendaCamisa->fotos;
        $this->assertTrue(count($fotosPrenda) > 0, 'Debe haber fotos de prenda guardadas');
        echo "✅ Se guardaron " . count($fotosPrenda) . " foto(s) de prenda\n";

        // 📊 RESUMEN FINAL
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 RESUMEN DEL TEST\n";
        echo str_repeat("=", 80) . "\n";
        echo "Cotización: $cotizacionId ✅\n";
        echo "Prendas: " . count($prendas) . " ✅\n";
        echo "  └─ Camisa: " . count($prendasTelas) . " telas, " . count($fotosPrenda) . " fotos\n";
        echo "  └─ Pantalón: " . count($pantaloDeTelas) . " telas\n";
        echo "Telas total: " . ($prendasTelas->count() + $pantaloDeTelas->count()) . " ✅\n";
        echo "Tallas: " . count($tallas) . " ✅\n";
        echo "Variantes: " . count($variantes) . " ✅\n";
        echo str_repeat("=", 80) . "\n\n";
    }

    /**
     * ✅ Test: Verificar que la estructura de datos desde FormData sea correcta
     */
    public function test_verificar_estructura_telas_desde_formdata()
    {
        $this->actingAs($this->user);

        $requestData = [
            'cliente' => 'Test Estructura',
            'forma_de_pago' => 'Contado',
            'tipo_cotizacion' => 'PB',
            'productos_friendly' => [
                [
                    'nombre_producto' => 'Prenda Test Estructura',
                    'cantidad' => 5,
                    // ✅ Estructura correcta: telas con color_id, tela_id, fotos[]
                    'telas' => [
                        [
                            'color_id' => 1,
                            'tela_id' => 2,
                            'referencia' => 'REF-001',
                            'fotos' => ['foto1.webp', 'foto2.webp']
                        ]
                    ],
                    'tallas' => ['M', 'L'],
                    'cantidades' => ['M' => 5, 'L' => 5]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $requestData);
        $response->assertStatus(201);

        $cotizacion = Cotizacion::findOrFail($response->json('borrador_id'));
        $prenda = $cotizacion->prendas->first();
        $telas = $prenda->telas;

        // Verificar estructura
        $this->assertCount(1, $telas);
        $this->assertEquals(1, $telas->first()->color_id);
        $this->assertEquals(2, $telas->first()->tela_id);

        echo "✅ Estructura de telas desde FormData es correcta\n";
    }

    /**
     * ✅ Test: Editar cotización y modificar telas
     */
    public function test_editar_cotizacion_y_modificar_telas()
    {
        // Crear cotización inicial
        $cotizacion = Cotizacion::factory()->create([
            'asesor_id' => $this->user->id,
            'es_borrador' => true
        ]);

        $prenda = PrendaCot::factory()->create(['cotizacion_id' => $cotizacion->id]);
        
        // Crear 2 telas iniciales
        PrendaTelaCot::factory(2)->create(['prenda_cot_id' => $prenda->id]);

        $this->actingAs($this->user);

        // Enviar actualización con 3 telas
        $updateData = [
            'cliente' => $cotizacion->cliente,
            'forma_de_pago' => 'Crédito',
            'tipo_cotizacion' => 'PB',
            'cotizacion_id' => $cotizacion->id,
            'productos_friendly' => [
                [
                    'nombre_producto' => $prenda->nombre_producto,
                    'cantidad' => $prenda->cantidad,
                    'telas' => [
                        ['color_id' => 1, 'tela_id' => 1, 'fotos' => []],
                        ['color_id' => 2, 'tela_id' => 1, 'fotos' => []],
                        ['color_id' => 3, 'tela_id' => 1, 'fotos' => []]
                    ],
                    'tallas' => ['M', 'L'],
                    'cantidades' => ['M' => 5, 'L' => 5]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $updateData);
        $response->assertStatus(201);

        // Recargar y verificar
        $prenda->refresh();
        $this->assertCount(3, $prenda->telas, 'Después de editar, debe tener 3 telas');

        echo "✅ Edición de cotización y modificación de telas funciona\n";
    }
}
