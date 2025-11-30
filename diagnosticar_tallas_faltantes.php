<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "═══════════════════════════════════════════════════════════════\n";
echo "DIAGNÓSTICO: Órdenes Viejas Sin Tallas\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Buscar órdenes viejas
$ordenes = PedidoProduccion::with('prendas')
    ->orderBy('created_at', 'asc')
    ->limit(10)
    ->get();

foreach ($ordenes as $orden) {
    echo "📋 Orden: {$orden->numero_pedido} (creada: {$orden->created_at})\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    foreach ($orden->prendas as $index => $prenda) {
        echo "\n  Prenda " . ($index + 1) . ": {$prenda->nombre_prenda}\n";
        echo "  • descripcion: " . (strlen($prenda->descripcion ?? '') > 0 ? "SÍ" : "VACÍA") . "\n";
        echo "  • cantidad_talla: " . ($prenda->cantidad_talla ? "SÍ (" . strlen($prenda->cantidad_talla) . " chars)" : "VACÍA") . "\n";
        
        if ($prenda->cantidad_talla) {
            $tallas = json_decode($prenda->cantidad_talla, true);
            echo "  • Tallas decodificadas: " . json_encode($tallas) . "\n";
        }
        
        echo "  • descripcion_armada: " . (strlen($prenda->descripcion_armada ?? '') > 0 ? "SÍ" : "VACÍA") . "\n";
    }
    
    echo "\n  >>> descripcion_prendas (attribute):\n";
    $desc = $orden->descripcion_prendas;
    $preview = substr($desc, 0, 200);
    echo "  " . str_replace("\n", "\n  ", $preview) . "\n";
    
    echo "════════════════════════════════════════════════════════════════\n\n";
}
