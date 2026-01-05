<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== ASESORAS EN LOGO_PEDIDOS ===\n\n";

$asesoras = DB::table('logo_pedidos')
    ->select('asesora', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('asesora')
    ->get();

foreach ($asesoras as $asesora) {
    echo sprintf("%-30s : %d pedidos\n", $asesora->asesora ?: '(NULL)', $asesora->cantidad);
}

echo "\n";
