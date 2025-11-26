<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNÓSTICO DE LA BD ===\n\n";

// Verificar tabla_original
$tablaOrig = DB::table('tabla_original')->count();
echo "📊 tabla_original: $tablaOrig registros\n";

// Verificar registros_por_orden
$regOrd = DB::table('registros_por_orden')->count();
echo "📊 registros_por_orden: $regOrd registros\n";

// Verificar pedidos_produccion
$pedidos = DB::table('pedidos_produccion')->count();
echo "📊 pedidos_produccion: $pedidos registros\n";

// Verificar prendas_pedido
$prendas = DB::table('prendas_pedido')->count();
echo "📊 prendas_pedido: $prendas registros\n";

// Verificar procesos_prenda
$procesos = DB::table('procesos_prenda')->count();
echo "📊 procesos_prenda: $procesos registros\n\n";

// Ver un ejemplo de registros_por_orden
$ejemplo = DB::table('registros_por_orden')->limit(3)->get();
echo "📋 Ejemplos de registros_por_orden:\n";
foreach ($ejemplo as $row) {
    echo json_encode($row) . "\n";
}
