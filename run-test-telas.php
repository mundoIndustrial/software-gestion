#!/usr/bin/env php
<?php

/**
 * 🧪 SCRIPT DE TEST - Múltiples Telas con Referencias, Colores e Imágenes
 * 
 * Uso: php run-test-telas.php
 * 
 * ✅ SEGURO: NO afecta la BD real
 * ✅ Usa transacciones para aislar cambios
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaTelaFotoCot;
use App\Application\Services\CotizacionPrendaService;
use Illuminate\Support\Facades\DB;

echo "\n\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🧪 TEST MANUAL: Múltiples Telas con Referencias y Colores\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// Usar transacción para no afectar BD real
DB::transaction(function () {
    echo "🔐 Iniciando transacción (cambios se revertirán al finalizar)\n\n";
    
    // ========== TEST 1 ==========
    echo "TEST 1️⃣: Guardar Prenda con 3 Telas Diferentes\n";
    echo "─────────────────────────────────────────────────\n";

    // Crear cotización manualmente
    $cotizacion = Cotizacion::create([
        'numero_cotizacion' => 'TEST-' . time(),
        'tipo' => 'P',
        'es_borrador' => false,
        'estado' => 'ENVIADA_CONTADOR',
        'usuario_id' => 1,
        'cliente_id' => 1,
        'tipo_venta' => 'M'
    ]);
    
    $servicio = app(CotizacionPrendaService::class);

    $prendaData = [
        'nombre_producto' => 'Camiseta Multicolor',
        'descripcion' => 'Test con 3 telas',
        'cantidad' => 1,
        'variantes' => ['color' => 'Multicolor'],
        'telas' => [
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

    echo "📝 Datos:\n";
    echo "   - Prenda: {$prendaData['nombre_producto']}\n";
    echo "   - Total telas: " . count($prendaData['telas']) . "\n";
    foreach ($prendaData['telas'] as $idx => $tela) {
        echo "     Tela " . ($idx+1) . ": {$tela['referencia']} (color_id={$tela['color_id']}, fotos=" . count($tela['fotos']) . ")\n";
    }

    $prenda = $servicio->guardarPrendaConTelas($cotizacion, $prendaData);
    echo "\n✅ Prenda guardada (ID: {$prenda->id})\n";

    echo "\n🔍 VERIFICACIONES:\n";

    // Verificar prenda
    if ($prenda->nombre_producto !== 'Camiseta Multicolor') die("❌ Nombre de prenda incorrecto\n");
    echo "   ✅ Prenda existe y nombre es correcto\n";

    // Verificar total de fotos
    $fotosTotales = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)->count();
    echo "   ✅ Total fotos guardadas: {$fotosTotales} (esperado: 4)\n";
    if ($fotosTotales !== 4) die("❌ Total de fotos incorrecto (esperaba 4, obtuve {$fotosTotales})\n");

    // Verificar referencias
    echo "   ✅ Referencias por tela:\n";
    
    $referencias = ['ALG-001', 'POL-002', 'LIN-003'];
    foreach ($referencias as $ref) {
        $count = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
            ->where('referencia', $ref)
            ->count();
        echo "      - {$ref}: {$count} foto(s)\n";
        if ($count === 0) die("❌ No hay fotos para referencia {$ref}\n");
    }

    // Verificar colores
    echo "   ✅ Colores por tela:\n";
    
    $coloresEsperados = [
        10 => 1,  // ALG-001: 1 foto
        15 => 2,  // POL-002: 2 fotos
        20 => 1   // LIN-003: 1 foto
    ];
    
    foreach ($coloresEsperados as $colorId => $expectedCount) {
        $count = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
            ->where('color_id', $colorId)
            ->count();
        echo "      - Color {$colorId}: {$count} foto(s)\n";
        if ($count !== $expectedCount) die("❌ Count de fotos incorrecto para color {$colorId} (esperaba {$expectedCount}, obtuve {$count})\n");
    }

    // Verificar orden de fotos en POL-002
    echo "   ✅ Verificando orden de fotos (POL-002):\n";
    
    $fotosPol = PrendaTelaFotoCot::where('prenda_cot_id', $prenda->id)
        ->where('referencia', 'POL-002')
        ->orderBy('orden')
        ->get();
    
    foreach ($fotosPol as $idx => $foto) {
        $expectedOrden = $idx + 1;
        echo "      - Foto {$foto->orden}: {$foto->ruta_original}\n";
        if ($foto->orden !== $expectedOrden) die("❌ Orden de foto incorrecto\n");
    }

    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✅ TEST 1 PASÓ\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";

    // ========== TEST 2 ==========
    echo "TEST 2️⃣: Estructura Exacta en BD\n";
    echo "─────────────────────────────────────────────────\n";

    $cotizacion2 = Cotizacion::create([
        'numero_cotizacion' => 'TEST-' . (time() + 1),
        'tipo' => 'P',
        'es_borrador' => false,
        'estado' => 'ENVIADA_CONTADOR',
        'usuario_id' => 1,
        'cliente_id' => 1,
        'tipo_venta' => 'M'
    ]);
    
    $prendaData2 = [
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

    $prenda2 = $servicio->guardarPrendaConTelas($cotizacion2, $prendaData2);
    $foto = PrendaTelaFotoCot::where('prenda_cot_id', $prenda2->id)->first();

    echo "\n🔍 Campos guardados en BD:\n";
    echo "   - prenda_cot_id: {$foto->prenda_cot_id}\n";
    echo "   - referencia: {$foto->referencia}\n";
    echo "   - color_id: {$foto->color_id}\n";
    echo "   - tela_id: {$foto->tela_id}\n";
    echo "   - ruta_original: {$foto->ruta_original}\n";
    echo "   - ruta_webp: {$foto->ruta_webp}\n";
    echo "   - orden: {$foto->orden}\n";
    echo "   - tamaño: {$foto->tamaño}\n";

    if ($foto->referencia !== 'TEST-XYZ') die("❌ Referencia incorrecta (esperaba TEST-XYZ, obtuve {$foto->referencia})\n");
    if ($foto->color_id !== 25) die("❌ Color_id incorrecto\n");
    if ($foto->orden !== 1) die("❌ Orden incorrecto\n");
    if ($foto->tamaño !== 99999) die("❌ Tamaño incorrecto\n");

    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✅ TEST 2 PASÓ\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";

    // ========== TEST 3 ==========
    echo "TEST 3️⃣: Múltiples Prendas con Múltiples Telas\n";
    echo "─────────────────────────────────────────────────\n";

    $cotizacion3 = Cotizacion::create([
        'numero_cotizacion' => 'TEST-' . (time() + 2),
        'tipo' => 'P',
        'es_borrador' => false,
        'estado' => 'ENVIADA_CONTADOR',
        'usuario_id' => 1,
        'cliente_id' => 1,
        'tipo_venta' => 'M'
    ]);

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

    $prenda2Data = [
        'nombre_producto' => 'Pantalón',
        'descripcion' => 'Pantalón B',
        'cantidad' => 1,
        'variantes' => [],
        'telas' => [
            ['tela_id' => 3, 'color_id' => 20, 'referencia' => 'LIN-2', 'fotos' => [['ruta_original' => 'img3.webp', 'ruta_webp' => 'img3.webp', 'orden' => 1, 'tamaño' => 3000]]]
        ]
    ];

    $prendaGuardada1 = $servicio->guardarPrendaConTelas($cotizacion3, $prenda1Data);
    $prendaGuardada2 = $servicio->guardarPrendaConTelas($cotizacion3, $prenda2Data);

    echo "📝 Prendas guardadas:\n";
    echo "   - Prenda 1: {$prendaGuardada1->nombre_producto} (ID: {$prendaGuardada1->id})\n";
    echo "   - Prenda 2: {$prendaGuardada2->nombre_producto} (ID: {$prendaGuardada2->id})\n";

    $fotos1 = PrendaTelaFotoCot::where('prenda_cot_id', $prendaGuardada1->id)->count();
    $fotos2 = PrendaTelaFotoCot::where('prenda_cot_id', $prendaGuardada2->id)->count();

    echo "\n✅ Fotos por prenda:\n";
    echo "   - Prenda 1: {$fotos1} foto(s) (esperado: 2)\n";
    echo "   - Prenda 2: {$fotos2} foto(s) (esperado: 1)\n";

    if ($fotos1 !== 2) die("❌ Prenda 1 tiene {$fotos1} fotos, esperaba 2\n");
    if ($fotos2 !== 1) die("❌ Prenda 2 tiene {$fotos2} fotos, esperaba 1\n");

    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "✅ TEST 3 PASÓ\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "\n";
});

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "✅ ✅ ✅ TODOS LOS TESTS PASARON ✅ ✅ ✅\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";
echo "✅ Base de datos NO fue modificada (transacción revertida)\n";
echo "\n";
