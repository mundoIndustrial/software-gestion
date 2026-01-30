<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;

echo "Estados en pedidos_produccion:\n";
echo "==========================\n";

$estados = PedidoProduccion::select('estado')->distinct()->get();

foreach ($estados as $pedido) {
    echo "- " . ($pedido->estado ?? 'NULL') . "\n";
}

echo "\nTotal de pedidos por estado:\n";
echo "============================\n";

$conteos = PedidoProduccion::selectRaw('estado, COUNT(*) as total')
    ->groupBy('estado')
    ->orderBy('estado')
    ->get();

foreach ($conteos as $conteo) {
    $estado = $conteo->estado ?? 'NULL';
    $total = $conteo->total;
    echo "- $estado: $total pedidos\n";
}

echo "\nPedidos con estado PENDIENTE_SUPERVISOR:\n";
echo "========================================\n";

$pendientes = PedidoProduccion::where('estado', 'PENDIENTE_SUPERVISOR')
    ->select('id', 'numero_pedido', 'estado', 'cliente')
    ->limit(5)
    ->get();

if ($pendientes->count() > 0) {
    foreach ($pendientes as $pedido) {
        echo "- Pedido #{$pedido->numero_pedido} (ID: {$pedido->id}) - {$pedido->cliente} - Estado: {$pedido->estado}\n";
    }
} else {
    echo "No hay pedidos con estado PENDIENTE_SUPERVISOR\n";
}

echo "\nVerificación de 10 pedidos aleatorios:\n";
echo "====================================\n";

$pedidos = PedidoProduccion::inRandomOrder()
    ->select('id', 'numero_pedido', 'estado', 'cliente')
    ->limit(10)
    ->get();

foreach ($pedidos as $pedido) {
    $tieneBoton = $pedido->estado === 'PENDIENTE_SUPERVISOR' ? 'SÍ' : 'NO';
    echo "- Pedido #{$pedido->numero_pedido} - {$pedido->cliente} - Estado: '{$pedido->estado}' - Botón Aprobar: $tieneBoton\n";
}
