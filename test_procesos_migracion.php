<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 Verificando procesos migrados...\n\n";

// Contar procesos por tipo
$procesos = DB::table('procesos_prenda')
    ->groupBy('proceso')
    ->select('proceso', DB::raw('count(*) as cantidad'))
    ->orderBy('cantidad', 'desc')
    ->get();

echo "Procesos migrados:\n";
foreach ($procesos as $p) {
    echo "   • " . $p->proceso . ": " . $p->cantidad . "\n";
}

echo "\n\nProcesos esperados del enum:\n";
$procesosEsperados = [
    'Creación Orden',
    'Inventario',
    'Insumos y Telas',
    'Corte',
    'Bordado',
    'Estampado',
    'Costura',
    'Reflectivo',
    'Lavandería',
    'Arreglos',
    'Control Calidad',
    'Entrega',
    'Despacho'
];

$procesosActuales = $procesos->pluck('proceso')->toArray();

foreach ($procesosEsperados as $p) {
    $existe = in_array($p, $procesosActuales);
    echo "   " . ($existe ? "✅" : "❌") . " " . $p . "\n";
}

echo "\n\n📍 Órdenes entregadas sin procesos de Despacho:\n";

// Buscar órdenes entregadas
$ordenesEntregadas = DB::table('pedidos_produccion')
    ->where('estado', 'Entregado')
    ->limit(5)
    ->get();

echo "Total órdenes entregadas: " . DB::table('pedidos_produccion')->where('estado', 'Entregado')->count() . "\n\n";

foreach ($ordenesEntregadas as $orden) {
    $procesosDespacho = DB::table('procesos_prenda')
        ->where('pedidos_produccion_id', $orden->id)
        ->where('proceso', 'Despacho')
        ->count();
    
    echo "   Orden " . $orden->numero_pedido . " (ID: " . $orden->id . "): " . 
         ($procesosDespacho > 0 ? "✅ Tiene Despacho" : "❌ NO tiene Despacho") . "\n";
}

echo "\n✅ Completado\n";
