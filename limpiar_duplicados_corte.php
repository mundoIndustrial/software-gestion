<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\RegistroPisoCorte;
use Illuminate\Support\Facades\DB;

echo "=== LIMPIEZA DE REGISTROS DUPLICADOS - CORTE ===\n\n";

// Preguntar confirmación
echo "⚠️  ADVERTENCIA: Este script eliminará registros duplicados de la base de datos.\n";
echo "¿Desea continuar? (escriba 'SI' para confirmar): ";
$handle = fopen("php://stdin", "r");
$confirmacion = trim(fgets($handle));
fclose($handle);

if (strtoupper($confirmacion) !== 'SI') {
    echo "\n❌ Operación cancelada.\n";
    exit(0);
}

echo "\n=== PASO 1: Identificar duplicados ===\n";

// Obtener registros de octubre 2025
$registros = RegistroPisoCorte::whereYear('fecha', 2025)
    ->whereMonth('fecha', 10)
    ->with(['hora', 'operario', 'tela'])
    ->orderBy('fecha')
    ->orderBy('hora_id')
    ->orderBy('operario_id')
    ->orderBy('id')
    ->get();

echo "Total registros en octubre 2025: " . $registros->count() . "\n";
echo "Suma total ANTES: " . $registros->sum('cantidad') . "\n\n";

// Agrupar por fecha, hora, operario, orden_produccion, cantidad
$grupos = $registros->groupBy(function($registro) {
    return sprintf(
        "%s|%s|%s|%s|%d",
        $registro->fecha->format('Y-m-d'),
        $registro->hora_id ?? 'NULL',
        $registro->operario_id ?? 'NULL',
        $registro->orden_produccion,
        $registro->cantidad
    );
});

$idsAEliminar = [];
$cantidadAEliminar = 0;

foreach ($grupos as $key => $grupo) {
    if ($grupo->count() > 1) {
        // Mantener solo el primer registro (menor ID)
        $registrosOrdenados = $grupo->sortBy('id');
        $primero = $registrosOrdenados->first();
        
        // Marcar el resto para eliminar
        foreach ($registrosOrdenados->skip(1) as $registro) {
            $idsAEliminar[] = $registro->id;
            $cantidadAEliminar += $registro->cantidad;
        }
    }
}

echo "=== PASO 2: Resumen de duplicados ===\n";
echo "Registros a eliminar: " . count($idsAEliminar) . "\n";
echo "Cantidad total a eliminar: $cantidadAEliminar\n";
echo "IDs a eliminar: " . implode(', ', $idsAEliminar) . "\n\n";

if (count($idsAEliminar) == 0) {
    echo "✅ No hay duplicados para eliminar.\n";
    exit(0);
}

echo "=== PASO 3: Confirmar eliminación ===\n";
echo "¿Confirma la eliminación de " . count($idsAEliminar) . " registros? (escriba 'ELIMINAR' para confirmar): ";
$handle = fopen("php://stdin", "r");
$confirmacion2 = trim(fgets($handle));
fclose($handle);

if (strtoupper($confirmacion2) !== 'ELIMINAR') {
    echo "\n❌ Operación cancelada.\n";
    exit(0);
}

echo "\n=== PASO 4: Eliminando registros ===\n";

DB::beginTransaction();

try {
    // Eliminar en lotes de 100
    $lotes = array_chunk($idsAEliminar, 100);
    $totalEliminados = 0;
    
    foreach ($lotes as $i => $lote) {
        $eliminados = RegistroPisoCorte::whereIn('id', $lote)->delete();
        $totalEliminados += $eliminados;
        echo "Lote " . ($i + 1) . "/" . count($lotes) . ": $eliminados registros eliminados\n";
    }
    
    DB::commit();
    
    echo "\n✅ Eliminación completada exitosamente.\n";
    echo "Total de registros eliminados: $totalEliminados\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error durante la eliminación: " . $e->getMessage() . "\n";
    echo "Se revirtieron todos los cambios.\n";
    exit(1);
}

echo "=== PASO 5: Verificar resultados ===\n";

// Volver a contar
$registrosDespues = RegistroPisoCorte::whereYear('fecha', 2025)
    ->whereMonth('fecha', 10)
    ->get();

echo "Total registros DESPUÉS: " . $registrosDespues->count() . "\n";
echo "Suma total DESPUÉS: " . $registrosDespues->sum('cantidad') . "\n\n";

echo "=== COMPARACIÓN ===\n";
echo "Registros eliminados: " . ($registros->count() - $registrosDespues->count()) . "\n";
echo "Cantidad reducida: " . ($registros->sum('cantidad') - $registrosDespues->sum('cantidad')) . "\n\n";

echo "Total en Excel: 14,201\n";
echo "Total en BD (corregido): " . $registrosDespues->sum('cantidad') . "\n";
echo "Diferencia: " . ($registrosDespues->sum('cantidad') - 14201) . "\n\n";

echo "✅ Proceso completado.\n";
