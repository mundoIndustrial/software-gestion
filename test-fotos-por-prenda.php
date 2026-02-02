<?php

/**
 * TEST: Contar fotos por cada prenda del pedido
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Fotos POR CADA PRENDA del pedido ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$todasLasFotos = [];
$conteoTotal = 0;

foreach ($data['data']['prendas'] as $p => $prenda) {
    echo "PRENDA " . ($p + 1) . ": {$prenda['nombre']}\n";
    
    $fotosEstaPrenda = 0;
    
    // telas_array.fotos_tela
    if ($prenda['telas_array']) {
        foreach ($prenda['telas_array'] as $tela) {
            if ($tela['fotos_tela']) {
                $fotosEstaPrenda += count($tela['fotos_tela']);
                foreach ($tela['fotos_tela'] as $f) {
                    $todasLasFotos[] = $f['ruta_webp'] ?? $f['url'];
                }
            }
        }
    }
    
    // procesos.imagenes
    if ($prenda['procesos']) {
        foreach ($prenda['procesos'] as $proc) {
            if ($proc['imagenes']) {
                $fotosEstaPrenda += count($proc['imagenes']);
                foreach ($proc['imagenes'] as $f) {
                    $todasLasFotos[] = $f['ruta_webp'];
                }
            }
        }
    }
    
    $conteoTotal += $fotosEstaPrenda;
    echo "  → Fotos a mostrar: $fotosEstaPrenda\n\n";
}

$fotosUnicas = array_unique($todasLasFotos);

echo "=== RESUMEN ===\n";
echo "Total prendas: " . count($data['data']['prendas']) . "\n";
echo "Total fotos (con duplicadas): " . $conteoTotal . "\n";
echo "Total fotos ÚNICAS: " . count($fotosUnicas) . "\n";

echo "\nFotos únicas:\n";
foreach ($fotosUnicas as $i => $url) {
    $tipo = strpos($url, '/tela/') ? 'TELA' : 'PROCESO';
    echo ($i + 1) . ". [$tipo] " . substr($url, 0, 55) . "\n";
}

echo "\n✅ TEST COMPLETADO\n";
