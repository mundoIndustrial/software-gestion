<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

// Test del cálculo de días hábiles
// Lógica: El contador inicia desde el PRIMER DÍA HÁBIL DESPUÉS de la fecha de creación

function calcularDiasHabilesBatch(Carbon $inicio, Carbon $fin): int {
    // Avanzar al día siguiente
    $current = $inicio->copy()->addDay();
    
    // Contar solo desde el primer día hábil después
    $totalDays = 0;
    $weekends = 0;
    
    while ($current <= $fin) {
        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
        
        $totalDays++;
        if ($isWeekend) $weekends++;
        
        $current->addDay();
    }

    $businessDays = $totalDays - $weekends;

    return max(0, $businessDays);
}

// Test 1: Orden creada 22/11/2025 (sábado) → Entregada 26/11/2025 (miércoles)
// Contador inicia desde el lunes 24/11 (primer día hábil después)
$inicio = Carbon::createFromFormat('Y-m-d', '2025-11-22');
$fin = Carbon::createFromFormat('Y-m-d', '2025-11-26');

$businessDays = calcularDiasHabilesBatch($inicio, $fin);

echo "Test 1: Orden creada 22/11/2025 (Sábado) → Entregada 26/11/2025 (Miércoles)\n";
echo "═════════════════════════════════════════════════════════════════════════════\n";
echo "Contador inicia DESPUÉS de la creación (desde el primer día hábil después):\n\n";

echo "22/11 (Sábado)   - NO CUENTA (es la fecha de creación, fin de semana además)\n";
echo "23/11 (Domingo)  - NO CUENTA (fin de semana)\n";
echo "24/11 (Lunes)    - CUENTA como día 1 (primer día hábil después de creación)\n";
echo "25/11 (Martes)   - CUENTA como día 2\n";
echo "26/11 (Miércoles)- CUENTA como día 3\n";
echo "\n";

echo "Resultado esperado: 3 días\n";
echo "Resultado actual: $businessDays días\n";
echo "Estado: " . ($businessDays === 3 ? "✓ CORRECTO" : "✗ INCORRECTO") . "\n";

// Test 2: Orden creada 24/11/2025 (lunes) → Entregada 26/11/2025 (miércoles)
// Contador inicia desde el martes 25/11
echo "\n\nTest 2: Orden creada 24/11/2025 (Lunes) → Entregada 26/11/2025 (Miércoles)\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$inicio2 = Carbon::createFromFormat('Y-m-d', '2025-11-24');
$fin2 = Carbon::createFromFormat('Y-m-d', '2025-11-26');

$businessDays2 = calcularDiasHabilesBatch($inicio2, $fin2);

echo "Contador inicia DESPUÉS de la creación (desde el primer día hábil después):\n\n";
echo "24/11 (Lunes)    - NO CUENTA (es la fecha de creación)\n";
echo "25/11 (Martes)   - CUENTA como día 1 (primer día hábil después de creación)\n";
echo "26/11 (Miércoles)- CUENTA como día 2\n";
echo "\n";

echo "Resultado esperado: 2 días\n";
echo "Resultado actual: $businessDays2 días\n";
echo "Estado: " . ($businessDays2 === 2 ? "✓ CORRECTO" : "✗ INCORRECTO") . "\n";
?>

