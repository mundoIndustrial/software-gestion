<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTADOS EN RECIBOS COSTURA ===\n";
$estados = DB::table('consecutivos_recibos_pedidos')
    ->select('estado')
    ->distinct()
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->pluck('estado');

foreach ($estados as $estado) {
    $count = DB::table('consecutivos_recibos_pedidos')
        ->where('estado', $estado)
        ->where('tipo_recibo', 'COSTURA')
        ->where('activo', 1)
        ->count();
    echo "- $estado ($count recibos)\n";
}

echo "\n=== ÁREAS EN RECIBOS COSTURA ===\n";
$areas = DB::table('consecutivos_recibos_pedidos')
    ->select(DB::raw('LOWER(TRIM(area)) as area'))
    ->distinct()
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->pluck('area');

foreach ($areas as $area) {
    $count = DB::table('consecutivos_recibos_pedidos')
        ->whereRaw('LOWER(TRIM(area)) = ?', [$area])
        ->where('tipo_recibo', 'COSTURA')
        ->where('activo', 1)
        ->count();
    echo "- $area ($count recibos)\n";
}
