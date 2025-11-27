<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== VERIFICACIÓN DE CÁLCULO DE DÍAS ===\n\n";

// Obtener festivos
$festivos = DB::table('festivos')->pluck('fecha')->toArray();
echo "Total festivos en sistema: " . count($festivos) . "\n\n";

// Buscar algunos pedidos con Creación de Orden y Despacho
$pedidosConProcesos = DB::table('procesos_prenda as p1')
    ->join('procesos_prenda as p2', 'p1.numero_pedido', '=', 'p2.numero_pedido')
    ->where('p1.proceso', 'Creación de Orden')
    ->where('p2.proceso', 'Despacho')
    ->select('p1.numero_pedido', 'p1.fecha_inicio as fecha_creacion', 'p2.fecha_fin as fecha_despacho')
    ->limit(10)
    ->get();

echo "Ejemplo de cálculo de días:\n";
echo "================================\n\n";

$festivosSet = [];
foreach ($festivos as $f) {
    try {
        $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
    } catch (\Exception $e) {
        // Ignorar
    }
}

foreach ($pedidosConProcesos as $pedido) {
    $fechaInicio = Carbon::parse($pedido->fecha_creacion);
    $fechaFin = Carbon::parse($pedido->fecha_despacho);
    
    $current = $fechaInicio->copy()->addDay();
    $totalDays = 0;
    $weekends = 0;
    $holidaysCount = 0;
    
    $maxIterations = 3650;
    $iterations = 0;
    
    while ($current <= $fechaFin && $iterations < $maxIterations) {
        $dateString = $current->format('Y-m-d');
        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        $isFestivo = isset($festivosSet[$dateString]);
        
        $totalDays++;
        if ($isWeekend) $weekends++;
        if ($isFestivo) $holidaysCount++;
        
        $current->addDay();
        $iterations++;
    }
    
    $businessDays = max(0, $totalDays - $weekends - $holidaysCount);
    
    echo "Pedido: {$pedido->numero_pedido}\n";
    echo "  Creación: " . $fechaInicio->format('d/m/Y l') . "\n";
    echo "  Despacho: " . $fechaFin->format('d/m/Y l') . "\n";
    echo "  Total días: $totalDays\n";
    echo "  Sábados/Domingos: $weekends\n";
    echo "  Festivos: $holidaysCount\n";
    echo "  Días hábiles: $businessDays\n";
    echo "---\n";
}

echo "\n✅ VERIFICACIÓN COMPLETADA\n";
?>
