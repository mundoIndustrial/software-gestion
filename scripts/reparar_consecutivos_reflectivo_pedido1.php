<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=======================================================\n";
echo "REPARAR CONSECUTIVOS REFLECTIVO FALTANTES - PEDIDO #1\n";
echo "=======================================================\n\n";

$pedidoId = 1;

// 1. Identificar prendas que necesitan consecutivo REFLECTIVO
echo " PASO 1: Identificando prendas afectadas...\n";
echo str_repeat("-", 80) . "\n";

$prendasConReflectivo = DB::table('prendas_pedido as pp')
    ->join('pedidos_procesos_prenda_detalles as ppd', 'pp.id', '=', 'ppd.prenda_pedido_id')
    ->join('tipos_procesos as tp', 'ppd.tipo_proceso_id', '=', 'tp.id')
    ->where('pp.pedido_produccion_id', $pedidoId)
    ->where('pp.de_bodega', true)
    ->where('tp.nombre', 'REFLECTIVO')
    ->whereNull('pp.deleted_at')
    ->whereNull('ppd.deleted_at')
    ->select('pp.id', 'pp.nombre_prenda', 'pp.de_bodega')
    ->distinct()
    ->get();

echo "Total prendas con de_bodega=true y REFLECTIVO: " . $prendasConReflectivo->count() . "\n\n";

$prendasSinConsecutivo = [];

foreach ($prendasConReflectivo as $prenda) {
    $tieneConsecutivo = DB::table('consecutivos_recibos_pedidos')
        ->where('pedido_produccion_id', $pedidoId)
        ->where('prenda_id', $prenda->id)
        ->where('tipo_recibo', 'REFLECTIVO')
        ->exists();
    
    if (!$tieneConsecutivo) {
        $prendasSinConsecutivo[] = $prenda;
        echo "  Prenda ID {$prenda->id} ({$prenda->nombre_prenda}) - SIN CONSECUTIVO\n";
    } else {
        echo " Prenda ID {$prenda->id} ({$prenda->nombre_prenda}) - Ya tiene consecutivo\n";
    }
}

echo "\n";

if (empty($prendasSinConsecutivo)) {
    echo " No hay prendas sin consecutivo. Todo está correcto.\n";
    exit(0);
}

echo "📝 Se encontraron " . count($prendasSinConsecutivo) . " prendas sin consecutivo REFLECTIVO.\n\n";

// 2. Obtener el consecutivo global actual de REFLECTIVO
echo " PASO 2: Obteniendo consecutivo global de REFLECTIVO...\n";
echo str_repeat("-", 80) . "\n";

$consecutivoGlobal = DB::table('consecutivos_recibos')
    ->where('tipo_recibo', 'REFLECTIVO')
    ->first();

if (!$consecutivoGlobal) {
    echo " ERROR: No existe consecutivo global para REFLECTIVO\n";
    exit(1);
}

echo "Consecutivo global actual: {$consecutivoGlobal->consecutivo_actual}\n";
echo "Consecutivo inicial: {$consecutivoGlobal->consecutivo_inicial}\n\n";

// 3. Crear los consecutivos faltantes
echo " PASO 3: Creando consecutivos faltantes...\n";
echo str_repeat("-", 80) . "\n";

DB::beginTransaction();

try {
    $consecutivoActual = $consecutivoGlobal->consecutivo_actual;
    
    foreach ($prendasSinConsecutivo as $prenda) {
        // Incrementar el consecutivo
        $consecutivoActual++;
        $nuevoConsecutivo = $consecutivoActual;
        
        // Crear el consecutivo para el pedido-prenda
        $consecutivoId = DB::table('consecutivos_recibos_pedidos')->insertGetId([
            'pedido_produccion_id' => $pedidoId,
            'prenda_id' => $prenda->id,
            'tipo_recibo' => 'REFLECTIVO',
            'consecutivo_inicial' => $nuevoConsecutivo,
            'consecutivo_actual' => $nuevoConsecutivo,
            'activo' => true,
            'notas' => 'Generado automáticamente por script de reparación - ' . date('Y-m-d H:i:s'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo " Creado consecutivo #{$nuevoConsecutivo} para Prenda ID {$prenda->id} ({$prenda->nombre_prenda})\n";
        echo "   - Consecutivo ID: {$consecutivoId}\n";
        echo "   - Tipo: REFLECTIVO\n";
        echo "   - Número: {$nuevoConsecutivo}\n\n";
    }
    
    // Actualizar el consecutivo global
    DB::table('consecutivos_recibos')
        ->where('tipo_recibo', 'REFLECTIVO')
        ->update([
            'consecutivo_actual' => $consecutivoActual,
            'updated_at' => now()
        ]);
    
    echo " Consecutivo global actualizado a: {$consecutivoActual}\n\n";
    
    DB::commit();
    
    echo "=======================================================\n";
    echo " REPARACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "=======================================================\n";
    echo "Total consecutivos creados: " . count($prendasSinConsecutivo) . "\n";
    echo "Consecutivo global final: {$consecutivoActual}\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n ERROR durante la reparación:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
