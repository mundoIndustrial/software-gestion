<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Array de actualizaciones
$actualizaciones = [
    'ESTAMPADO' => 22610,
    'BORDADO' => 42637,
    'COSTURA' => 45936,
    'REFLECTIVO' => 45939,
];

echo "Actualizando consecutivos en la tabla consecutivos_recibos...\n\n";

foreach ($actualizaciones as $tipo => $numero) {
    try {
        // Verificar si existe el registro
        $existe = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', $tipo)
            ->exists();
        
        if ($existe) {
            // Actualizar
            DB::table('consecutivos_recibos')
                ->where('tipo_recibo', $tipo)
                ->update([
                    'consecutivo_actual' => $numero,
                    'updated_at' => now()
                ]);
            echo "✓ Actualizado: {$tipo} -> {$numero}\n";
        } else {
            echo "✗ No existe registro para: {$tipo}\n";
        }
    } catch (\Exception $e) {
        echo "✗ Error al actualizar {$tipo}: " . $e->getMessage() . "\n";
    }
}

echo "\n--- Verificación final ---\n";
$registros = DB::table('consecutivos_recibos')
    ->whereIn('tipo_recibo', array_keys($actualizaciones))
    ->get();

foreach ($registros as $reg) {
    echo "{$reg->tipo_recibo}: {$reg->consecutivo_actual}\n";
}

echo "\nActualización completada.\n";
?>
