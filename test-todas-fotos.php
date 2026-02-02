<?php

/**
 * TEST: Verificar TODAS las fotos de TODAS las prendas
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Todas las fotos de todas las prendas ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$todasLasFotos = [];
$totalPrendas = count($data['data']['prendas']);

echo "Analizando $totalPrendas prendas...\n\n";

foreach ($data['data']['prendas'] as $p => $prenda) {
    echo "=== PRENDA " . ($p + 1) . ": {$prenda['nombre']} ===\n";
    
    // Fotos de telas_array
    if ($prenda['telas_array']) {
        echo "telas_array (" . count($prenda['telas_array']) . " telas):\n";
        foreach ($prenda['telas_array'] as $t => $tela) {
            echo "  Tela $t ({$tela['tela_nombre']}):\n";
            if ($tela['fotos_tela']) {
                foreach ($tela['fotos_tela'] as $foto) {
                    $url = $foto['ruta_webp'] ?? $foto['url'];
                    echo "    ✓ " . substr($url, 0, 55) . "...\n";
                    $todasLasFotos[] = $url;
                }
            }
        }
    }
    
    // Fotos de procesos
    if ($prenda['procesos']) {
        echo "procesos (" . count($prenda['procesos']) . " procesos):\n";
        foreach ($prenda['procesos'] as $proc => $proceso) {
            $nombreProceso = $proceso['proceso_nombre'] ?? $proceso['nombre'] ?? "Proceso $proc";
            echo "  Proceso $proc ($nombreProceso):\n";
            if ($proceso['imagenes']) {
                foreach ($proceso['imagenes'] as $foto) {
                    $url = $foto['ruta_webp'];
                    echo "    ✓ " . substr($url, 0, 55) . "...\n";
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
echo "Total fotos únicas: " . count($fotosUnicas) . "\n";

foreach ($fotosUnicas as $i => $url) {
    echo ($i + 1) . ". " . substr($url, 0, 60) . "...\n";
}

echo "\n✅ TEST COMPLETADO\n";
