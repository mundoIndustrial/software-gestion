<?php

/**
 * TEST: Verificar que las fotos se extraen correctamente de los datos
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Extracción de Fotos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

if (!$data['success']) {
    echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    exit(1);
}

$pedido = $data['data'];

// Simular lo que hace el JavaScript
$todasLasFotos = [];

if ($pedido['prendas'] && is_array($pedido['prendas'])) {
    foreach ($pedido['prendas'] as $prenda) {
        echo "📦 Prenda: " . ($prenda['nombre'] ?? 'N/A') . "\n";
        
        // Fotos de tela (imagenes_tela)
        if (!empty($prenda['imagenes_tela']) && is_array($prenda['imagenes_tela'])) {
            echo "   📸 imagenes_tela: " . count($prenda['imagenes_tela']) . "\n";
            foreach ($prenda['imagenes_tela'] as $img) {
                if ($img['ruta_webp']) {
                    $todasLasFotos[] = $img['ruta_webp'];
                    echo "      ✅ " . $img['ruta_webp'] . "\n";
                }
            }
        }
        
        // Fotos de colores/telas (colores_telas -> fotos_tela)
        if (!empty($prenda['colores_telas']) && is_array($prenda['colores_telas'])) {
            echo "   🎨 colores_telas: " . count($prenda['colores_telas']) . "\n";
            foreach ($prenda['colores_telas'] as $colorTela) {
                if (!empty($colorTela['fotos_tela']) && is_array($colorTela['fotos_tela'])) {
                    echo "      📸 fotos_tela: " . count($colorTela['fotos_tela']) . "\n";
                    foreach ($colorTela['fotos_tela'] as $img) {
                        if ($img['ruta_webp'] || $img['url']) {
                            $url = $img['ruta_webp'] ?? $img['url'];
                            $todasLasFotos[] = $url;
                            echo "         ✅ " . $url . "\n";
                        }
                    }
                }
            }
        }
        
        // Fotos de procesos
        if (!empty($prenda['procesos']) && is_array($prenda['procesos'])) {
            echo "   ⚙️  procesos: " . count($prenda['procesos']) . "\n";
            foreach ($prenda['procesos'] as $proceso) {
                if (!empty($proceso['imagenes']) && is_array($proceso['imagenes'])) {
                    echo "      📸 imagenes: " . count($proceso['imagenes']) . "\n";
                    foreach ($proceso['imagenes'] as $img) {
                        if ($img['ruta_webp']) {
                            $todasLasFotos[] = $img['ruta_webp'];
                            echo "         ✅ " . $img['ruta_webp'] . "\n";
                        }
                    }
                }
            }
        }
        
        echo "\n";
    }
}

// Eliminar duplicados
$fotosUnicas = array_unique($todasLasFotos);

echo "📊 RESUMEN:\n";
echo "   Total fotos (con duplicados): " . count($todasLasFotos) . "\n";
echo "   Total fotos (únicas): " . count($fotosUnicas) . "\n";

if (count($fotosUnicas) > 0) {
    echo "\n✅ Se extraen correctamente " . count($fotosUnicas) . " fotos\n";
} else {
    echo "\n❌ No se encontraron fotos\n";
}

echo "\n✅ TEST COMPLETADO\n";
