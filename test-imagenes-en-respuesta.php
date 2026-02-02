<?php

/**
 * TEST: Verificar si imagenes está en la respuesta del API
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Verificar campo imagenes en respuesta ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "Campos de la prenda:\n";
foreach (['id', 'nombre', 'imagenes', 'imagenes_tela', 'colores_telas', 'telas_array', 'procesos'] as $campo) {
    if (isset($prenda[$campo])) {
        if (is_array($prenda[$campo])) {
            echo "✓ $campo: ARRAY (" . count($prenda[$campo]) . " elementos)\n";
        } else {
            echo "✓ $campo: " . substr((string)$prenda[$campo], 0, 40) . "\n";
        }
    } else {
        echo "✗ $campo: NO EXISTE\n";
    }
}

echo "\nDetalle de imagenes:\n";
if (isset($prenda['imagenes']) && is_array($prenda['imagenes'])) {
    echo "Fotos en 'imagenes': " . count($prenda['imagenes']) . "\n";
    foreach ($prenda['imagenes'] as $img) {
        echo "  - " . $img['ruta_webp'] . "\n";
    }
} else {
    echo "Campo imagenes no existe o está vacío\n";
}

echo "\n✅ TEST COMPLETADO\n";
