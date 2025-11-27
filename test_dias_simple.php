<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIAGNÓSTICO RÁPIDO DE DÍAS ===\n\n";

$pedidos = \App\Models\PedidoProduccion::limit(5)->get();

foreach ($pedidos as $p) {
    $procesos = \Illuminate\Support\Facades\DB::table('procesos_prenda')
        ->where('numero_pedido', $p->numero_pedido)
        ->orderBy('fecha_inicio', 'ASC')
        ->get();
    
    $dias = \App\Services\CacheCalculosService::getTotalDias($p->numero_pedido, $p->estado);
    
    echo "Pedido {$p->numero_pedido}:\n";
    echo "  • Procesos: {$procesos->count()}\n";
    echo "  • Días calculados: {$dias}\n";
    
    if ($procesos->count() > 0) {
        echo "  • Primera fecha: " . $procesos->first()->fecha_inicio . "\n";
        echo "  • Última fecha: " . $procesos->last()->fecha_inicio . "\n";
    }
    echo "\n";
}
