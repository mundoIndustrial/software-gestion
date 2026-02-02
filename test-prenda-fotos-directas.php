<?php

/**
 * TEST: Verificar fotos directas en prenda_fotos_pedido
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Fotos directas de prenda_fotos_pedido ===\n\n";

// Pedido 45807 tiene pedido_produccion_id = 3
$prenda = \App\Models\PrendaPedido::where('pedido_produccion_id', 3)->first();

if ($prenda) {
    echo "Prenda: {$prenda->nombre_prenda}\n";
    
    // Verificar si el modelo tiene relación 'fotos'
    $fotosCount = $prenda->fotos()->count();
    echo "Fotos directas en BD: $fotosCount\n";
    
    if ($fotosCount > 0) {
        $fotos = $prenda->fotos()->orderBy('orden')->get();
        foreach ($fotos as $foto) {
            echo "  - " . substr($foto->ruta_webp, 0, 55) . "\n";
        }
    }
} else {
    echo "Prenda no encontrada\n";
}

echo "\n✅ TEST COMPLETADO\n";
