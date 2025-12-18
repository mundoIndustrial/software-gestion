<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaTelaFotoCot;
use App\Application\Services\CotizacionPrendaService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * 🧪 TEST: Múltiples Telas con Referencias, Colores e Imágenes
 * 
 * ✅ SEGURO: Usa DatabaseTransactions - NO afecta la BD real
 * ✅ Los cambios se revierten automáticamente después del test
 * 
 * Verifica que:
 * - Se guardan múltiples telas (3 telas)
 * - Cada tela tiene su referencia
 * - Cada tela tiene su color_id
 * - Cada tela tiene sus imágenes (1, 2 o 3 imágenes por tela)
 * - Las imágenes se asocian correctamente a cada tela
 */
class CotizacionMultiplesTelasTest extends TestCase
{
    use DatabaseTransactions; // ✅ AISLADO - No afecta BD real
    
    /**
     * Test 1: Guardar prenda con 3 telas diferentes
     */
    public function test_guardar_prenda_con_tres_telas_diferentes()
    {
        $this->printTitulo('Guardar Prenda con 3 Telas Diferentes');

        // Crear cotización
        $cotizacion = Cotizacion::factory()->create();
        
        // Crear servicio
        $servicio = app(CotizacionPrendaService::class);

        // Datos de prenda con 3 telas
        $prendaData = [
            'nombre_producto' => 'Camiseta Multicolor',
            'descripcion' => 'Test con 3 telas',
            'cantidad' => 1,
            'variantes' => ['color' => 'Multicolor'],
            'telas' => [
                // TELA 1: Algodón - 1 imagen
                [
                    'tela_id' => 1,
                    'color_id' => 10,
                    'referencia' => 'ALG-001',
                    'fotos' => [
                        [
                            'ruta_original' => 'telas/algodon1.webp',
                            'ruta_webp' => 'telas/algodon1.webp',
                            'orden' => 1,
                            'tamaño' => 45678
                        ]
                    ]
                ],
                // TELA 2: Poliéster - 2 imágenes
                [
                    'tela_id' => 2,
                    'color_id' => 15,
                    'referencia' => 'POL-002',
                    'fotos' => [
                        [
                            'ruta_original' => 'telas/poliester1.webp',
                            'ruta_webp' => 'telas/poliester1.webp',
                            'orden' => 1,
                            'tamaño' => 52341
                        ],
                        [
                            'ruta_original' => 'telas/poliester2.webp',
                            'ruta_webp' => 'telas/poliester2.webp',
                            'orden' => 2,
                            'tamaño' => 48756
                        ]
                    ]
                ],
                // TELA 3: Lino - 1 imagen
                [
                    'tela_id' => 3,
                    'color_id' => 20,
                    'referencia' => 'LIN-003',
                    'fotos' => [
                        [
                            'ruta_original' => 'telas/lino1.webp',
                            'ruta_webp' => 'telas/lino1.webp',
                            'orden' => 1,
                            'tamaño' => 61234
                        ]
                    ]
                ]
            ]
        ];

        echo "  📝 Datos:\n";
        echo "     - Prenda: {$prendaData['nombre_producto']}\n";
        echo "     - Total telas: " . count($prendaData['telas']) . "\n";
        foreach ($prendaData['telas'] as $idx => $tela) {
            echo "       Tela " . ($idx+1) . ": {$tela['referencia']} (color_id={$tela['color_id']}, fotos=" . count($tela['fotos']) . ")\n";
        }

        // Guardar prenda
        $prenda = $servicio->guardarPrendaConTelas($cotizacion, $prendaData);
        
        echo "\n  ✅ Prenda guardada (ID: {$prenda->id})\n";

        // VERIFICACIONES
        echo "\n  🔍 VERIFICACIONES:\n";

        // 1. Verificar prenda existe
        $this->assertNotNull($prenda);
        $this->assertEquals('Camiseta Multicolor', $prenda->nombre_producto);
        echo "     ✅ Prenda existe y nombre es correcto\n";

        // 2. Verificar total de fotos
        $fotosTotales = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)->count();
        $this->assertEquals(4, $fotosTotales);
        echo "     ✅ Total fotos guardadas: {$fotosTotales} (esperado: 4)\n";

        // 3. Verificar referencias
        echo "     ✅ Referencias por tela:\n";
        
        $referencias = ['ALG-001', 'POL-002', 'LIN-003'];
        foreach ($referencias as $ref) {
            $count = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
                ->where('referencia', $ref)
                ->count();
            $this->assertGreaterThan(0, $count);
            echo "        - {$ref}: {$count} foto(s)\n";
        }

        // 4. Verificar colores
        echo "     ✅ Colores por tela:\n";
        
        $colores = [10, 15, 20];
        $coloresEsperados = [
            10 => 1,  // ALG-001: 1 foto
            15 => 2,  // POL-002: 2 fotos
            20 => 1   // LIN-003: 1 foto
        ];
        
        foreach ($colores as $colorId) {
            $count = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
                ->where('color_id', $colorId)
                ->count();
            $this->assertEquals($coloresEsperados[$colorId], $count);
            echo "        - Color {$colorId}: {$count} foto(s)\n";
        }

        // 5. Verificar POL-002 tiene 2 fotos ordenadas
        echo "     ✅ Verificando orden de fotos (POL-002):\n";
        
        $fotosPol = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
            ->where('referencia', 'POL-002')
            ->orderBy('orden')
            ->get();
        
        $this->assertCount(2, $fotosPol);
        
