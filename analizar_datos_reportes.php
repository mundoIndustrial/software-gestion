<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== ANÁLISIS DE DATOS EN COTIZACIONES Y PEDIDOS ===\n\n";

// Fechas de cotizaciones
echo "📅 COTIZACIONES POR MES:\n";
$cotizacionesPorMes = DB::table('cotizaciones')
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
    ->groupBy('mes')
    ->orderBy('mes', 'desc')
    ->get();

foreach ($cotizacionesPorMes as $row) {
    echo "  • $row->mes: $row->total cotizaciones\n";
}

// Fechas de pedidos
echo "\n📅 PEDIDOS POR MES:\n";
$pedidosPorMes = DB::table('pedidos_produccion')
    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
    ->groupBy('mes')
    ->orderBy('mes', 'desc')
    ->get();

foreach ($pedidosPorMes as $row) {
    echo "  • $row->mes: $row->total pedidos\n";
}

// Cotizaciones por asesor en diciembre
echo "\n👤 COTIZACIONES POR ASESOR (Diciembre 2025):\n";
$cotizacionesPorAsesor = DB::table('cotizaciones')
    ->leftJoin('users', 'cotizaciones.asesor_id', '=', 'users.id')
    ->whereYear('cotizaciones.created_at', 2025)
    ->whereMonth('cotizaciones.created_at', 12)
    ->selectRaw('users.name, COUNT(*) as total')
    ->groupBy('users.id', 'users.name')
    ->orderByDesc('total')
    ->get();

if ($cotizacionesPorAsesor->isEmpty()) {
    echo "  ❌ No hay cotizaciones en diciembre 2025\n";
} else {
    foreach ($cotizacionesPorAsesor as $row) {
        echo "  • $row->name: $row->total\n";
    }
}

// Pedidos por asesor en diciembre
echo "\n👤 PEDIDOS POR ASESOR (Diciembre 2025):\n";
$pedidosPorAsesor = DB::table('pedidos_produccion')
    ->leftJoin('users', 'pedidos_produccion.asesor_id', '=', 'users.id')
    ->whereYear('pedidos_produccion.created_at', 2025)
    ->whereMonth('pedidos_produccion.created_at', 12)
    ->selectRaw('users.name, COUNT(*) as total')
    ->groupBy('users.id', 'users.name')
    ->orderByDesc('total')
    ->get();

if ($pedidosPorAsesor->isEmpty()) {
    echo "  ❌ No hay pedidos en diciembre 2025\n";
} else {
    foreach ($pedidosPorAsesor as $row) {
        echo "  • $row->name: $row->total\n";
    }
}

// Fechas extremas
echo "\n📊 RANGO DE FECHAS:\n";
$cotMin = DB::table('cotizaciones')->min('created_at');
$cotMax = DB::table('cotizaciones')->max('created_at');
echo "  Cotizaciones: $cotMin a $cotMax\n";

$pedMin = DB::table('pedidos_produccion')->min('created_at');
$pedMax = DB::table('pedidos_produccion')->max('created_at');
echo "  Pedidos: $pedMin a $pedMax\n";

echo "\n✅ Análisis completado\n";
