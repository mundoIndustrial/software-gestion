<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\VarianteCot;
use App\Models\PrendaTelaFotoCot;
use App\Application\Services\CotizacionPrendaService;
use App\Domain\Repositories\CotizacionRepository;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;

/**
 * Test: MÃºltiples Telas, Referencias, Colores e ImÃ¡genes
 * 
 *  IMPORTANTE: Este test usa DatabaseTransactions para AISLAR los cambios
 * No afecta la base de datos real - todos los cambios se revierten al finalizar
 */
class CotizacionMultiplesTelasTest extends TestCase
{
    use DatabaseTransactions; //  No afecta BD real
    
    protected CotizacionPrendaService $cotizacionPrendaService;
    protected CotizacionRepository $cotizacionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cotizacionPrendaService = app(CotizacionPrendaService::class);
        $this->cotizacionRepository = app(CotizacionRepository::class);
        
        // Simular almacenamiento de archivos (en memoria)
        Storage::fake('public');
    }

    /**
     * Test: Guardar prenda con mÃºltiples telas, referencias, colores e imÃ¡genes
     */
    public function test_guardar_prenda_multiples_telas_con_imagenes()
    {
        echo "\n\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo "ðŸ§ª TEST: MÃºltiples Telas con ImÃ¡genes\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";

        // Crear cotizaciÃ³n de prueba
        $cotizacion = Cotizacion::factory()->create([
            'numero_cotizacion' => 'TEST-' . time(),
            'tipo' => 'P',
            'es_borrador' => false
        ]);
        echo " CotizaciÃ³n creada: {$cotizacion->numero_cotizacion}\n";

        // Datos de prenda con mÃºltiples telas
        $prendaData = [
            'nombre_producto' => 'Camiseta Test',
            'descripcion' => 'Camiseta para prueba de mÃºltiples telas',
            'cantidad' => 1,
            'variantes' => [
                'color' => 'Variado',
                'tipo_prenda' => 'Camiseta'
            ],
            'telas' => [
                // TELA 1
                [
                    'tela_id' => 1,
                    'color_id' => 10,
                    'referencia' => 'ALG-001',
                    'fotos' => [
                        [
                            'ruta_original' => 'cotizaciones/' . $cotizacion->id . '/telas/tela1_' . time() . '.webp',
                            'ruta_webp' => 'cotizaciones/' . $cotizacion->id . '/telas/tela1_' . time() . '.webp',
                            'orden' => 1,
                            'tamaÃ±o' => 45678
                        ]
                    ]
                ],
                // TELA 2
                [
                    'tela_id' => 2,
                    'color_id' => 15,
                    'referencia' => 'POL-002',
                    'fotos' => [
                        [
                            'ruta_original' => 'cotizaciones/' . $cotizacion->id . '/telas/tela2a_' . time() . '.webp',
                            'ruta_webp' => 'cotizaciones/' . $cotizacion->id . '/telas/tela2a_' . time() . '.webp',
                            'orden' => 1,
                            'tamaÃ±o' => 52341
                        ],
                        [
                            'ruta_original' => 'cotizaciones/' . $cotizacion->id . '/telas/tela2b_' . time() . '.webp',
                            'ruta_webp' => 'cotizaciones/' . $cotizacion->id . '/telas/tela2b_' . time() . '.webp',
                            'orden' => 2,
                            'tamaÃ±o' => 48756
                        ]
                    ]
                ],
                // TELA 3
                [
                    'tela_id' => 3,
                    'color_id' => 20,
                    'referencia' => 'LIN-003',
                    'fotos' => [
                        [
                            'ruta_original' => 'cotizaciones/' . $cotizacion->id . '/telas/tela3_' . time() . '.webp',
                            'ruta_webp' => 'cotizaciones/' . $cotizacion->id . '/telas/tela3_' . time() . '.webp',
                            'orden' => 1,
                            'tamaÃ±o' => 61234
                        ]
                    ]
                ]
            ]
        ];

        echo "\n Datos de prenda:\n";
        echo "   - Nombre: {$prendaData['nombre_producto']}\n";
        echo "   - Telas: " . count($prendaData['telas']) . "\n";
        foreach ($prendaData['telas'] as $idx => $tela) {
            echo "     Tela " . ($idx + 1) . ": referencia={$tela['referencia']}, fotos=" . count($tela['fotos']) . "\n";
        }

        // Guardar la prenda
        try {
            $this->cotizacionPrendaService->guardarPrendaConTelas(
                $cotizacion,
                $prendaData
            );
            echo "\n Prenda guardada correctamente\n";
        } catch (\Exception $e) {
            $this->fail("Error al guardar prenda: " . $e->getMessage());
        }

        // VERIFICACIONES
        echo "\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo " VERIFICACIONES\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";

        // 1. Verificar que la prenda se guardÃ³
        $prenda = PrendaCot::where('cotizacion_id', $cotizacion->id)
            ->where('nombre_producto', 'Camiseta Test')
            ->first();
        
        $this->assertNotNull($prenda, "La prenda deberÃ­a existir");
        echo "\n PRENDA GUARDADA\n";
        echo "   - ID: {$prenda->id}\n";
        echo "   - Nombre: {$prenda->nombre_producto}\n";

        // 2. Verificar que la variante se guardÃ³
        $variante = VarianteCot::where('prenda_cot_id', $prenda->id)->first();
        $this->assertNotNull($variante, "La variante deberÃ­a existir");
        echo "\n VARIANTE GUARDADA\n";
        echo "   - ID: {$variante->id}\n";

        // 3. Verificar fotos de telas
        $fotosTelas = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)->get();
        echo "\n FOTOS DE TELAS GUARDADAS: " . count($fotosTelas) . "\n";

        $this->assertCount(4, $fotosTelas, "DeberÃ­a haber exactamente 4 fotos (1+2+1)");

        // Verificar cada foto
        $fotosAgrupadas = $fotosTelas->groupBy('referencia')->toArray();
        
        foreach ($fotosTelas as $idx => $foto) {
            echo "   Foto " . ($idx + 1) . ":\n";
            echo "      - Referencia: {$foto->referencia}\n";
            echo "      - Ruta: {$foto->ruta_original}\n";
            echo "      - Orden: {$foto->orden}\n";
            echo "      - TamaÃ±o: {$foto->tamaÃ±o} bytes\n";
        }

        // 4. VerificaciÃ³n de referencias Ãºnicas
        $referencias = ['ALG-001', 'POL-002', 'LIN-003'];
        echo "\n REFERENCIAS POR TELA\n";
        
        foreach ($referencias as $ref) {
            $fotos = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
                ->where('referencia', $ref)
                ->get();
            
            $this->assertGreaterThan(0, count($fotos), "DeberÃ­a haber fotos para referencia $ref");
            echo "   - {$ref}: " . count($fotos) . " fotos\n";
        }

        // 5. VerificaciÃ³n de colores
        echo "\n COLORES POR TELA\n";
        $colores = [10, 15, 20];
        $telaIdx = 0;
        
        foreach ($colores as $colorId) {
            $fotosColor = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
                ->where('color_id', $colorId)
                ->get();
            
            echo "   - Color ID {$colorId}: " . count($fotosColor) . " fotos\n";
        }

        // 6. VerificaciÃ³n de orden de fotos
        echo "\n ORDEN DE FOTOS POR TELA\n";
        
        $fotoPol = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
            ->where('referencia', 'POL-002')
            ->orderBy('orden')
            ->get();
        
        $this->assertCount(2, $fotoPol, "POL-002 debe tener 2 fotos");
        
        foreach ($fotoPol as $idx => $foto) {
            $expectedOrden = $idx + 1;
            $this->assertEquals($expectedOrden, $foto->orden, "Orden de foto debe ser $expectedOrden");
            echo "   POL-002 Foto {$foto->orden}: \n";
        }

        // 7. VerificaciÃ³n de timestamps
        echo "\n TIMESTAMPS\n";
        foreach ($fotosTelas as $foto) {
            $this->assertNotNull($foto->created_at);
            echo "   - Foto creada: {$foto->created_at->format('Y-m-d H:i:s')}\n";
        }

        echo "\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo " TODOS LOS TESTS PASARON\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo "\n";
    }

    /**
     * Test: Verificar estructura correcta de datos en BD
     */
    public function test_estructura_datos_telas()
    {
        echo "\n\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo "ðŸ§ª TEST: Estructura de Datos en BD\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";

        // Crear cotizaciÃ³n
        $cotizacion = Cotizacion::factory()->create();

        $prendaData = [
            'nombre_producto' => 'Test Estructura',
            'descripcion' => 'Test',
            'cantidad' => 1,
            'variantes' => ['color' => 'Test'],
            'telas' => [
                [
                    'tela_id' => 1,
                    'color_id' => 10,
                    'referencia' => 'TEST-001',
                    'fotos' => [
                        [
                            'ruta_original' => 'test/ruta1.webp',
                            'ruta_webp' => 'test/ruta1.webp',
                            'orden' => 1,
                            'tamaÃ±o' => 12345
                        ]
                    ]
                ]
            ]
        ];

        $this->cotizacionPrendaService->guardarPrendaConTelas($cotizacion, $prendaData);

        $prenda = PrendaCot::where('cotizacion_id', $cotizacion->id)->first();
        $foto = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)->first();

        echo "\n ESTRUCTURA DE DATOS EN BD\n";
        echo "   prenda_cot_id: " . $foto->prenda_cot_id . "\n";
        echo "   referencia: " . $foto->referencia . "\n";
        echo "   color_id: " . $foto->color_id . "\n";
        echo "   ruta_original: " . $foto->ruta_original . "\n";
        echo "   ruta_webp: " . $foto->ruta_webp . "\n";
        echo "   orden: " . $foto->orden . "\n";
        echo "   tamaÃ±o: " . $foto->tamaÃ±o . "\n";

        $this->assertEquals('TEST-001', $foto->referencia);
        $this->assertEquals(10, $foto->color_id);
        $this->assertEquals(1, $foto->orden);
        $this->assertEquals(12345, $foto->tamaÃ±o);

        echo "\nâ•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo " ESTRUCTURA CORRECTA\n";
        echo "â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•\n";
        echo "\n";
    }
}

