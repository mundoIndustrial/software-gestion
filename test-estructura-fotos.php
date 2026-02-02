<?php

/**
 * TEST: Verificar estructura completa de fotos en el pedido
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Estructura Completa de Fotos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$pedido = $data['data'];

// Analizar estructura de prendas
if ($pedido['prendas'] && is_array($pedido['prendas'])) {
    foreach ($pedido['prendas'] as $idx => $prenda) {
        echo "📦 Prenda $idx: " . ($prenda['nombre'] ?? 'N/A') . "\n";
        
        // Listar TODOS los campos que contienen "imagen", "foto", "image", "photo"
        foreach ($prenda as $key => $value) {
            if (stripos($key, 'imagen') !== false || stripos($key, 'foto') !== false) {
                echo "   🔍 $key: ";
                if (is_array($value)) {
                    echo "ARRAY con " . count($value) . " elementos\n";
                    if (count($value) > 0) {
                        $first = $value[0];
                        if (is_array($first)) {
                            echo "      Primer elemento tiene claves: " . implode(", ", array_keys($first)) . "\n";
                            // Mostrar rutas
                            if (isset($first['ruta_webp'])) {
                                echo "      ✅ ruta_webp: " . $first['ruta_webp'] . "\n";
                            }
                            if (isset($first['ruta_original'])) {
                                echo "      ✅ ruta_original: " . $first['ruta_original'] . "\n";
                            }
                            if (isset($first['url'])) {
                                echo "      ✅ url: " . $first['url'] . "\n";
                            }
                        }
                    }
                } else {
                    echo "NO ARRAY\n";
                }
            }
        }
        echo "\n";
    }
}

echo "\n✅ TEST COMPLETADO\n";
