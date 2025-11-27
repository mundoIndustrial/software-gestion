<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Simular lo que hace el controller - Método 1: usando Eloquent
$uniqueValues1 = DB::table('prendas_pedido')
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->distinct()
    ->pluck('descripcion')
    ->sort()
    ->values();

echo "=== MÉTODO 1 (Eloquent distinct) ===\n";
echo "Total valores: " . count($uniqueValues1) . "\n";

// Método 2: usando selectRaw
$uniqueValues2 = DB::table('prendas_pedido')
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->selectRaw('DISTINCT descripcion')
    ->pluck('descripcion')
    ->sort()
    ->values();

echo "\n=== MÉTODO 2 (selectRaw) ===\n";
echo "Total valores: " . count($uniqueValues2) . "\n";

// Método 3: usando SQL raw select
$uniqueValues3 = DB::table('prendas_pedido')
    ->selectRaw('DISTINCT descripcion')
    ->whereNotNull('descripcion')
    ->where('descripcion', '!=', '')
    ->orderBy('descripcion')
    ->pluck('descripcion')
    ->values();

echo "\n=== MÉTODO 3 (SQL raw con ordenamiento) ===\n";
echo "Total valores: " . count($uniqueValues3) . "\n";

// Mostrar los con "napole"
$napoles = $uniqueValues3->filter(function($v) {
    return stripos($v, 'napole') !== false;
});

echo "\nValores con 'napole': " . count($napoles) . "\n";
foreach ($napoles as $val) {
    echo "- " . substr($val, 0, 80) . "\n";
}
