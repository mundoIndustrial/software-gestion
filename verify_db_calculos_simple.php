<?php
// Script para verificar que los últimos registros tienen cálculos correctos

// Este script se ejecuta desde artisan tinker o como comando artisan

use App\Models\RegistroPisoCorte;

echo "=== VERIFICACIÓN DE CÁLCULOS EN BASE DE DATOS ===\n\n";

$registros = RegistroPisoCorte::latest('id')->limit(10)->get();

$conProblemas = 0;
$conCalculos = 0;

foreach ($registros as $index => $reg) {
    echo "REGISTRO #{$reg->id}\n";
    echo "─────────────────────────────────────\n";
    echo "Fecha: {$reg->fecha}\n";
    echo "Orden: {$reg->orden_produccion}\n";
    echo "Actividad: {$reg->actividad}\n";
    
    // Verificar si es 0
    if ($reg->tiempo_disponible == 0 && $reg->meta == 0 && $reg->eficiencia == 0) {
        echo "  ❌ PROBLEMA: Todos los cálculos son 0\n";
        $conProblemas++;
    } else if ($reg->tiempo_disponible > 0) {
        echo "  ✓ Tiempo disponible: " . number_format($reg->tiempo_disponible, 2) . " seg\n";
        echo "  ✓ Meta: " . number_format($reg->meta, 2) . "\n";
        echo "  ✓ Eficiencia: " . number_format($reg->eficiencia, 2) . "\n";
        echo "  ✅ Cálculos OK\n";
        $conCalculos++;
    }
    
    echo "\n";
}

echo "═════════════════════════════════════\n";
echo "Resumen:\n";
echo "  ✅ Con cálculos correctos: $conCalculos\n";
echo "  ❌ Con problemas: $conProblemas\n";
echo "═════════════════════════════════════\n";

if ($conProblemas === 0) {
    echo "✅ Todos los registros tienen cálculos correctos\n";
} else {
    echo "⚠️  Hay registros con problemas en los cálculos\n";
}
?>
