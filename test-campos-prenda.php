<?php

/**
 * TEST: Listar TODOS los campos de la prenda
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Todos los campos de la prenda ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ Error\n";
    exit(1);
}

$pedido = $data['data'];

if ($pedido['prendas'] && is_array($pedido['prendas'])) {
    $prenda = $pedido['prendas'][0];
    
    echo "Campos principales de la prenda:\n";
    foreach ($prenda as $key => $value) {
        if (is_array($value)) {
            echo "📦 $key: ARRAY (" . count($value) . " elementos)\n";
        } else if (is_object($value)) {
            echo "📦 $key: OBJECT\n";
        } else {
            echo "   $key: " . substr((string)$value, 0, 50) . "\n";
        }
    }
}

echo "\n✅ TEST COMPLETADO\n";
