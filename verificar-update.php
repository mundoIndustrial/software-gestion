<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver todos los estados
$estados = DB::table('pedidos_produccion')
    ->groupBy('estado')
    ->selectRaw('estado, COUNT(*) as count')
    ->get();

echo "Estados en BD:\n";
foreach ($estados as $e) {
    echo "  - " . ($e->estado ?: 'NULL') . ": " . $e->count . "\n";
}

echo "\nActualizando PENDIENTE_SUPERVISOR a pendiente_cartera...\n";
$updated = DB::table('pedidos_produccion')
    ->where('estado', 'PENDIENTE_SUPERVISOR')
    ->update(['estado' => 'pendiente_cartera']);

echo "Actualizados: " . $updated . "\n";

echo "\nVerificando después de actualizar:\n";
$estados2 = DB::table('pedidos_produccion')
    ->groupBy('estado')
    ->selectRaw('estado, COUNT(*) as count')
    ->get();

foreach ($estados2 as $e) {
    echo "  - " . ($e->estado ?: 'NULL') . ": " . $e->count . "\n";
}