        foreach ($fotosPol as $idx => $foto) {
            $expectedOrden = $idx + 1;
            $this->assertEquals($expectedOrden, $foto->orden);
            echo "        - Foto {$foto->orden}: {$foto->ruta_original}\n";
        }

        echo "\n";
        $this->printExito('Test completado');
    }

    /**
     * Test 2: Verificar estructura exacta en BD
     */
    public function test_estructura_exacta_en_base_datos()
    {
        $this->printTitulo('Verificar Estructura Exacta en BD');

        $cotizacion = Cotizacion::factory()->create();
        $servicio = app(CotizacionPrendaService::class);

        $prendaData = [
            'nombre_producto' => 'Test Estructura',
            'descripcion' => 'Test',
            'cantidad' => 1,
            'variantes' => [],
            'telas' => [
                [
                    'tela_id' => 5,
                    'color_id' => 25,
                    'referencia' => 'TEST-XYZ',
                    'fotos' => [
                        [
                            'ruta_original' => 'ruta/test.webp',
                            'ruta_webp' => 'ruta/test.webp',
                            'orden' => 1,
                            'tamaño' => 99999
                        ]
                    ]
                ]
            ]
        ];

        $prenda = $servicio->guardarPrendaConTelas($cotizacion, $prendaData);
        $foto = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)->first();

        echo "  🔍 Campos guardados en BD:\n";
        echo "     - prenda_cot_id: {$foto->prenda_cot_id}\n";
        echo "     - referencia: {$foto->referencia}\n";
        echo "     - color_id: {$foto->color_id}\n";
        echo "     - tela_id: {$foto->tela_id}\n";
        echo "     - ruta_original: {$foto->ruta_original}\n";
        echo "     - ruta_webp: {$foto->ruta_webp}\n";
        echo "     - orden: {$foto->orden}\n";
        echo "     - tamaño: {$foto->tamaño}\n";

        // Verificaciones
        $this->assertEquals($prenda->id, $foto->prenda_cot_id);
        $this->assertEquals('TEST-XYZ', $foto->referencia);
        $this->assertEquals(25, $foto->color_id);
        $this->assertEquals(5, $foto->tela_id);
        $this->assertEquals('ruta/test.webp', $foto->ruta_original);
        $this->assertEquals(1, $foto->orden);
        $this->assertEquals(99999, $foto->tamaño);

        echo "\n";
        $this->printExito('Estructura verificada correctamente');
    }

    /**
     * Test 3: Múltiples prendas con múltiples telas
     */
    public function test_multiples_prendas_con_multiples_telas()
    {
        $this->printTitulo('Múltiples Prendas con Múltiples Telas');

        $cotizacion = Cotizacion::factory()->create();
        $servicio = app(CotizacionPrendaService::class);

        // Prenda 1
        $prenda1Data = [
            'nombre_producto' => 'Camiseta',
            'descripcion' => 'Camiseta A',
            'cantidad' => 1,
            'variantes' => [],
            'telas' => [
                ['tela_id' => 1, 'color_id' => 10, 'referencia' => 'ALG-1', 'fotos' => [['ruta_original' => 'img1.webp', 'ruta_webp' => 'img1.webp', 'orden' => 1, 'tamaño' => 1000]]],
                ['tela_id' => 2, 'color_id' => 11, 'referencia' => 'POL-1', 'fotos' => [['ruta_original' => 'img2.webp', 'ruta_webp' => 'img2.webp', 'orden' => 1, 'tamaño' => 2000]]]
            ]
        ];

        // Prenda 2
        $prenda2Data = [
            'nombre_producto' => 'Pantalón',
            'descripcion' => 'Pantalón B',
            'cantidad' => 1,
            'variantes' => [],
            'telas' => [
                ['tela_id' => 3, 'color_id' => 20, 'referencia' => 'LIN-2', 'fotos' => [['ruta_original' => 'img3.webp', 'ruta_webp' => 'img3.webp', 'orden' => 1, 'tamaño' => 3000]]]
            ]
        ];

        $prenda1 = $servicio->guardarPrendaConTelas($cotizacion, $prenda1Data);
        $prenda2 = $servicio->guardarPrendaConTelas($cotizacion, $prenda2Data);

        echo "  📝 Prendas guardadas:\n";
        echo "     - Prenda 1: {$prenda1->nombre_producto} (ID: {$prenda1->id})\n";
        echo "     - Prenda 2: {$prenda2->nombre_producto} (ID: {$prenda2->id})\n";

        // Verificar fotos por prenda
        $fotos1 = PrendaTelaFotoCot::where('prenda_cot_id', $prenda1->id)->count();
        $fotos2 = PrendaTelaFotoCot::where('prenda_cot_id', $prenda2->id)->count();

        echo "\n  ✅ Fotos por prenda:\n";
        echo "     - Prenda 1: {$fotos1} foto(s) (esperado: 2)\n";
        echo "     - Prenda 2: {$fotos2} foto(s) (esperado: 1)\n";

        $this->assertEquals(2, $fotos1);
        $this->assertEquals(1, $fotos2);

        echo "\n";
        $this->printExito('Múltiples prendas guardadas correctamente');
    }

    // ========== HELPER METHODS ==========
    
    private function printTitulo($titulo)
    {
        echo "\n";
        echo "════════════════════════════════════════════════════════\n";
        echo "🧪 TEST: {$titulo}\n";
        echo "════════════════════════════════════════════════════════\n";
    }

    private function printExito($mensaje)
    {
        echo "════════════════════════════════════════════════════════\n";
        echo "✅ {$mensaje}\n";
        echo "════════════════════════════════════════════════════════\n";
        echo "\n";
    }
}
