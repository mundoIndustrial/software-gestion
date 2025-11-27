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

echo "=== COMPARACIÓN DE CÁLCULOS ===\n\n";

// Función para calcular como ANTES (solo Creación -> Despacho)
function calcularDiasAntesLogica($numeroPedido, $festivos) {
    $procesoCreacion = DB::table('procesos_prenda')
        ->where('numero_pedido', $numeroPedido)
        ->where('proceso', 'Creación de Orden')
        ->select('fecha_inicio')
        ->first();

    $procesoDespacho = DB::table('procesos_prenda')
        ->where('numero_pedido', $numeroPedido)
        ->where('proceso', 'Despacho')
        ->select('fecha_fin')
        ->first();

    if (!$procesoCreacion || !$procesoDespacho) {
        return 0;
    }
    
    $festivosSet = [];
    foreach ($festivos as $f) {
        try {
            $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
        } catch (\Exception $e) {}
    }
    
    $fechaInicio = Carbon::parse($procesoCreacion->fecha_inicio);
    $fechaFin = Carbon::parse($procesoDespacho->fecha_fin);
    
    return calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
}

function calcularDiasHabiles(Carbon $inicio, Carbon $fin, $festivosSet): int {
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
    
    return $totalDays - $weekends - $holidays;
}

// Prueba con primeros 10 pedidos
$pedidos = PedidoProduccion::limit(10)->get();
$festivos = Festivo::pluck('fecha')->toArray();

echo "Pedido\t| ANTES (Creoción->Despacho)\t| AHORA (Todos procesos)\t| DIFERENCIA\n";
echo str_repeat("-", 80) . "\n";

foreach ($pedidos as $p) {
    $diasAntes = calcularDiasAntesLogica($p->numero_pedido, $festivos);
    $diasAhora = CacheCalculosService::getTotalDias($p->numero_pedido, $p->estado);
    $diff = $diasAhora - $diasAntes;
    
    echo "{$p->numero_pedido}\t| {$diasAntes}\t\t\t| {$diasAhora}\t\t| " . ($diff >= 0 ? "+$diff" : "$diff") . "\n";
}
