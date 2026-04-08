<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RECIBOS QUE VERÁ revisor_entregas ===\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->whereIn(DB::raw('LOWER(TRIM(area))'), ['costura', 'control de calidad'])
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->select('id', 'consecutivo_actual', 'estado', DB::raw('LOWER(TRIM(area)) as area'))
    ->orderBy('consecutivo_actual', 'desc')
    ->get();

echo "Total: " . count($recibos) . " recibos\n\n";

foreach ($recibos as $r) {
    echo "ID: {$r->id} | Recibo: {$r->consecutivo_actual} | Estado: {$r->estado} | Área: {$r->area}\n";
}
