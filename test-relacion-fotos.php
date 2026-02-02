<?php

/**
 * TEST: Verificar que la relación fotos() funciona
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Verificar relación fotos() en modelo ===\n\n";

$prenda = \App\Models\PrendaPedido::where('pedido_produccion_id', 3)->first();

if ($prenda) {
    echo "Prenda: {$prenda->nombre_prenda}\n";
    echo "ID: {$prenda->id}\n\n";
    
    // Cargar la relación
    $prenda->load('fotos');
    
    echo "Fotos cargadas mediante load(): " . $prenda->fotos->count() . "\n";
    
    if ($prenda->fotos->count() > 0) {
        foreach ($prenda->fotos as $foto) {
            echo "  - ID: {$foto->id}, ruta_webp: " . substr($foto->ruta_webp, 0, 50) . "\n";
        }
    }
    
    echo "\nUsando query directa:\n";
    $fotosDirectas = $prenda->fotos()->get();
    echo "Fotos via query: " . $fotosDirectas->count() . "\n";
    
    if ($fotosDirectas->count() > 0) {
        foreach ($fotosDirectas as $foto) {
            echo "  - ID: {$foto->id}, ruta_webp: " . substr($foto->ruta_webp, 0, 50) . "\n";
        }
    }
    
} else {
    echo "Prenda no encontrada\n";
}

echo "\n✅ TEST COMPLETADO\n";
