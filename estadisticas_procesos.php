<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ESTADÍSTICAS DE PROCESOS ===\n\n";

$totalPedidos = \App\Models\PedidoProduccion::count();
$pedidosConProcesos = \Illuminate\Support\Facades\DB::table('procesos_prenda')
    ->distinct()
    ->count('numero_pedido');

echo "Total de pedidos: $totalPedidos\n";
echo "Pedidos con procesos: $pedidosConProcesos\n";
echo "Pedidos SIN procesos: " . ($totalPedidos - $pedidosConProcesos) . "\n\n";

// Ver primeros 20 que NO tienen procesos
echo "Primeros 20 pedidos SIN procesos:\n";
$sinProcesos = \App\Models\PedidoProduccion::limit(20)->get()
    ->filter(function($p) {
        return \Illuminate\Support\Facades\DB::table('procesos_prenda')
            ->where('numero_pedido', $p->numero_pedido)
            ->count() === 0;
    });

foreach ($sinProcesos as $p) {
    echo "  • Pedido {$p->numero_pedido} (Estado: {$p->estado})\n";
}

echo "\n\nPedidos CON procesos:\n";
$conProcesos = \App\Models\PedidoProduccion::limit(20)->get()
    ->filter(function($p) {
        return \Illuminate\Support\Facades\DB::table('procesos_prenda')
            ->where('numero_pedido', $p->numero_pedido)
            ->count() > 0;
    });

foreach ($conProcesos as $p) {
    $procesos = \Illuminate\Support\Facades\DB::table('procesos_prenda')
        ->where('numero_pedido', $p->numero_pedido)
        ->count();
    $dias = \App\Services\CacheCalculosService::getTotalDias($p->numero_pedido, $p->estado);
    echo "  • Pedido {$p->numero_pedido}: {$procesos} procesos, {$dias} días\n";
}
