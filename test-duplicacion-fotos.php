<?php

/**
 * TEST: Analizar por qué las fotos se duplican
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Análisis de duplicación de fotos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "Analizando fuentes de fotos:\n\n";

$fotos = [
    'telas_array.fotos' => [],
    'telas_array.fotos_tela' => [],
    'imagenes_tela' => [],
    'colores_telas.fotos_tela' => [],
    'procesos.imagenes' => []
];

// 1. telas_array.fotos
if ($prenda['telas_array']) {
    foreach ($prenda['telas_array'] as $tela) {
        if ($tela['fotos']) {
            foreach ($tela['fotos'] as $f) {
                $fotos['telas_array.fotos'][] = $f['ruta_webp'] ?? $f['url'];
            }
        }
    }
}

// 2. telas_array.fotos_tela
if ($prenda['telas_array']) {
    foreach ($prenda['telas_array'] as $tela) {
        if ($tela['fotos_tela']) {
            foreach ($tela['fotos_tela'] as $f) {
                $fotos['telas_array.fotos_tela'][] = $f['ruta_webp'] ?? $f['url'];
            }
        }
    }
}

// 3. imagenes_tela
if ($prenda['imagenes_tela']) {
    foreach ($prenda['imagenes_tela'] as $f) {
        $fotos['imagenes_tela'][] = $f['ruta_webp'];
    }
}

// 4. colores_telas.fotos_tela
if ($prenda['colores_telas']) {
    foreach ($prenda['colores_telas'] as $color) {
        if ($color['fotos_tela']) {
            foreach ($color['fotos_tela'] as $f) {
                $fotos['colores_telas.fotos_tela'][] = $f['ruta_webp'] ?? $f['url'];
            }
        }
    }
}

// 5. procesos.imagenes
if ($prenda['procesos']) {
    foreach ($prenda['procesos'] as $proc) {
        if ($proc['imagenes']) {
            foreach ($proc['imagenes'] as $f) {
                $fotos['procesos.imagenes'][] = $f['ruta_webp'];
            }
        }
    }
}

foreach ($fotos as $fuente => $urls) {
    echo "$fuente: " . count($urls) . " fotos\n";
    foreach ($urls as $url) {
        echo "  - " . substr($url, 0, 50) . "...\n";
    }
}

// Contar duplicadas
$todas = array_merge(...array_values($fotos));
$unicas = array_unique($todas);

echo "\n=== RESUMEN ===\n";
echo "Total de referencias (con duplicadas): " . count($todas) . "\n";
echo "Total únicas: " . count($unicas) . "\n";
echo "Duplicadas encontradas: " . (count($todas) - count($unicas)) . "\n";

// Mostrar qué fotos son iguales
echo "\n=== ANÁLISIS DE DUPLICACIÓN ===\n";
$conteos = array_count_values($todas);
foreach ($conteos as $url => $count) {
    if ($count > 1) {
        echo "La foto '" . substr($url, 0, 40) . "...' aparece $count veces en diferentes fuentes\n";
    }
}

echo "\n✅ TEST COMPLETADO\n";
