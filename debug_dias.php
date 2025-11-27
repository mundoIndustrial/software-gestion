<?php
/**
 * Script de diagnóstico para cálculo de días
 * Ejecutar: php debug_dias.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n========================================\n";
echo "DIAGNÓSTICO DE CÁLCULO DE DÍAS\n";
echo "========================================\n\n";

// 1. Verificar primeros 5 pedidos
$pedidos = PedidoProduccion::limit(5)->get();
echo "📋 Verificando primeros 5 pedidos:\n\n";

foreach ($pedidos as $pedido) {
    echo "─────────────────────────────────────\n";
    echo "Pedido: {$pedido->numero_pedido}\n";
    echo "Estado: {$pedido->estado}\n";
    echo "Fecha creación: {$pedido->fecha_de_creacion_de_orden}\n";
    
    // Obtener procesos
    $procesos = DB::table('procesos_prenda')
        ->where('numero_pedido', $pedido->numero_pedido)
        ->orderBy('fecha_inicio', 'ASC')
        ->select('proceso', 'fecha_inicio', 'fecha_fin')
        ->get();
    
    echo "\n📊 Procesos encontrados: " . $procesos->count() . "\n";
    
    if ($procesos->count() > 0) {
        foreach ($procesos as $i => $proc) {
            echo "  [{$i}] {$proc->proceso}\n";
            echo "      Inicio: {$proc->fecha_inicio}\n";
            echo "      Fin: {$proc->fecha_fin}\n";
        }
        
        // Calcular días usando el servicio
        $dias = CacheCalculosService::getTotalDias($pedido->numero_pedido, $pedido->estado);
        echo "\n✅ Total de días calculados: {$dias}\n";
        
        // Cálculo manual para verificar
        echo "\n🔍 Verificación manual:\n";
        $festivos = Festivo::pluck('fecha')->toArray();
        $festivosSet = [];
        foreach ($festivos as $f) {
            try {
                $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
            } catch (\Exception $e) {}
        }
        
        $procesosFechas = $procesos->map(fn($p) => Carbon::parse($p->fecha_inicio))->toArray();
        $totalDiasManual = 0;
        
        foreach ($procesosFechas as $idx => $fechaInicio) {
            $fechaFin = isset($procesosFechas[$idx + 1]) ? $procesosFechas[$idx + 1] : Carbon::now();
            $diasSegmento = calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
            echo "  Proceso {$idx}: {$fechaInicio->format('Y-m-d')} → {$fechaFin->format('Y-m-d')} = {$diasSegmento} días\n";
            $totalDiasManual += $diasSegmento;
        }
        
        echo "  TOTAL MANUAL: {$totalDiasManual} días\n";
        
        if ($dias === $totalDiasManual) {
            echo "  ✅ Cálculos coinciden\n";
        } else {
            echo "  ❌ MISMATCH: Servicio={$dias}, Manual={$totalDiasManual}\n";
        }
    } else {
        echo "⚠️  NO HAY PROCESOS PARA ESTE PEDIDO\n";
    }
    
    echo "\n";
}

echo "\n========================================\n";
echo "Festivos registrados: " . Festivo::count() . "\n";
echo "Total pedidos: " . PedidoProduccion::count() . "\n";
echo "Total procesos: " . DB::table('procesos_prenda')->count() . "\n";
echo "========================================\n\n";

/**
 * Función auxiliar para calcular días hábiles
 */
function calcularDiasHabiles(Carbon\Carbon $inicio, Carbon\Carbon $fin, $festivosSet): int
{
    $current = $inicio->copy()->addDay();
    $totalDays = 0;
    $weekends = 0;
    $holidays = 0;
    
    $maxIterations = 3650;
    $iterations = 0;
    
    while ($current <= $fin && $iterations < $maxIterations) {
        $dateString = $current->format('Y-m-d');
        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        $isFestivo = isset($festivosSet[$dateString]);
        
        $totalDays++;
        if ($isWeekend) $weekends++;
        if ($isFestivo) $holidays++;
        
        $current->addDay();
        $iterations++;
    }
    
    return $totalDays - $weekends - $holidays;
}
?>
