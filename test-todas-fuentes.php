<?php

/**
 * TEST: Detallar TODAS las fotos incluyendo vacías
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Detallar cada fuente incluyendo arrays vacíos ===\n\n";

$controller = app(\App\Infrastructure\Http\Controllers\Operario\OperarioController::class);
$response = $controller->getPedidoData(45807);
$data = json_decode($response->getContent(), true);

$prenda = $data['data']['prendas'][0];

echo "PRENDA: {$prenda['nombre']}\n\n";

// 1. telas_array
echo "1. telas_array:\n";
foreach ($prenda['telas_array'] as $i => $tela) {
    echo "   Tela $i: {$tela['tela_nombre']}\n";
    echo "     - fotos: " . count($tela['fotos']) . " elementos\n";
    foreach ($tela['fotos'] as $f) {
        echo "       • " . substr($f['ruta_webp'] ?? $f['url'], 0, 50) . "\n";
    }
    echo "     - fotos_tela: " . count($tela['fotos_tela']) . " elementos\n";
    foreach ($tela['fotos_tela'] as $f) {
        echo "       • " . substr($f['ruta_webp'] ?? $f['url'], 0, 50) . "\n";
    }
}

// 2. imagenes_tela
echo "\n2. imagenes_tela: " . count($prenda['imagenes_tela']) . " elementos\n";
foreach ($prenda['imagenes_tela'] as $f) {
    echo "   • " . substr($f['ruta_webp'], 0, 50) . "\n";
}

// 3. colores_telas
echo "\n3. colores_telas:\n";
foreach ($prenda['colores_telas'] as $i => $color) {
    echo "   Color $i: {$color['color_nombre']}\n";
    echo "     - fotos: " . count($color['fotos']) . " elementos\n";
    foreach ($color['fotos'] as $f) {
        echo "       • " . substr($f['ruta_webp'] ?? $f['url'], 0, 50) . "\n";
    }
    echo "     - fotos_tela: " . count($color['fotos_tela']) . " elementos\n";
    foreach ($color['fotos_tela'] as $f) {
        echo "       • " . substr($f['ruta_webp'] ?? $f['url'], 0, 50) . "\n";
    }
}

// 4. procesos
echo "\n4. procesos:\n";
foreach ($prenda['procesos'] as $i => $proc) {
    echo "   Proceso $i:\n";
    echo "     - imagenes: " . count($proc['imagenes']) . " elementos\n";
    foreach ($proc['imagenes'] as $f) {
        echo "       • " . substr($f['ruta_webp'], 0, 50) . "\n";
    }
}

// 5. imagenes
echo "\n5. imagenes: " . count($prenda['imagenes']) . " elementos\n";
foreach ($prenda['imagenes'] as $f) {
    echo "   • " . substr($f['ruta_webp'] ?? $f['url'] ?? 'SIN URL', 0, 50) . "\n";
}

echo "\n✅ TEST COMPLETADO\n";
