<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cotizacion = App\Models\Cotizacion::find(6);

if ($cotizacion) {
    echo "📋 Cotización ID: {$cotizacion->id}\n";
    echo "👤 Cliente: {$cotizacion->cliente}\n\n";
    
    $prendas = $cotizacion->prendas;
    echo "📦 Prendas: {$prendas->count()}\n\n";
    
    foreach ($prendas as $idx => $prenda) {
        echo "📦 Prenda {$idx}: {$prenda->nombre_producto} (ID: {$prenda->id})\n";
        
        // Fotos de telas
        if ($prenda->telaFotos) {
            echo "🧵 Fotos de telas: {$prenda->telaFotos->count()}\n";
            foreach ($prenda->telaFotos as $foto) {
                echo "   📸 Foto ID {$foto->id}:\n";
                echo "      - tela_index: {$foto->tela_index}\n";
                echo "      - ruta: {$foto->ruta_original}\n";
            }
        } else {
            echo "⚠️ No hay fotos de telas\n";
        }
        
        echo "\n";
    }
} else {
    echo "❌ Cotización 6 no encontrada\n";
}
