<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;

// Obtener un pedido con prendas
$pedido = PedidoProduccion::with('prendas')->first();

if ($pedido) {
    echo "=== PEDIDO: " . $pedido->numero_pedido . " ===\n";
    echo "descripcion_prendas (dinámico):\n";
    echo $pedido->descripcion_prendas;
    echo "\n\n";
    
    echo "=== DESGLOSE POR PRENDA ===\n";
    foreach ($pedido->prendas as $index => $prenda) {
        echo "\nPrenda " . ($index + 1) . ":\n";
        echo "  nombre: " . $prenda->nombre_prenda . "\n";
        echo "  cantidad: " . $prenda->cantidad . "\n";
        echo "  formatted_description:\n";
        echo $prenda->formatted_description . "\n";
        echo "  ---\n";
    }
} else {
    echo "No pedidos found\n";
}
