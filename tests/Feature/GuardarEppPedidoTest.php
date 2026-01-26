<?php

namespace Tests\Feature;

use App\Models\Epp;
use App\Models\EppCategoria;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Services\PedidoEppService;
use Tests\TestCase;

class GuardarEppPedidoTest extends TestCase
{
    protected $eppService;
    protected $pedido;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eppService = new PedidoEppService();
        
        // Usar un pedido existente o crear uno para tests
        $this->pedido = PedidoProduccion::first() ?? $this->crearPedidoTest();
    }

    /**
     * Test: Guardar un EPP con imagen en un pedido
     */
    public function test_guardar_epp_con_imagen_en_pedido()
    {
        $this->info("\n Test: Guardar EPP con imagen en pedido\n");

        // Obtener un EPP existente
        $epp = Epp::first();
        
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles en la BD');
        }

        // Datos del EPP a guardar
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 10,
                'tallas_medidas' => [
                    'talla' => 'L',
                    'medida' => '58cm',
                    'color' => 'Blanco'
                ],
                'observaciones' => 'Con logo de empresa',
                'imagenes' => [
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/imagen-1.jpg',
                        'principal' => true,
                        'orden' => 0
                    ],
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/imagen-2.jpg',
                        'principal' => false,
                        'orden' => 1
                    ]
                ]
            ]
        ];

        // Guardar EPP en el pedido
        $pedidosEpp = $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);

        // Verificaciones
        $this->assertCount(1, $pedidosEpp);
        $pedidoEpp = $pedidosEpp[0];

        // Verificar que se guardó en BD
        $this->assertDatabaseHas('pedido_epp', [
            'id' => $pedidoEpp->id,
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 10,
        ]);

        // Verificar relaciones
        $this->assertEquals($this->pedido->id, $pedidoEpp->pedido_produccion_id);
        $this->assertEquals($epp->id, $pedidoEpp->epp_id);
        $this->assertEquals(10, $pedidoEpp->cantidad);

        // Verificar JSON de tallas
        $this->assertIsArray($pedidoEpp->tallas_medidas);
        $this->assertEquals('L', $pedidoEpp->tallas_medidas['talla']);
        $this->assertEquals('58cm', $pedidoEpp->tallas_medidas['medida']);

        echo "\n EPP guardado correctamente en pedido\n";
        echo "   - ID PedidoEpp: {$pedidoEpp->id}\n";
        echo "   - Pedido: {$this->pedido->numero_pedido}\n";
        echo "   - EPP: {$epp->nombre}\n";
        echo "   - Cantidad: {$pedidoEpp->cantidad}\n";

        return $pedidoEpp->id;
    }

    /**
     * Test: Verificar que las imÃ¡genes se guardaron correctamente
     */
    public function test_imagenes_del_epp_se_guardaron()
    {
        $this->info("\nTest: Verificar imÃ¡genes del EPP\n");

        // Primero guardar el EPP
        $epp = Epp::first();
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles');
        }

        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 5,
                'imagenes' => [
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/frente.jpg',
                        'principal' => true,
                        'orden' => 0
                    ],
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/lateral.jpg',
                        'principal' => false,
                        'orden' => 1
                    ],
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/trasera.jpg',
                        'principal' => false,
                        'orden' => 2
                    ]
                ]
            ]
        ];

        $pedidosEpp = $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);
        $pedidoEpp = $pedidosEpp[0];

        // Verificar que las imÃ¡genes se guardaron
        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->get();
        
        $this->assertCount(3, $imagenes);

        // Verificar en BD
        $this->assertDatabaseHas('pedido_epp_imagenes', [
            'pedido_epp_id' => $pedidoEpp->id,
            'principal' => true,
            'orden' => 0
        ]);

        // Verificar imagen principal
        $imagenPrincipal = $pedidoEpp->imagenPrincipal();
        $this->assertNotNull($imagenPrincipal);
        $this->assertTrue($imagenPrincipal->principal);

        echo "\n ImÃ¡genes guardadas correctamente\n";
        echo "   - Total imÃ¡genes: " . count($imagenes) . "\n";
        echo "   - Imagen principal: {$imagenPrincipal->archivo}\n";
        foreach ($imagenes as $img) {
            $tipo = $img->principal ? '(PRINCIPAL)' : '';
            echo "   - Orden {$img->orden}: {$img->archivo} {$tipo}\n";
        }
    }

    /**
     * Test: Obtener EPP de un pedido con relaciones
     */
    public function test_obtener_epps_del_pedido()
    {
        $this->info("\n Test: Obtener EPP del pedido con relaciones\n");

        $epp = Epp::first();
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles');
        }

        // Guardar EPP
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 20,
                'tallas_medidas' => ['talla' => 'M'],
                'observaciones' => 'Test observation',
                'imagenes' => [
                    ['archivo' => '/storage/test1.jpg', 'principal' => true, 'orden' => 0]
                ]
            ]
        ];

        $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);

        // Obtener EPP del pedido
        $eppsDelPedido = $this->eppService->obtenerEppsDelPedido($this->pedido);

        // Verificaciones
        $this->assertIsArray($eppsDelPedido);
        $this->assertGreaterThan(0, count($eppsDelPedido));

        $ultimoEpp = end($eppsDelPedido);
        
        // Verificar estructura
        $this->assertArrayHasKey('id', $ultimoEpp);
        $this->assertArrayHasKey('epp_id', $ultimoEpp);
        $this->assertArrayHasKey('epp_nombre', $ultimoEpp);
        $this->assertArrayHasKey('cantidad', $ultimoEpp);
        $this->assertArrayHasKey('tallas_medidas', $ultimoEpp);
        $this->assertArrayHasKey('observaciones', $ultimoEpp);
        $this->assertArrayHasKey('imagenes', $ultimoEpp);

        echo "\n Datos obtenidos correctamente\n";
        echo "   - EPP nombre: {$ultimoEpp['epp_nombre']}\n";
        echo "   - Cantidad: {$ultimoEpp['cantidad']}\n";
        echo "   - Observaciones: {$ultimoEpp['observaciones']}\n";
        echo "   - Total imÃ¡genes: " . count($ultimoEpp['imagenes']) . "\n";
    }

    /**
     * Test: Guardar mÃºltiples EPP en un pedido
     */
    public function test_guardar_multiples_epps_en_pedido()
    {
        $this->info("\n Test: Guardar mÃºltiples EPP en un pedido\n");

        $epps = Epp::limit(2)->get();
        
        if (count($epps) < 2) {
            $this->markTestSkipped('No hay 2 EPPs disponibles');
        }

        // Preparar datos de mÃºltiples EPP
        $eppsData = [
            [
                'epp_id' => $epps[0]->id,
                'cantidad' => 10,
                'tallas_medidas' => ['talla' => 'L'],
                'observaciones' => 'Primer EPP'
            ],
            [
                'epp_id' => $epps[1]->id,
                'cantidad' => 50,
                'tallas_medidas' => ['tamaÃ±o' => 'M'],
                'observaciones' => 'Segundo EPP'
            ]
        ];

        // Guardar
        $pedidosEpp = $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);

        // Verificar
        $this->assertCount(2, $pedidosEpp);
        $this->assertDatabaseHas('pedido_epp', [
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epps[0]->id,
            'cantidad' => 10
        ]);
        $this->assertDatabaseHas('pedido_epp', [
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epps[1]->id,
            'cantidad' => 50
        ]);

        echo "\n MÃºltiples EPP guardados correctamente\n";
        echo "   - Total guardados: " . count($pedidosEpp) . "\n";
        foreach ($pedidosEpp as $index => $pe) {
            echo "   - EPP " . ($index + 1) . ": {$pe->cantidad} unidades\n";
        }
    }

    /**
     * Test: Actualizar EPP de un pedido
     */
    public function test_actualizar_epp_del_pedido()
    {
        $this->info("\n  Test: Actualizar EPP del pedido\n");

        $epp = Epp::first();
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles');
        }

        // Crear EPP
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 10,
                'tallas_medidas' => ['talla' => 'L'],
                'observaciones' => 'Original'
            ]
        ];

        $pedidosEpp = $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);
        $pedidoEpp = $pedidosEpp[0];

        // Actualizar
        $this->eppService->actualizarEpp($pedidoEpp, [
            'cantidad' => 20,
            'observaciones' => 'Actualizado'
        ]);

        // Verificar en BD
        $this->assertDatabaseHas('pedido_epp', [
            'id' => $pedidoEpp->id,
            'cantidad' => 20,
            'observaciones' => 'Actualizado'
        ]);

        echo "\n EPP actualizado correctamente\n";
        echo "   - Cantidad anterior: 10\n";
        echo "   - Cantidad nueva: 20\n";
        echo "   - Observaciones: Actualizado\n";
    }

    /**
     * Test: Serializar EPP a JSON
     */
    public function test_serializar_epps_a_json()
    {
        $this->info("\n Test: Serializar EPP a JSON\n");

        $epp = Epp::first();
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles');
        }

        // Guardar EPP
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 15,
                'tallas_medidas' => ['talla' => 'M', 'color' => 'Azul'],
                'observaciones' => 'Para serializar'
            ]
        ];

        $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);

        // Serializar a JSON
        $json = $this->eppService->serializarEppsAJson($this->pedido);

        // Verificar
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertGreaterThan(0, count($decoded));

        echo "\n EPP serializado a JSON correctamente\n";
        echo "   - JSON length: " . strlen($json) . " caracteres\n";
        echo "   - Items en JSON: " . count($decoded) . "\n";
        echo "   - JSON preview: " . substr($json, 0, 100) . "...\n";
    }

    /**
     * Crear pedido de test si no existe
     */
    private function crearPedidoTest(): PedidoProduccion
    {
        return PedidoProduccion::create([
            'cotizacion_id' => 1,
            'numero_cotizacion' => 'TEST-' . time(),
            'numero_pedido' => 99999,
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'estado' => 'Pendiente',
            'fecha_de_creacion_de_orden' => now()->toDateString(),
            'dia_de_entrega' => 5,
            'fecha_estimada_de_entrega' => now()->addDays(5),
            'cantidad_total' => 100
        ]);
    }

    /**
     * Test: Guardar EPP con imÃ¡genes sin hacer refresh
     *  Registra el EPP en la BD
     *  Guarda las imÃ¡genes asociadas
     *  No borra la BD
     *  Verifica que todo se guardó correctamente
     */
    public function test_guardar_epp_sin_refresh()
    {
        $this->info("\n Test: Guardar EPP sin hacer refresh\n");

        // Obtener un EPP existente
        $epp = Epp::first();
        
        if (!$epp) {
            $this->markTestSkipped('No hay EPP disponibles en la BD');
        }

        $this->info("   - EPP a guardar: {$epp->nombre}\n");
        $this->info("   - Pedido: {$this->pedido->numero_pedido}\n");

        // Datos del EPP a guardar
        $eppsData = [
            [
                'epp_id' => $epp->id,
                'cantidad' => 25,
                'tallas_medidas' => [
                    'talla' => 'XL',
                    'medida' => '64cm',
                    'color' => 'Negro'
                ],
                'observaciones' => 'EPP de prueba sin refresh',
                'imagenes' => [
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/frente-test.jpg',
                        'principal' => true,
                        'orden' => 0
                    ],
                    [
                        'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/lateral-test.jpg',
                        'principal' => false,
                        'orden' => 1
                    ]
                ]
            ]
        ];

        //  Guardar EPP
        $pedidosEpp = $this->eppService->guardarEppsDelPedido($this->pedido, $eppsData);

        //  Verificaciones
        $this->assertCount(1, $pedidosEpp, 'Se debe guardar exactamente 1 EPP');
        $pedidoEpp = $pedidosEpp[0];

        //  Verificar que estÃ¡ en la BD
        $this->assertDatabaseHas('pedido_epp', [
            'id' => $pedidoEpp->id,
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $epp->id,
            'cantidad' => 25,
            'observaciones' => 'EPP de prueba sin refresh'
        ]);

        //  Verificar relaciones
        $this->assertEquals($this->pedido->id, $pedidoEpp->pedido_produccion_id);
        $this->assertEquals($epp->id, $pedidoEpp->epp_id);
        $this->assertEquals(25, $pedidoEpp->cantidad);

        //  Verificar JSON de tallas
        $this->assertIsArray($pedidoEpp->tallas_medidas);
        $this->assertEquals('XL', $pedidoEpp->tallas_medidas['talla']);
        $this->assertEquals('64cm', $pedidoEpp->tallas_medidas['medida']);
        $this->assertEquals('Negro', $pedidoEpp->tallas_medidas['color']);

        //  Verificar que las imÃ¡genes se guardaron
        $imagenes = PedidoEppImagen::where('pedido_epp_id', $pedidoEpp->id)->get();
        $this->assertCount(2, $imagenes, 'Se deben guardar 2 imÃ¡genes');

        //  Verificar imagen principal
        $this->assertDatabaseHas('pedido_epp_imagenes', [
            'pedido_epp_id' => $pedidoEpp->id,
            'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/frente-test.jpg',
            'principal' => true,
            'orden' => 0
        ]);

        //  Verificar segunda imagen
        $this->assertDatabaseHas('pedido_epp_imagenes', [
            'pedido_epp_id' => $pedidoEpp->id,
            'archivo' => '/storage/pedidos/' . $this->pedido->id . '/epp/lateral-test.jpg',
            'principal' => false,
            'orden' => 1
        ]);

        //  Recargar desde BD para confirmar
        $pedidoEppRecargado = PedidoEpp::find($pedidoEpp->id);
        $this->assertNotNull($pedidoEppRecargado, 'El EPP debe existir en la BD');
        $this->assertEquals(25, $pedidoEppRecargado->cantidad);

        //  Output de Ã©xito
        echo "\n EPP guardado exitosamente sin refresh\n";
        echo "   - ID PedidoEpp: {$pedidoEpp->id}\n";
        echo "   - Cantidad: {$pedidoEpp->cantidad}\n";
        echo "   - Talla: {$pedidoEpp->tallas_medidas['talla']}\n";
        echo "   - Color: {$pedidoEpp->tallas_medidas['color']}\n";
        echo "   - ImÃ¡genes guardadas: " . count($imagenes) . "\n";
        echo "   - BD NO fue borrada \n\n";
    }

    /**
     * Helper para imprimir info de test
     */
    private function info(string $message): void
    {
        echo $message;
    }
}

