<?php

/**
 * TEST: Verificar recibos y otros campos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Verificar estructura de recibos y otros campos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

echo "Campos principales del pedido:\n";
foreach ($data['data'] as $key => $value) {
    if (is_array($value)) {
        echo "📦 $key: ARRAY (" . count($value) . " elementos)\n";
    } elseif (is_object($value)) {
        echo "📦 $key: OBJECT\n";
    }
}

echo "\n=== Recibos ===\n";
if ($data['data']['recibos']) {
    foreach ($data['data']['recibos'] as $i => $recibo) {
        echo "Recibo $i: {$recibo['nombre']}\n";
        echo "  - imagenes: " . (isset($recibo['imagenes']) ? count($recibo['imagenes']) : 'NO EXISTE') . "\n";
        if (isset($recibo['imagenes']) && is_array($recibo['imagenes'])) {
            foreach ($recibo['imagenes'] as $img) {
                echo "    • " . substr($img['ruta_webp'] ?? $img['url'] ?? 'SIN URL', 0, 50) . "\n";
            }
        }
        
        // Buscar cualquier campo con URL
        foreach ($recibo as $k => $v) {
            if (is_array($v) && strpos($k, 'foto') !== false || strpos($k, 'image') !== false) {
                echo "  - $k: " . count($v) . " elementos\n";
            }
        }
    }
}

echo "\n✅ TEST COMPLETADO\n";
