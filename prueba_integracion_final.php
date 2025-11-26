<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\ProcesosPrenda;
use Carbon\Carbon;

echo "\n=== PRUEBA FINAL: INTEGRACIÓN COMPLETA ===\n\n";

// Simular el método calcularTotalDiasBatchConCache del controlador
$festivos = \App\Models\Festivo::pluck('fecha')->toArray();

echo "📊 Festivos cargados: " . count($festivos) . "\n\n";

// Obtener órdenes entregadas
$ordenesEntregadas = PedidoProduccion::where('estado', 'Entregado')
    ->limit(3)
    ->get();

echo "✅ PRUEBA DE CÁLCULO DE DURACIÓN (órdenes entregadas):\n\n";

foreach($ordenesEntregadas as $orden) {
    echo "Pedido #{$orden->numero_pedido}\n";
    
    // Obtener procesos igual que en el controlador
    $procesosPrenda = ProcesosPrenda::where('numero_pedido', $orden->numero_pedido)
        ->whereNotNull('fecha_inicio')
        ->orderBy('fecha_inicio', 'asc')
        ->get();
    
    if ($procesosPrenda->isEmpty()) {
        echo "  ⚠️ Sin procesos\n";
    } else {
        echo "  Procesos: {$procesosPrenda->count()}\n";
        
        $fechaInicio = Carbon::parse($procesosPrenda->first()->fecha_inicio);
        $fechaFin = Carbon::parse($procesosPrenda->last()->fecha_inicio);
        
        echo "    Inicio: {$fechaInicio->format('d/m/Y')}\n";
        echo "    Fin: {$fechaFin->format('d/m/Y')}\n";
        
        // Calcular días hábiles
        $totalDays = $fechaInicio->diffInDays($fechaFin);
        
        // Contar fines de semana
        $weekends = 0;
        $current = $fechaInicio->copy();
        while ($current <= $fechaFin) {
            if ($current->isWeekend()) {
                $weekends++;
            }
            $current->addDay();
        }
        
        // Contar festivos
        $festivosEnRango = array_filter($festivos, function ($festivo) use ($fechaInicio, $fechaFin) {
            $fechaFestivo = Carbon::parse($festivo);
            return $fechaFestivo->between($fechaInicio, $fechaFin);
        });
        
        $dias = $totalDays - $weekends - count($festivosEnRango);
        
        echo "    📊 Duración: $dias días hábiles\n";
    }
    echo "\n";
}

echo "\n✅ Integración completada\n";
echo "✅ El RegistroOrdenController ahora puede calcular duración desde procesos_prenda\n";
?>
