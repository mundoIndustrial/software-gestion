<?php
// Cargar autoload
require 'vendor/autoload.php';

// Crear la aplicación de Laravel
$app = require_once 'bootstrap/app.php';

// Registrar los bindings necesarios
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Procesar las facades
\Illuminate\Support\Facades\Facade::setFacadeApplication($app);

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Http\Kernel;

try {
    echo "\n=== DIAGNÓSTICO: REGISTRO ÓRDENES BODEGA ===\n";
    
    // 1. Verificar tabla
    $tables = DB::select("SHOW TABLES LIKE 'tabla_original_bodega'");
    echo "✓ Tabla existe: " . (count($tables) > 0 ? "SÍ" : "NO") . "\n";
    
    if (count($tables) === 0) {
        echo "❌ ERROR: La tabla no existe\n";
        exit(1);
    }
    
    // 2. Contar registros
    $count = DB::table('tabla_original_bodega')->count();
    echo "✓ Total de registros: $count\n";
    
    if ($count === 0) {
        echo "⚠️  LA TABLA ESTÁ VACÍA\n\n";
        
        // Verificar si hay data en tabla_original
        $countOriginal = DB::table('tabla_original')->count();
        echo "✓ Registros en 'tabla_original': $countOriginal\n";
        
        if ($countOriginal > 0) {
            echo "➜ SOLUCIÓN: Migrar datos de tabla_original a tabla_original_bodega\n";
        }
    } else {
        echo "\n=== PRIMEROS 5 REGISTROS ===\n";
        $registros = DB::table('tabla_original_bodega')
            ->select('pedido', 'cliente', 'estado')
            ->limit(5)
            ->get();
        
        foreach ($registros as $reg) {
            echo "  Pedido: {$reg->pedido}, Cliente: {$reg->cliente}, Estado: {$reg->estado}\n";
        }
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
