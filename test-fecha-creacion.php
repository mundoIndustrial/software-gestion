<?php

/**
 * TEST: Verificar que fecha_creacion está en la respuesta
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Verificar fecha_creacion ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$pedido = $data['data'];

echo "1️⃣  Campos en la respuesta:\n";
foreach ($pedido as $key => $value) {
    if (!is_array($value) && !is_object($value)) {
        echo "   - $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
}

echo "\n2️⃣  Verificar campo fecha_creacion:\n";
if (isset($pedido['fecha_creacion'])) {
    echo "   ✅ Campo encontrado: " . ($pedido['fecha_creacion'] ?? 'NULL') . "\n";
} else {
    echo "   ❌ Campo NO encontrado\n";
}

echo "\n✅ TEST COMPLETADO\n";
