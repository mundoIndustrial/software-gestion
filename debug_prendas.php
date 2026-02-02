<?php
// Debug prendas por pedido

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;

// Obtener todos los pedidos
$pedidos = PedidoProduccion::all();

echo "Total de pedidos: " . $pedidos->count() . "\n";
echo str_repeat("=", 80) . "\n";

$sinPrendas = [];
$conPrendas = [];

foreach ($pedidos as $pedido) {
    $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->count();
    
    echo "Pedido ID: {$pedido->id} | Número: {$pedido->numero_pedido} | Prendas: {$prendas}\n";
    
    if ($prendas == 0) {
        $sinPrendas[] = $pedido->id;
    } else {
        $conPrendas[] = $pedido->id;
    }
}

echo str_repeat("=", 80) . "\n";
echo "Pedidos SIN prendas (" . count($sinPrendas) . "): " . implode(", ", $sinPrendas) . "\n";
echo "Pedidos CON prendas (" . count($conPrendas) . "): " . implode(", ", $conPrendas) . "\n";
