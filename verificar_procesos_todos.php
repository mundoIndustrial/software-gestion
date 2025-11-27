<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== VERIFICACIÓN DE TODOS LOS PROCESOS ===\n\n";

// Ver procesos únicos
$procesosUnicos = DB::table('procesos_prenda')
    ->distinct()
    ->pluck('proceso')
    ->toArray();

echo "Procesos únicos en la tabla:\n";
foreach ($procesosUnicos as $proc) {
    $count = DB::table('procesos_prenda')->where('proceso', $proc)->count();
    echo "  - $proc: $count registros\n";
}

echo "\n=== PRIMEROS 5 PROCESOS ===\n";
$primeros = DB::table('procesos_prenda')
    ->select('numero_pedido', 'proceso', 'encargado', 'fecha_inicio')
    ->limit(5)
    ->get();

foreach ($primeros as $p) {
    echo "\nPedido: {$p->numero_pedido}";
    echo "\nProceso: {$p->proceso}";
    echo "\nEncargado: " . ($p->encargado ?? '[NULL]');
    echo "\nFecha: {$p->fecha_inicio}";
    echo "\n---\n";
}

echo "\n=== INFORMACIÓN GENERAL ===\n";
echo "Total procesos: " . DB::table('procesos_prenda')->count() . "\n";
echo "Total pedidos únicos: " . DB::table('procesos_prenda')->distinct('numero_pedido')->count() . "\n";
echo "Procesos con encargado: " . DB::table('procesos_prenda')->whereNotNull('encargado')->where('encargado', '!=', '')->count() . "\n";
?>
