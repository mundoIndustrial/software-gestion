<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;
use Carbon\Carbon;

echo "=== ANÁLISIS COMPLETO DE DATOS DE CORTE ===\n\n";

// Obtener todos los registros
$todosRegistros = RegistroPisoCorte::with(['hora', 'operario'])->get();

echo "Total de registros en la base de datos: " . $todosRegistros->count() . "\n";
echo "Suma total de todas las cantidades: " . $todosRegistros->sum('cantidad') . "\n\n";

// Agrupar por fecha
echo "=== REGISTROS POR FECHA ===\n";
$porFecha = $todosRegistros->groupBy(function($registro) {
    return $registro->fecha->format('Y-m-d');
})->sortKeys();

foreach ($porFecha as $fecha => $grupo) {
    $cantidad = $grupo->sum('cantidad');
    $meta = $grupo->sum('meta');
    echo sprintf("%-12s | Registros: %4d | Cantidad: %6d | Meta: %8.2f\n", 
        $fecha, $grupo->count(), $cantidad, $meta);
}

echo "\n=== REGISTROS POR MES ===\n";
$porMes = $todosRegistros->groupBy(function($registro) {
    return $registro->fecha->format('Y-m');
})->sortKeys();

foreach ($porMes as $mes => $grupo) {
    $cantidad = $grupo->sum('cantidad');
    $meta = $grupo->sum('meta');
    echo sprintf("%-10s | Registros: %4d | Cantidad: %6d | Meta: %8.2f\n", 
        $mes, $grupo->count(), $cantidad, $meta);
}

// Verificar si hay algún mes con ~14,000 unidades
echo "\n=== BUSCANDO MES CON ~14,000 UNIDADES ===\n";
foreach ($porMes as $mes => $grupo) {
    $cantidad = $grupo->sum('cantidad');
    if ($cantidad >= 13000 && $cantidad <= 15000) {
        echo "✅ ENCONTRADO: $mes tiene $cantidad unidades\n";
        
        // Mostrar detalle por hora para este mes
        echo "\n--- Detalle por hora para $mes ---\n";
        $porHora = $grupo->groupBy(function($registro) {
            return $registro->hora ? $registro->hora->hora : 'SIN HORA';
        });
        
        foreach ($porHora as $hora => $horaGrupo) {
            $cantidadHora = $horaGrupo->sum('cantidad');
            $metaHora = $horaGrupo->sum('meta');
            $eficiencia = $metaHora > 0 ? round(($cantidadHora / $metaHora) * 100, 1) : 0;
            echo sprintf("  %-15s | Cantidad: %6d | Meta: %8.2f | Eficiencia: %6.1f%%\n", 
                $hora, $cantidadHora, $metaHora, $eficiencia);
        }
    }
}

// Verificar el rango de fechas
echo "\n=== RANGO DE FECHAS ===\n";
$fechaMin = $todosRegistros->min('fecha');
$fechaMax = $todosRegistros->max('fecha');
echo "Fecha mínima: " . ($fechaMin ? $fechaMin->format('Y-m-d') : 'N/A') . "\n";
echo "Fecha máxima: " . ($fechaMax ? $fechaMax->format('Y-m-d') : 'N/A') . "\n";

// Verificar si hay registros con cantidad muy alta (posibles errores)
echo "\n=== REGISTROS CON CANTIDAD ALTA (>1000) ===\n";
$registrosAltos = $todosRegistros->filter(function($registro) {
    return $registro->cantidad > 1000;
});

if ($registrosAltos->count() > 0) {
    echo "Se encontraron " . $registrosAltos->count() . " registros con cantidad > 1000:\n";
    foreach ($registrosAltos as $registro) {
        echo sprintf("  ID: %5d | Fecha: %s | Cantidad: %6d | Operario: %s\n",
            $registro->id,
            $registro->fecha->format('Y-m-d'),
            $registro->cantidad,
            $registro->operario ? $registro->operario->name : 'N/A'
        );
    }
} else {
    echo "No hay registros con cantidad > 1000\n";
}

// Verificar registros con cantidad = 0
echo "\n=== REGISTROS CON CANTIDAD = 0 ===\n";
$registrosCero = $todosRegistros->filter(function($registro) {
    return $registro->cantidad == 0;
});
echo "Registros con cantidad = 0: " . $registrosCero->count() . "\n";
