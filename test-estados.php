<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener estados únicos
$estados = DB::table('pedidos_produccion')
    ->distinct()
    ->pluck('estado')
    ->toArray();

echo "Estados encontrados en pedidos_produccion:\n";
foreach ($estados as $estado) {
    echo "  - " . ($estado ?: 'NULL') . "\n";
}

// Obtener recuento de cada estado
$conteos = DB::table('pedidos_produccion')
    ->groupBy('estado')
    ->selectRaw('estado, COUNT(*) as count')
    ->get();

echo "\nConteo por estado:\n";
foreach ($conteos as $item) {
    echo "  - " . ($item->estado ?: 'NULL') . ": " . $item->count . "\n";
}
