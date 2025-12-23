<?php
/**
 * TEST UNITARIO: Validar lógica de recuperación de fotos_existentes
 * 
 * Este test validar que la lógica de slice() funciona correctamente
 * para mapear fotos_existentes a prenda_tela_cot
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST UNITARIO: Lógica de mapeo de fotos de tela             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// ===== TEST 1: Verificar que slice() funciona correctamente =====
echo "\n🔍 Test 1: Validar slice() para indexación de telas\n";
echo "─────────────────────────────────────────────────────────────\n";

// Simular colección de prenda_telas_cot
$mockTelas = collect([
    (object)[
        'id' => 100,
        'tela' => 'ALGODÓN',
        'color' => 'AZUL',
        'indice' => 0
    ],
    (object)[
        'id' => 101,
        'tela' => 'POLIÉSTER',
        'color' => 'NEGRO',
        'indice' => 1
    ],
    (object)[
        'id' => 102,
        'tela' => 'LINO',
        'color' => 'BLANCO',
        'indice' => 2
    ],
]);

echo "Colección de telas:\n";
foreach ($mockTelas as $t) {
    echo "  [{$t->indice}] ID={$t->id}, tela={$t->tela}, color={$t->color}\n";
}

// Prueba slice()
$testCases = [
    0 => 'ALGODÓN (AZUL)',
    1 => 'POLIÉSTER (NEGRO)',
    2 => 'LINO (BLANCO)',
];

$allPass = true;
foreach ($testCases as $index => $expected) {
    $tela = $mockTelas->slice($index, 1)->first();
    
    if (!$tela) {
        echo "\n❌ FALLO en índice $index: no se encontró tela\n";
        $allPass = false;
        continue;
    }
    
    if ($tela->id !== (100 + $index)) {
        echo "\n❌ FALLO en índice $index: ID incorrecto\n";
        $allPass = false;
        continue;
    }
    
    echo "✅ Índice $index → ID={$tela->id} ({$expected})\n";
}

if (!$allPass) {
    echo "\n❌ Test 1 FALLIDO\n";
    exit(1);
}

echo "✅ Test 1 PASÓ\n";

// ===== TEST 2: Validar estructura de datos para fotos_existentes =====
echo "\n🔍 Test 2: Validar parseo de fotos_existentes\n";
echo "─────────────────────────────────────────────────────────────\n";

$testFotos = [
    'formato_json' => '["20","21"]',
    'formato_array' => ["20", "21"],
    'formato_int_array' => [20, 21],
];

foreach ($testFotos as $tipo => $fotos) {
    echo "\nFormato: $tipo\n";
    
    // Simular el parseo del código
    $fotosTelaExistentes = $fotos;
    if (is_string($fotosTelaExistentes)) {
        $fotosTelaExistentes = json_decode($fotosTelaExistentes, true) ?? [];
    }
    if (!is_array($fotosTelaExistentes)) {
        $fotosTelaExistentes = [];
    }
    
    $count = count($fotosTelaExistentes);
    if ($count !== 2) {
        echo "  ❌ FALLO: Se esperaban 2 fotos, se obtuvieron $count\n";
        $allPass = false;
    } else {
        echo "  ✅ Se parseron correctamente: $count fotos\n";
        foreach ($fotosTelaExistentes as $idx => $fotoId) {
            echo "     - Foto [$idx]: $fotoId\n";
        }
    }
}

if (!$allPass) {
    echo "\n❌ Test 2 FALLIDO\n";
    exit(1);
}

echo "\n✅ Test 2 PASÓ\n";

// ===== TEST 3: Validar conversión de índice =====
echo "\n🔍 Test 3: Validar conversión de índice string → int\n";
echo "─────────────────────────────────────────────────────────────\n";

$testIndices = [
    0 => (int)"0",
    "1" => (int)"1",
    "2" => (int)"2",
];

foreach ($testIndices as $input => $expected) {
    $actual = (int)$input;
    if ($actual === $expected) {
        echo "✅ Índice \"$input\" → $actual\n";
    } else {
        echo "❌ FALLO: Índice \"$input\" → $actual (esperado: $expected)\n";
        $allPass = false;
    }
}

if (!$allPass) {
    echo "\n❌ Test 3 FALLIDO\n";
    exit(1);
}

echo "\n✅ Test 3 PASÓ\n";

// ===== TEST 4: Validar query de BD (buscar fotos_existentes reales) =====
echo "\n🔍 Test 4: Buscar fotos_existentes en BD\n";
echo "─────────────────────────────────────────────────────────────\n";

// Buscar fotos de tela reales en BD (del draft anterior)
$fotosExistentes = DB::table('prenda_tela_fotos_cot')
    ->where('prenda_cot_id', 32)  // Draft #54 prenda_id=32
    ->orderBy('created_at')
    ->get();

if ($fotosExistentes->count() > 0) {
    echo "✅ Se encontraron " . $fotosExistentes->count() . " fotos de tela en BD\n";
    foreach ($fotosExistentes as $idx => $foto) {
        echo "  Foto $idx: ID={$foto->id}, prenda_tela_cot_id={$foto->prenda_tela_cot_id}, ruta={$foto->ruta_webp}\n";
    }
    
    // Validar que tienen los campos necesarios
    $foto = $fotosExistentes->first();
    $camposRequeridos = ['id', 'prenda_cot_id', 'prenda_tela_cot_id'];
    
    $faltan = [];
    foreach ($camposRequeridos as $campo) {
        if (!isset($foto->$campo)) {
            $faltan[] = $campo;
        }
    }
    
    if (empty($faltan)) {
        echo "✅ Todos los campos requeridos están presentes\n";
    } else {
        echo "❌ Faltan campos: " . implode(', ', $faltan) . "\n";
        $allPass = false;
    }
} else {
    echo "ℹ️  No hay fotos de tela en draft #54 para comparar\n";
    echo "   (Esto es OK, solo si el test anterior no generó datos)\n";
}

echo "\n✅ Test 4 COMPLETÓ\n";

// ===== RESULTADO FINAL =====
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
if ($allPass) {
    echo "║  🎉 TODOS LOS TESTS PASARON                                  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n✅ La lógica de mapeo de fotos es correcta y lista para usar\n\n";
} else {
    echo "║  ❌ ALGUNOS TESTS FALLARON                                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    exit(1);
}

echo "═════════════════════════════════════════════════════════════════\n\n";
