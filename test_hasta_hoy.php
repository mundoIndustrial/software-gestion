<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Carbon\Carbon;

// Simular el cálculo
function calcularDiasHabilesBatch(Carbon $inicio, Carbon $fin): int
{
    $current = $inicio->copy()->addDay();
    
    $totalDays = 0;
    $weekends = 0;
    $holidaysCount = 0;
    
    while ($current <= $fin) {
        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        
        $totalDays++;
        if ($isWeekend) $weekends++;
        
        $current->addDay();
    }

    $businessDays = $totalDays - $weekends - $holidaysCount;

    return max(0, $businessDays);
}

// Prueba: Pedido Recibido el 22/11/2025 (sábado), contar hasta hoy (26/11/2025)
$fechaInicio = Carbon::createFromFormat('Y-m-d', '2025-11-22');
$fechaFin = Carbon::now();

$totalDias = calcularDiasHabilesBatch($fechaInicio, $fechaFin);

echo "Test: Pedido Recibido 22/11/2025 (Sábado) → Hoy " . $fechaFin->format('Y-m-d (l)') . "\n";
echo "═════════════════════════════════════════════════════════════════\n";
echo "Contador inicia DESPUÉS de la creación (desde el primer día hábil después):\n\n";

$current = $fechaInicio->copy()->addDay();
$dia = 0;
while ($current <= $fechaFin) {
    $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
    $dayName = $current->format('D');
    
    if (!$isWeekend) {
        $dia++;
        echo "$current->year-$current->month-$current->day ($dayName) - CUENTA como día $dia\n";
    } else {
        echo "$current->year-$current->month-$current->day ($dayName) - fin de semana\n";
    }
    
    $current->addDay();
}

echo "\n";
echo "Resultado esperado: 3 días (aprox)\n";
echo "Resultado actual: $totalDias días\n";
echo "Estado: " . ($totalDias >= 2 && $totalDias <= 4 ? "✓ CORRECTO (rango esperado)" : "⚠ REVISAR") . "\n";
?>
