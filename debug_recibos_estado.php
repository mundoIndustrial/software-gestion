<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RECIBOS DE COSTURA ACTIVOS ===\n\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->select('id','pedido_produccion_id','consecutivo_actual','estado','activo')
    ->get();

foreach($recibos as $r) {
    echo "ID:{$r->id} | PedidoID:{$r->pedido_produccion_id} | Consecutivo:{$r->consecutivo_actual} | Estado:{$r->estado} | Activo:{$r->activo}\n";
}

echo "\nTotal: " . $recibos->count() . "\n";

echo "\n=== RECIBOS QUE APARECERÍAN EN /recibos-costura (estado != PENDIENTE_INSUMOS) ===\n\n";
$visibles = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->where('estado', '!=', 'PENDIENTE_INSUMOS')
    ->select('id','pedido_produccion_id','consecutivo_actual','estado')
    ->get();

foreach($visibles as $r) {
    echo "ID:{$r->id} | PedidoID:{$r->pedido_produccion_id} | Consecutivo:{$r->consecutivo_actual} | Estado:{$r->estado}\n";
}
echo "\nTotal visibles: " . $visibles->count() . "\n";
