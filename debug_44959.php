<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== ANÁLISIS DETALLADO DEL PEDIDO 44959 ===\n\n";

$pedido = PedidoProduccion::where('numero_pedido', 44959)->first();

if (!$pedido) {
    echo "❌ Pedido no encontrado\n";
    exit;
}

echo "Pedido: {$pedido->numero_pedido}\n";
echo "Estado: {$pedido->estado}\n";
echo "Fecha creación: {$pedido->fecha_de_creacion_de_orden}\n\n";

// Ver todos los procesos
$procesos = DB::table('procesos_prenda')
    ->where('numero_pedido', 44959)
    ->orderBy('fecha_inicio', 'ASC')
    ->select('proceso', 'fecha_inicio', 'fecha_fin')
    ->get();

echo "PROCESOS:\n";
foreach ($procesos as $p) {
    echo "  • {$p->proceso}: {$p->fecha_inicio} → {$p->fecha_fin}\n";
}

// Cálculo ANTIGUO (Creación → Despacho)
$procesoCreacion = DB::table('procesos_prenda')
    ->where('numero_pedido', 44959)
    ->where('proceso', 'Creación de Orden')
    ->select('fecha_inicio')
    ->first();

$procesoDespacho = DB::table('procesos_prenda')
    ->where('numero_pedido', 44959)
    ->where('proceso', 'Despacho')
    ->select('fecha_fin')
    ->first();

echo "\nCÁLCULO ANTIGUO (Creación → Despacho):\n";
if ($procesoCreacion && $procesoDespacho) {
    echo "  Desde: {$procesoCreacion->fecha_inicio}\n";
    echo "  Hasta: {$procesoDespacho->fecha_fin}\n";
    
    $festivosArray = Festivo::pluck('fecha')->toArray();
    $festivosSet = [];
    foreach ($festivosArray as $f) {
        try {
            $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
        } catch (\Exception $e) {}
    }
    
    $inicio = Carbon::parse($procesoCreacion->fecha_inicio);
    $fin = Carbon::parse($procesoDespacho->fecha_fin);
    
    $current = $inicio->copy()->addDay();
    $totalDays = 0;
    $weekends = 0;
    $holidays = 0;
    
    while ($current <= $fin) {
        $dateString = $current->format('Y-m-d');
        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        $isFestivo = isset($festivosSet[$dateString]);
        
        $totalDays++;
        if ($isWeekend) $weekends++;
        if ($isFestivo) $holidays++;
        
        $current->addDay();
    }
    
    $diasHabiles = $totalDays - $weekends - $holidays;
    echo "  Total días: $totalDays, Fines semana: $weekends, Festivos: $holidays\n";
    echo "  ✅ DÍAS HÁBILES: $diasHabiles\n";
} else {
    echo "  ❌ No tiene procesos 'Creación de Orden' o 'Despacho'\n";
}

// Cálculo NUEVO (Todos los procesos)
echo "\nCÁLCULO NUEVO (Suma de todos los procesos):\n";
$diasNuevo = CacheCalculosService::getTotalDias(44959, $pedido->estado);
echo "  ✅ DÍAS HÁBILES: $diasNuevo\n";

echo "\nDIFERENCIA: " . ($diasNuevo - ($diasHabiles ?? 0)) . " días\n";
