<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ESTRUCTURA procesos_prenda ===\n";
$cols = Schema::getColumnListing('procesos_prenda');
foreach($cols as $col) echo "  - $col\n";

echo "\n=== DATOS EN procesos_prenda ===\n";
$procesos = DB::table('procesos_prenda')->get();
echo "Total: " . $procesos->count() . " registros\n\n";

if($procesos->count() > 0) {
    $first = $procesos->first();
    echo "Primer registro:\n";
    echo json_encode((array)$first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// Ahora revisar correlación entre tablas
echo "\n\n=== CORRELACIÓN ENTRE TABLAS ===\n";

$pedidos_total = DB::table('pedidos_produccion')->count();
$pedidos_con_prendas = DB::table('pedidos_produccion')
    ->join('prendas_pedido', 'pedidos_produccion.id', '=', 'prendas_pedido.pedido_produccion_id')
    ->distinct('pedidos_produccion.id')
    ->count();

echo "Pedidos totales: $pedidos_total\n";
echo "Pedidos con prendas: $pedidos_con_prendas\n";

// Revisar prendas por pedido
echo "\n📋 Prendas por pedido (primero):\n";
$pedido_1 = DB::table('pedidos_produccion')->first();
$prendas_p1 = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido_1->id)
    ->get();
echo "Pedido {$pedido_1->numero_pedido}: {$prendas_p1->count()} prendas\n";

foreach($prendas_p1 as $p) {
    echo "  - {$p->nombre_prenda} (qty: {$p->cantidad})\n";
}
?>
