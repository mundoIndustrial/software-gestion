<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;

echo "\n=== PRUEBA DE CÁLCULO DE DÍAS HÁBILES ===\n\n";

// Fechas del pedido 45395
$fechaInicio = Carbon::parse('2025-11-22'); // Sábado
$fechaFin = Carbon::parse('2025-11-26');    // Miércoles (hoy)

echo "Período: {$fechaInicio->format('d/m/Y')} ({$fechaInicio->format('l')}) a {$fechaFin->format('d/m/Y')} ({$fechaFin->format('l')})\n\n";

// Detallar cada día
echo "Detalles de días:\n";
$current = $fechaInicio->copy();
$diasHabiles = 0;
while($current <= $fechaFin) {
    $esHabil = !$current->isWeekend();
    if($esHabil) {
        $diasHabiles++;
    }
    echo "  {$current->format('d/m/Y')} ({$current->format('l')}): " . ($esHabil ? "✅ HÁBIL (Día $diasHabiles)" : "❌ WEEKEND") . "\n";
    $current->addDay();
}

echo "\n📊 Resultado:\n";
echo "  Días hábiles totales: $diasHabiles\n";

// Simulación del método del controlador
echo "\n\n=== SIMULACIÓN DEL MÉTODO calcularDiasHabilesBatch ===\n";

$totalDays = $fechaInicio->diffInDays($fechaFin) + 1;
echo "Total de días (incluyendo inicio y fin): $totalDays\n";

// Contar weekends
$weekends = 0;
$current = $fechaInicio->copy();
while($current <= $fechaFin) {
    if($current->isWeekend()) {
        $weekends++;
    }
    $current->addDay();
}
echo "Fines de semana: $weekends\n";

$businessDays = $totalDays - $weekends;
echo "Días hábiles (sin festivos): $businessDays\n";

echo "\n✅ El cálculo debería dar 3 días hábiles\n";
?>
