<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📊 ESTADO DE LA MIGRACIÓN:\n";
echo str_repeat("=", 60) . "\n";
echo "Pedidos: " . DB::table('pedidos_produccion')->count() . "\n";
echo "Prendas: " . DB::table('prendas_pedido')->count() . "\n";
echo "Procesos: " . DB::table('procesos_prenda')->count() . "\n";
echo str_repeat("=", 60) . "\n";

// Muestra algunos procesos de ejemplo
$procesos = DB::table('procesos_prenda')
    ->groupBy('proceso')
    ->selectRaw('proceso, COUNT(*) as cantidad')
    ->get();

echo "\nProcesos por tipo:\n";
foreach ($procesos as $p) {
    echo "  - {$p->proceso}: {$p->cantidad}\n";
}
