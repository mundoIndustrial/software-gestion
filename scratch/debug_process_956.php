<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$rs = DB::table('pedidos_procesos_prenda_detalles')
    ->where('prenda_pedido_id', 956)
    ->where('tipo_proceso_id', 2)
    ->get();

foreach($rs as $r) {
    echo "ID: {$r->id}, Prenda: {$r->prenda_pedido_id}, Tipo: {$r->tipo_proceso_id}, Recibo: '{$r->numero_recibo}', Estado: {$r->estado}\n";
}
