<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COLUMNAS DE consecutivos_recibos_pedidos ===\n";
$columns = DB::select('DESCRIBE consecutivos_recibos_pedidos');
foreach ($columns as $col) {
    echo "{$col->Field} | {$col->Type} | Default: {$col->Default}\n";
}

echo "\n=== RECIBOS ACTUALES ===\n";
$recibos = DB::table('consecutivos_recibos_pedidos')->get();
foreach ($recibos as $r) {
    $estado = property_exists($r, 'estado') ? $r->estado : 'N/A';
    $area = property_exists($r, 'area') ? $r->area : 'N/A';
    echo "ID:{$r->id} | Consec:{$r->consecutivo_actual} | Estado:{$estado} | Area:{$area}\n";
}
