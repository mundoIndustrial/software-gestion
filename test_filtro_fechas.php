<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n=== TEST DE FILTRO DE FECHAS ===\n\n";

// Simular el filtro de este mes
$now = now();
$startOfMonth = $now->clone()->startOfMonth();
$endOfMonth = $now->clone()->endOfMonth();

echo "Hoy: $now\n";
echo "Inicio de mes: $startOfMonth\n";
echo "Fin de mes: $endOfMonth\n\n";

// Contar cotizaciones en este mes
$cotizacionesEseMes = DB::table('cotizaciones')
    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->count();

echo "Cotizaciones en este mes (Dec 2025): $cotizacionesEseMes\n";

// Contar por asesor
$porAsesor = DB::table('cotizaciones')
    ->leftJoin('users', 'cotizaciones.asesor_id', '=', 'users.id')
    ->whereBetween('cotizaciones.created_at', [$startOfMonth, $endOfMonth])
    ->selectRaw('users.name, COUNT(*) as total')
    ->groupBy('users.id', 'users.name')
    ->get();

echo "\nPor asesor:\n";
foreach ($porAsesor as $row) {
    echo "  • " . ($row->name ?: 'SIN ASESOR') . ": $row->total\n";
}

// Pedidos en este mes
$pedidosEseMes = DB::table('pedidos_produccion')
    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->count();

echo "\nPedidos en este mes (Dec 2025): $pedidosEseMes\n";

// Pedidos válidos (con asesor_id)
$pedidosValidos = DB::table('pedidos_produccion')
    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->whereNotNull('asesor_id')
    ->count();

echo "Pedidos con asesor_id válido: $pedidosValidos\n";

// Por asesor
$pedidosPorAsesor = DB::table('pedidos_produccion')
    ->leftJoin('users', 'pedidos_produccion.asesor_id', '=', 'users.id')
    ->whereBetween('pedidos_produccion.created_at', [$startOfMonth, $endOfMonth])
    ->whereNotNull('pedidos_produccion.asesor_id')
    ->selectRaw('users.name, COUNT(*) as total')
    ->groupBy('users.id', 'users.name')
    ->get();

echo "\nPedidos por asesor (válidos):\n";
foreach ($pedidosPorAsesor as $row) {
    echo "  • " . ($row->name ?: 'SIN NOMBRE') . ": $row->total\n";
}

echo "\n✅ Test completado\n";
