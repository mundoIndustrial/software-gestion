<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

echo "\n=== PRUEBA DEL ENDPOINT /api/ordenes/{id}/procesos ===\n\n";

// Obtener un pedido entregado para prueba
$pedido = PedidoProduccion::where('estado', 'Entregado')->first();

if(!$pedido) {
    echo "No hay órdenes entregadas\n";
    exit;
}

echo "Pedido a probar: #{$pedido->numero_pedido} ({$pedido->id})\n\n";

// Simular lo que hace el controlador
$procesos = DB::table('procesos_prenda')
    ->where('numero_pedido', $pedido->numero_pedido)
    ->orderBy('fecha_inicio', 'asc')
    ->select('proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
    ->get();

echo "Procesos encontrados: {$procesos->count()}\n";

if($procesos->count() > 0) {
    echo "\nPrimeros 5 procesos:\n";
    foreach($procesos->take(5) as $p) {
        echo "  - {$p->proceso}: {$p->fecha_inicio} (Estado: {$p->estado_proceso})\n";
    }
    
    echo "\n✅ El endpoint debería funcionar correctamente\n";
} else {
    echo "⚠️ Sin procesos encontrados para este pedido\n";
}

echo "\nJSON que retornaría el endpoint:\n";
$response = [
    'numero_pedido' => $pedido->numero_pedido,
    'cliente' => $pedido->cliente,
    'fecha_inicio' => $pedido->fecha_de_creacion_de_orden,
    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
    'procesos' => $procesos->groupBy('proceso')->map(function($grupo) {
        return $grupo->first();
    })->values()
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
?>
