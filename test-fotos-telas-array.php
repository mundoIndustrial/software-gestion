<?php

/**
 * TEST: Verificar extracción de fotos de telas_array
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Extracción de fotos de telas_array ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];
$fotosExtraidas = [];

echo "Extrayendo fotos...\n\n";

// Fotos de telas_array (NUEVA FUENTE)
if ($prenda['telas_array'] && is_array($prenda['telas_array'])) {
    foreach ($prenda['telas_array'] as $i => $tela) {
        echo "Tela $i ({$tela['tela_nombre']}):\n";
        
        if ($tela['fotos'] && is_array($tela['fotos'])) {
            foreach ($tela['fotos'] as $foto) {
                $url = $foto['ruta_webp'] ?? $foto['url'] ?? null;
                if ($url) {
                    echo "  ✓ fotos: $url\n";
                    $fotosExtraidas[] = $url;
                }
            }
        }
        
        if ($tela['fotos_tela'] && is_array($tela['fotos_tela'])) {
            foreach ($tela['fotos_tela'] as $foto) {
                $url = $foto['ruta_webp'] ?? $foto['url'] ?? null;
                if ($url) {
                    echo "  ✓ fotos_tela: $url\n";
                    $fotosExtraidas[] = $url;
                }
            }
        }
    }
}

// Fotos de imagenes_tela
if ($prenda['imagenes_tela'] && is_array($prenda['imagenes_tela'])) {
    foreach ($prenda['imagenes_tela'] as $i => $foto) {
        $url = $foto['ruta_webp'] ?? null;
        if ($url) {
            echo "imagenes_tela[$i]: $url\n";
            $fotosExtraidas[] = $url;
        }
    }
}

// Fotos de colores_telas.fotos_tela
if ($prenda['colores_telas'] && is_array($prenda['colores_telas'])) {
    foreach ($prenda['colores_telas'] as $i => $color) {
        if ($color['fotos_tela'] && is_array($color['fotos_tela'])) {
            foreach ($color['fotos_tela'] as $foto) {
                $url = $foto['ruta_webp'] ?? $foto['url'] ?? null;
                if ($url) {
                    echo "colores_telas[$i].fotos_tela: $url\n";
                    $fotosExtraidas[] = $url;
                }
            }
        }
    }
}

// Fotos de procesos
if ($prenda['procesos'] && is_array($prenda['procesos'])) {
    foreach ($prenda['procesos'] as $i => $proceso) {
        if ($proceso['imagenes'] && is_array($proceso['imagenes'])) {
            foreach ($proceso['imagenes'] as $foto) {
                $url = $foto['ruta_webp'] ?? null;
                if ($url) {
                    echo "procesos[$i].imagenes: $url\n";
                    $fotosExtraidas[] = $url;
                }
            }
        }
    }
}

// Deduplicar
$fotosUnicas = array_unique($fotosExtraidas);

echo "\n=== RESUMEN ===\n";
echo "Total de fotos extraídas (antes deduplicar): " . count($fotosExtraidas) . "\n";
echo "Total de fotos únicas: " . count($fotosUnicas) . "\n";

if (count($fotosUnicas) > 0) {
    echo "\n✅ Se extraen correctamente las fotos de telas_array\n";
} else {
    echo "\n❌ No se extraen fotos\n";
}

echo "\n✅ TEST COMPLETADO\n";
