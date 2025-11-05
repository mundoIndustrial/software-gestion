<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE DUPLICADOS POR MÚLTIPLES TELAS ===\n\n";

// Obtener registros de octubre 2025
$registros = RegistroPisoCorte::whereYear('fecha', 2025)
    ->whereMonth('fecha', 10)
    ->with(['hora', 'operario', 'tela'])
    ->orderBy('fecha')
    ->orderBy('hora_id')
    ->orderBy('operario_id')
    ->get();

echo "Total registros en octubre 2025: " . $registros->count() . "\n";
echo "Suma total de cantidades: " . $registros->sum('cantidad') . "\n\n";

// Agrupar por fecha, hora, operario, orden_produccion
$grupos = $registros->groupBy(function($registro) {
    return sprintf(
        "%s|%s|%s|%s",
        $registro->fecha->format('Y-m-d'),
        $registro->hora ? $registro->hora->hora : 'SIN_HORA',
        $registro->operario ? $registro->operario->name : 'SIN_OPERARIO',
        $registro->orden_produccion
    );
});

echo "=== BUSCANDO REGISTROS DUPLICADOS ===\n\n";

$totalDuplicados = 0;
$cantidadDuplicada = 0;
$gruposDuplicados = 0;

foreach ($grupos as $key => $grupo) {
    if ($grupo->count() > 1) {
        $gruposDuplicados++;
        $parts = explode('|', $key);
        
        echo "Grupo duplicado #$gruposDuplicados:\n";
        echo "  Fecha: {$parts[0]}\n";
        echo "  Hora: {$parts[1]}\n";
        echo "  Operario: {$parts[2]}\n";
        echo "  Orden: {$parts[3]}\n";
        echo "  Cantidad de registros: " . $grupo->count() . "\n";
        
        $cantidades = [];
        $telas = [];
        
        foreach ($grupo as $registro) {
            $cantidades[] = $registro->cantidad;
            $telas[] = $registro->tela ? $registro->tela->nombre_tela : 'NULL';
            echo sprintf("    ID: %5d | Cantidad: %5d | Tela: %s\n",
                $registro->id,
                $registro->cantidad,
                $registro->tela ? $registro->tela->nombre_tela : 'NULL'
            );
        }
        
        // Verificar si todas las cantidades son iguales (indicativo de duplicación)
        $cantidadesUnicas = array_unique($cantidades);
        if (count($cantidadesUnicas) == 1) {
            echo "  ⚠️ TODAS LAS CANTIDADES SON IGUALES: " . $cantidades[0] . "\n";
            echo "  ⚠️ TELAS DIFERENTES: " . implode(', ', $telas) . "\n";
            echo "  ⚠️ CANTIDAD DUPLICADA: " . ($cantidades[0] * ($grupo->count() - 1)) . "\n";
            $cantidadDuplicada += ($cantidades[0] * ($grupo->count() - 1));
        }
        
        echo "  Suma de cantidades: " . $grupo->sum('cantidad') . "\n";
        echo "\n";
        
        $totalDuplicados += $grupo->count() - 1;
    }
}

echo "\n=== RESUMEN ===\n";
echo "Grupos con duplicados: $gruposDuplicados\n";
echo "Total de registros duplicados: $totalDuplicados\n";
echo "Cantidad total duplicada (exceso): $cantidadDuplicada\n\n";

echo "=== COMPARACIÓN ===\n";
echo "Total en BD: " . $registros->sum('cantidad') . "\n";
echo "Total en Excel: 14,201\n";
echo "Diferencia: " . ($registros->sum('cantidad') - 14201) . "\n";
echo "Cantidad duplicada encontrada: $cantidadDuplicada\n";

if (abs(($registros->sum('cantidad') - 14201) - $cantidadDuplicada) < 10) {
    echo "\n✅ LA DIFERENCIA SE EXPLICA POR LOS REGISTROS DUPLICADOS\n";
} else {
    echo "\n⚠️ Hay otras causas además de los duplicados\n";
}

// Verificar si hay registros con la misma fecha, hora, operario, cantidad pero diferente tela
echo "\n\n=== ANÁLISIS DETALLADO DE DUPLICACIÓN ===\n";

$duplicadosPorTela = DB::table('registro_piso_corte as r1')
    ->join('registro_piso_corte as r2', function($join) {
        $join->on('r1.fecha', '=', 'r2.fecha')
             ->on('r1.hora_id', '=', 'r2.hora_id')
             ->on('r1.operario_id', '=', 'r2.operario_id')
             ->on('r1.orden_produccion', '=', 'r2.orden_produccion')
             ->on('r1.cantidad', '=', 'r2.cantidad')
             ->whereColumn('r1.id', '<', 'r2.id')
             ->whereColumn('r1.tela_id', '!=', 'r2.tela_id');
    })
    ->whereYear('r1.fecha', 2025)
    ->whereMonth('r1.fecha', 10)
    ->select('r1.id as id1', 'r2.id as id2', 'r1.fecha', 'r1.cantidad', 'r1.tela_id', 'r2.tela_id as tela_id2')
    ->get();

echo "Pares de registros duplicados con diferente tela: " . $duplicadosPorTela->count() . "\n";

if ($duplicadosPorTela->count() > 0) {
    echo "\nPrimeros 10 ejemplos:\n";
    foreach ($duplicadosPorTela->take(10) as $par) {
        echo sprintf("  IDs: %5d y %5d | Fecha: %s | Cantidad: %5d | Telas: %s y %s\n",
            $par->id1, $par->id2, $par->fecha, $par->cantidad, $par->tela_id, $par->tela_id2
        );
    }
}
