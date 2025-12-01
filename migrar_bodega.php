<?php
// Script para migrar datos de tabla_original a tabla_original_bodega
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

// Inicializar la aplicación
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

try {
    echo "\n=== MIGRANDO DATOS A tabla_original_bodega ===\n\n";
    
    // 1. Contar registros en tabla_original
    $countOrig = \DB::table('tabla_original')->count();
    echo "✓ Registros en tabla_original: $countOrig\n";
    
    // 2. Contar registros en tabla_original_bodega
    $countBodega = \DB::table('tabla_original_bodega')->count();
    echo "✓ Registros en tabla_original_bodega (antes): $countBodega\n\n";
    
    if ($countBodega > 0) {
        echo "⚠️  tabla_original_bodega ya tiene datos. ¿Deseas continuar? [S/n]: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (strtolower(trim($line)) !== 's' && trim($line) !== '') {
            echo "Operación cancelada.\n";
            exit(0);
        }
        
        // Limpiar tabla
        echo "Limpiando tabla_original_bodega...\n";
        \DB::table('tabla_original_bodega')->truncate();
    }
    
    if ($countOrig === 0) {
        echo "❌ No hay datos en tabla_original para migrar.\n";
        exit(1);
    }
    
    // 3. Copiar datos en lotes
    echo "Copiando datos...\n";
    $loteSize = 100;
    $registros = \DB::table('tabla_original')
        ->select('*')
        ->get()
        ->chunk($loteSize);
    
    $totalInsertados = 0;
    foreach ($registros as $lote) {
        $dataLote = $lote->toArray();
        // Convertir objetos a arrays
        $dataLote = array_map(function($item) {
            return (array) $item;
        }, $dataLote);
        
        \DB::table('tabla_original_bodega')->insert($dataLote);
        $totalInsertados += count($lote);
        echo "  ✓ Insertados: $totalInsertados/$countOrig\n";
    }
    
    // 4. Verificar resultado
    $countFinal = \DB::table('tabla_original_bodega')->count();
    echo "\n✓ Registros en tabla_original_bodega (después): $countFinal\n";
    
    if ($countFinal === $countOrig) {
        echo "\n✅ MIGRACIÓN EXITOSA\n";
        echo "Se copiaron $countFinal registros correctamente.\n\n";
    } else {
        echo "\n⚠️  Posible problema en la migración.\n";
        echo "Esperados: $countOrig, Obtenidos: $countFinal\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
