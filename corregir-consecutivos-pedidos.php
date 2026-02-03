<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CORRECCIÓN: Actualizar consecutivos_recibos_pedidos ===\n\n";

// Obtener los valores correctos de la tabla maestra
$maestro = DB::table('consecutivos_recibos')
    ->whereIn('tipo_recibo', ['COSTURA', 'ESTAMPADO', 'BORDADO', 'REFLECTIVO', 'DTF', 'SUBLIMADO'])
    ->get()
    ->keyBy('tipo_recibo');

echo "--- Valores maestros actuales ---\n";
foreach ($maestro as $tipo => $reg) {
    echo "{$tipo}: {$reg->consecutivo_actual}\n";
}

echo "\n--- Actualizando registros ---\n";

// Actualizar los registros en consecutivos_recibos_pedidos con los valores correctos
foreach ($maestro as $tipo => $registro) {
    try {
        // Obtener todos los registros de este tipo que tengan consecutivo = 1
        $registros = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', $tipo)
            ->where('consecutivo_actual', 1)
            ->get();

        foreach ($registros as $reg) {
            // El número de recibo debe coincidir con el consecutivo_actual del maestro
            $nuevoConsecutivo = $registro->consecutivo_actual;
            
            DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reg->id)
                ->update([
                    'consecutivo_actual' => $nuevoConsecutivo,
                    'consecutivo_inicial' => $nuevoConsecutivo,
                    'updated_at' => now()
                ]);
            
            echo "✓ ID {$reg->id} ({$tipo}): Actualizado a {$nuevoConsecutivo}\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error al actualizar {$tipo}: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verificación final ---\n";
$finales = DB::table('consecutivos_recibos_pedidos')
    ->orderBy('tipo_recibo')
    ->get();

foreach ($finales as $reg) {
    echo "ID: {$reg->id} | Tipo: {$reg->tipo_recibo} | Consecutivo: {$reg->consecutivo_actual}\n";
}

echo "\nCorrección completada.\n";
?>
