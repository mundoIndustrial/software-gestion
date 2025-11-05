<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;
use Carbon\Carbon;

// Obtener el mes actual
$mesActual = Carbon::now()->month;
$anioActual = Carbon::now()->year;

echo "=== VERIFICACIÓN DE CANTIDADES - DASHBOARD CORTE ===\n\n";
echo "Mes: $mesActual, Año: $anioActual\n\n";

// Obtener todos los registros del mes actual
$registros = RegistroPisoCorte::whereMonth('fecha', $mesActual)
    ->whereYear('fecha', $anioActual)
    ->with(['hora', 'operario'])
    ->get();

echo "Total de registros en el mes: " . $registros->count() . "\n";
echo "Suma total de cantidades: " . $registros->sum('cantidad') . "\n\n";

// Agrupar por hora
echo "=== CANTIDADES POR HORA ===\n";
$porHora = $registros->groupBy(function($registro) {
    return $registro->hora ? $registro->hora->hora : 'SIN HORA';
});

$totalCantidadHoras = 0;
$totalMetaHoras = 0;

foreach ($porHora as $hora => $grupo) {
    $cantidad = $grupo->sum('cantidad');
    $meta = $grupo->sum('meta');
    $eficiencia = $meta > 0 ? round(($cantidad / $meta) * 100, 1) : 0;
    
    $totalCantidadHoras += $cantidad;
    $totalMetaHoras += $meta;
    
    echo sprintf("%-20s | Cantidad: %6d | Meta: %8.2f | Eficiencia: %6.1f%%\n", 
        $hora, $cantidad, $meta, $eficiencia);
}

echo "\n";
echo sprintf("%-20s | Cantidad: %6d | Meta: %8.2f | Eficiencia: %6.1f%%\n", 
    "TOTAL", 
    $totalCantidadHoras, 
    $totalMetaHoras, 
    $totalMetaHoras > 0 ? round(($totalCantidadHoras / $totalMetaHoras) * 100, 1) : 0
);

echo "\n\n=== CANTIDADES POR OPERARIO ===\n";
$porOperario = $registros->groupBy(function($registro) {
    return $registro->operario ? $registro->operario->name : 'SIN OPERARIO';
});

$totalCantidadOperarios = 0;
$totalMetaOperarios = 0;

foreach ($porOperario as $operario => $grupo) {
    $cantidad = $grupo->sum('cantidad');
    $meta = $grupo->sum('meta');
    $eficiencia = $meta > 0 ? round(($cantidad / $meta) * 100, 1) : 0;
    
    $totalCantidadOperarios += $cantidad;
    $totalMetaOperarios += $meta;
    
    echo sprintf("%-30s | Cantidad: %6d | Meta: %8.2f | Eficiencia: %6.1f%%\n", 
        $operario, $cantidad, $meta, $eficiencia);
}

echo "\n";
echo sprintf("%-30s | Cantidad: %6d | Meta: %8.2f | Eficiencia: %6.1f%%\n", 
    "TOTAL", 
    $totalCantidadOperarios, 
    $totalMetaOperarios, 
    $totalMetaOperarios > 0 ? round(($totalCantidadOperarios / $totalMetaOperarios) * 100, 1) : 0
);

echo "\n\n=== ANÁLISIS DE DUPLICADOS ===\n";
// Verificar si hay registros duplicados
$duplicados = $registros->groupBy(function($registro) {
    return $registro->fecha . '|' . 
           ($registro->hora ? $registro->hora->hora : 'SIN HORA') . '|' . 
           ($registro->operario ? $registro->operario->name : 'SIN OPERARIO') . '|' . 
           $registro->orden_produccion;
})->filter(function($grupo) {
    return $grupo->count() > 1;
});

if ($duplicados->count() > 0) {
    echo "⚠️ Se encontraron " . $duplicados->count() . " grupos de registros potencialmente duplicados:\n\n";
    foreach ($duplicados as $key => $grupo) {
        echo "Grupo: $key\n";
        echo "  Cantidad de registros: " . $grupo->count() . "\n";
        echo "  IDs: " . $grupo->pluck('id')->implode(', ') . "\n";
        echo "  Suma de cantidades: " . $grupo->sum('cantidad') . "\n\n";
    }
} else {
    echo "✅ No se encontraron registros duplicados\n";
}

echo "\n=== COMPARACIÓN CON EXCEL ===\n";
echo "Según el Excel (Imagen 1):\n";
echo "  Total: 14,201\n\n";
echo "Según el Dashboard (Imagen 2):\n";
echo "  Total: 14,502\n\n";
echo "Diferencia: " . (14502 - 14201) . " unidades\n\n";

echo "Total en Base de Datos: $totalCantidadHoras\n";
echo "Diferencia con Excel: " . ($totalCantidadHoras - 14201) . " unidades\n";
echo "Diferencia con Dashboard: " . ($totalCantidadHoras - 14502) . " unidades\n";
