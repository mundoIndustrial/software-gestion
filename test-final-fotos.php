<?php

/**
 * TEST FINAL: Verificar que se extraen TODAS las fotos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST FINAL: Todas las fotos con nueva lógica ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$todasLasFotos = [];

foreach ($data['data']['prendas'] as $prenda) {
    echo "PRENDA: {$prenda['nombre']}\n\n";
    
    // 1. Fotos directas de prenda
    if (isset($prenda['imagenes']) && is_array($prenda['imagenes'])) {
        echo "1. Fotos directas de prenda (" . count($prenda['imagenes']) . "):\n";
        foreach ($prenda['imagenes'] as $img) {
            $url = $img['ruta_webp'] ?? $img['url'];
            echo "   ✓ " . substr($url, 0, 55) . "\n";
            $todasLasFotos[] = $url;
        }
    }
    
    // 2. Fotos de telas
    if (isset($prenda['telas_array']) && is_array($prenda['telas_array'])) {
        echo "\n2. Fotos de telas (" . array_sum(array_map(function($t) { return count($t['fotos_tela'] ?? []); }, $prenda['telas_array'])) . "):\n";
        foreach ($prenda['telas_array'] as $tela) {
            if (isset($tela['fotos_tela']) && is_array($tela['fotos_tela'])) {
                foreach ($tela['fotos_tela'] as $img) {
                    $url = $img['ruta_webp'] ?? $img['url'];
                    echo "   ✓ " . substr($url, 0, 55) . "\n";
                    $todasLasFotos[] = $url;
                }
            }
        }
    }
    
    // 3. Fotos de procesos
    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
        $totalProcesos = array_sum(array_map(function($p) { return count($p['imagenes'] ?? []); }, $prenda['procesos']));
        echo "\n3. Fotos de procesos ($totalProcesos):\n";
        foreach ($prenda['procesos'] as $proc) {
            if (isset($proc['imagenes']) && is_array($proc['imagenes'])) {
                foreach ($proc['imagenes'] as $img) {
                    $url = $img['ruta_webp'];
                    echo "   ✓ " . substr($url, 0, 55) . "\n";
                    $todasLasFotos[] = $url;
                }
            }
        }
    }
    
    echo "\n";
}

$fotosUnicas = array_unique($todasLasFotos);

echo "=== RESUMEN FINAL ===\n";
echo "Total referencias: " . count($todasLasFotos) . "\n";
echo "Total fotos ÚNICAS: " . count($fotosUnicas) . "\n\n";

echo "Fotos únicas:\n";
foreach ($fotosUnicas as $i => $url) {
    echo ($i + 1) . ". " . substr($url, 0, 60) . "...\n";
}

echo "\n✅ TEST COMPLETADO\n";
