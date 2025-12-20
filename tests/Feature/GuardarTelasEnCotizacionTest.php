<?php

namespace Tests\Feature;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardarTelasEnCotizacionTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    public function setUp(): void
    {
        parent::setUp();
        
        try {
            $this->user = User::factory()->create([
                'rol' => 'asesor',
                'email' => 'asesor@test.com'
            ]);
        } catch (\Exception $e) {
            $this->user = User::create([
                'name' => 'Asesor Test',
                'email' => 'asesor@test.com',
                'password' => bcrypt('password'),
                'rol' => 'asesor'
            ]);
        }
    }

    /**
     * TEST 1: Guardar cotización con 1 prenda y 3 telas
     */
    public function test_telas_se_guardan_en_prenda_telas_cot()
    {
        $this->actingAs($this->user);

        $data = [
            'cliente' => 'Cliente Test',
            'tipo_cotizacion' => 'PL',
            'tipo_venta' => 'M',
            'es_borrador' => '1',
            'prendas' => [
                [
                    'nombre_producto' => 'Polo Hombre',
                    'descripcion' => 'Polo de prueba',
                    'cantidad' => 1,
                    'tallas' => ['S', 'M', 'L'],
                    'variantes' => [
                        'telas_multiples' => [
                            ['indice' => 0, 'color' => 'naranja', 'tela' => 'drill', 'referencia' => 'ref-001'],
                            ['indice' => 1, 'color' => 'azul', 'tela' => 'oxford', 'referencia' => 'ref-002'],
                            ['indice' => 2, 'color' => 'verde', 'tela' => 'gabardina', 'referencia' => 'ref-003']
                        ],
                        'genero_id' => 2
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $data);
        
        $this->assertEquals(201, $response->status(), 'Response debe ser 201 Created. ' . $response->getContent());
        $cotizacionId = $response->json('data.id');
        $cotizacion = Cotizacion::find($cotizacionId);
        
        $this->assertNotNull($cotizacion);
        $this->assertCount(1, $cotizacion->prendas);
        
        $prenda = $cotizacion->prendas->first();
        $this->assertNotNull($prenda);
        
        // Verificar telas_multiples en la variante
        $variante = $prenda->variantes->first();
        $this->assertNotNull($variante, 'La prenda debe tener una variante');
        $this->assertNotNull($variante->telas_multiples, 'La variante debe tener telas_multiples');
        $this->assertCount(3, $variante->telas_multiples, '✅ 3 telas guardadas en telas_multiples');
        
        // Verificar estructura de las telas
        $this->assertEquals('naranja', $variante->telas_multiples[0]['color']);
        $this->assertEquals('drill', $variante->telas_multiples[0]['tela']);
        $this->assertEquals('ref-001', $variante->telas_multiples[0]['referencia']);
        
        // VERIFICAR QUE SE GUARDARON EN prenda_telas_cot
        $telasCot = \DB::table('prenda_telas_cot')
            ->where('prenda_cot_id', $prenda->id)
            ->where('variante_prenda_cot_id', $variante->id)
            ->get();
        
        $this->assertCount(3, $telasCot, '✅ 3 registros en prenda_telas_cot (naranja, azul, verde)');
        
        // Verificar colores y telas específicas en BD
        $coloresEnBD = $telasCot->pluck('color_id')->toArray();
        $telasEnBD = $telasCot->pluck('tela_id')->toArray();
        
        $this->assertCount(3, $coloresEnBD, 'Debe haber 3 color_ids diferentes');
        $this->assertCount(3, $telasEnBD, 'Debe haber 3 tela_ids diferentes');
    }

    /**
     * TEST 2: Guardar cotización con 2 prendas, cada una con diferente cantidad de telas
     */
    public function test_guardar_2_prendas_con_diferentes_telas()
    {
        $this->actingAs($this->user);

        $data = [
            'cliente' => 'Cliente B',
            'tipo_cotizacion' => 'PB',
            'tipo_venta' => 'D',
            'es_borrador' => '1',
            'prendas' => [
                [
                    'nombre_producto' => 'Camisa Dama',
                    'descripcion' => 'Camisa para dama',
                    'cantidad' => 1,
                    'tallas' => ['S', 'M'],
                    'variantes' => [
                        'telas_multiples' => [
                            ['indice' => 0, 'color' => 'rojo', 'tela' => 'drill', 'referencia' => 'ref-101'],
                            ['indice' => 1, 'color' => 'blanco', 'tela' => 'oxford', 'referencia' => 'ref-102']
                        ],
                        'genero_id' => 1
                    ]
                ],
                [
                    'nombre_producto' => 'Pantalón Caballero',
                    'descripcion' => 'Pantalón para caballero',
                    'cantidad' => 1,
                    'tallas' => ['30', '32', '34'],
                    'variantes' => [
                        'telas_multiples' => [
                            ['indice' => 0, 'color' => 'negro', 'tela' => 'drill', 'referencia' => 'ref-201'],
                            ['indice' => 1, 'color' => 'gris', 'tela' => 'gabardina', 'referencia' => 'ref-202'],
                            ['indice' => 2, 'color' => 'azul', 'tela' => 'oxford', 'referencia' => 'ref-203'],
                            ['indice' => 3, 'color' => 'marrón', 'tela' => 'twill', 'referencia' => 'ref-204']
                        ],
                        'genero_id' => 2
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $data);
        
        $this->assertEquals(201, $response->status(), $response->getContent());
        $cotizacionId = $response->json('data.id');
        $cotizacion = Cotizacion::find($cotizacionId);
        
        $prendas = $cotizacion->prendas;
        $this->assertCount(2, $prendas);
        
        // Verificar telas de prenda 1
        $variante1 = $prendas[0]->variantes->first();
        $this->assertCount(2, $variante1->telas_multiples, '✅ Prenda 1: 2 telas');
        
        // Verificar telas de prenda 2
        $variante2 = $prendas[1]->variantes->first();
        $this->assertCount(4, $variante2->telas_multiples, '✅ Prenda 2: 4 telas');
    }

    /**
     * TEST 3: Editar cotización y agregar más telas
     */
    public function test_editar_cotizacion_y_agregar_telas()
    {
        $this->actingAs($this->user);

        // Crear cotización inicial
        $data = [
            'cliente' => 'Cliente C',
            'tipo_cotizacion' => 'RF',
            'tipo_venta' => 'X',
            'es_borrador' => true,
            'prendas' => [
                [
                    'nombre_producto' => 'Uniforme',
                    'cantidad' => 20,
                    'telas' => [
                        ['color_id' => 1, 'tela_id' => 1]
                    ],
                    'cantidades' => ['M' => 20]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $data);
        $cotizacionId = $response->json('data.id');
        
        // Actualizar y agregar 2 telas más
        $dataUpdate = [
            'cotizacion_id' => $cotizacionId,
            'cliente' => 'Cliente C Actualizado',
            'tipo_cotizacion' => 'RF',
            'tipo_venta' => 'X',
            'es_borrador' => true,
            'prendas' => [
                [
                    'nombre_producto' => 'Uniforme',
                    'cantidad' => 20,
                    'telas' => [
                        ['color_id' => 1, 'tela_id' => 1],
                        ['color_id' => 2, 'tela_id' => 2],
                        ['color_id' => 3, 'tela_id' => 3]
                    ],
                    'cantidades' => ['M' => 20]
                ]
            ]
        ];

        $responseUpdate = $this->postJson('/asesores/cotizaciones/guardar', $dataUpdate);
        $this->assertEquals(200, $responseUpdate->status());
        
        $cotizacion = Cotizacion::find($cotizacionId);
        $prenda = $cotizacion->prendas->first();
        $this->assertCount(3, $prenda->telas, '✅ Telas actualizadas a 3');
    }

    /**
     * TEST 4: Show cotización y verificar que se devuelven todas las telas
     */
    public function test_show_cotizacion_retorna_todas_las_telas()
    {
        $this->actingAs($this->user);

        // Crear
        $data = [
            'cliente' => 'Cliente D',
            'tipo_cotizacion' => 'P',
            'tipo_venta' => 'M',
            'es_borrador' => true,
            'prendas' => [
                [
                    'nombre_producto' => 'Chaqueta',
                    'cantidad' => 3,
                    'telas' => [
                        ['color_id' => 1, 'tela_id' => 1],
                        ['color_id' => 2, 'tela_id' => 2],
                        ['color_id' => 3, 'tela_id' => 3],
                        ['color_id' => 4, 'tela_id' => 4],
                        ['color_id' => 5, 'tela_id' => 5],
                        ['color_id' => 6, 'tela_id' => 6]
                    ],
                    'cantidades' => ['S' => 1, 'M' => 1, 'L' => 1]
                ]
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $data);
        $cotizacionId = $response->json('data.id');
        
        // Show
        $showResponse = $this->getJson("/asesores/cotizaciones/{$cotizacionId}");
        $this->assertEquals(200, $showResponse->status());
        
        $data = $showResponse->json();
        $this->assertCount(1, $data['prendas']);
        $this->assertCount(6, $data['prendas'][0]['telas'], '✅ Show retorna 6 telas');
    }
}
