<?php

/**
 * TEST: Profundizar en telas_array y fotos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Profundizar en telas_array ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "1. telas_array:\n";
print_r($prenda['telas_array']);

echo "\n2. colores_telas:\n";
foreach ($prenda['colores_telas'] as $i => $color) {
    echo "\n   Color $i:\n";
    foreach ($color as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: ARRAY (" . count($value) . " elementos)\n";
        } else {
            echo "   - $key: " . substr((string)$value, 0, 40) . "\n";
        }
    }
}

echo "\n3. variantes:\n";
foreach ($prenda['variantes'] as $i => $var) {
    echo "\n   Variante $i:\n";
    foreach ($var as $key => $value) {
        if (is_array($value)) {
            echo "   - $key: ARRAY (" . count($value) . " elementos)\n";
        } else {
            echo "   - $key: " . substr((string)$value, 0, 40) . "\n";
        }
    }
}

echo "\n✅ TEST COMPLETADO\n";
