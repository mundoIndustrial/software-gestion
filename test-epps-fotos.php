<?php

/**
 * TEST: Verificar fotos en EPPs
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Fotos en EPPs ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

echo "EPPs en el pedido:\n";
if ($data['data']['epps'] && is_array($data['data']['epps'])) {
    foreach ($data['data']['epps'] as $i => $epp) {
        echo "\nEPP $i: {$epp['nombre']}\n";
        
        // Mostrar todos los campos del EPP
        foreach ($epp as $key => $value) {
            if (is_array($value)) {
                echo "  - $key: ARRAY (" . count($value) . " elementos)\n";
                if ($key === 'imagenes' || strpos($key, 'foto') !== false || strpos($key, 'image') !== false) {
                    foreach ($value as $img) {
                        if (is_array($img) && isset($img['ruta_webp'])) {
                            echo "    • " . substr($img['ruta_webp'], 0, 50) . "\n";
                        }
                    }
                }
            } elseif (is_string($value) && strpos($value, '/storage/') !== false) {
                echo "  - $key: " . substr($value, 0, 50) . "\n";
            }
        }
    }
} else {
    echo "No hay EPPs\n";
}

echo "\n✅ TEST COMPLETADO\n";
