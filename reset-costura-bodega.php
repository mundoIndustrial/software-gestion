<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    echo "=== Limpiando COSTURA-BODEGA antiguo del seeder ===\n\n";

    // Eliminar todos los COSTURA-BODEGA que tienen consecutivo_actual = 0 (del seeder antiguo)
    $deleted = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->where('consecutivo_actual', 0)
        ->delete();
    
    echo "✓ Registros COSTURA-BODEGA eliminados: {$deleted}\n";
    
    // Resetear consecutivo a 0
    DB::table('consecutivos_recibos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->update(['consecutivo_actual' => 0]);
    
    echo "✓ Consecutivo COSTURA-BODEGA reseteado a 0\n";
    
    // Verificar estado actual
    echo "\n=== Estado actual ===\n";
    
    $consecutivo = DB::table('consecutivos_recibos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->first();
    
    echo "Consecutivo actual: {$consecutivo->consecutivo_actual}\n";
    
    $count = DB::table('consecutivos_recibos_pedidos')
        ->where('tipo_recibo', 'COSTURA-BODEGA')
        ->count();
    
    echo "Registros COSTURA-BODEGA pendientes: {$count}\n";
    
    echo "\n✓ Listo para nueva aprobación\n";

} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
