<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS DE TABLAS DE FOTOS Y SUS RELACIONES ===\n\n";

// Tablas de fotos relacionadas a prendas_pedido
$tablas_fotos = [
    'prenda_fotos_pedido',
    'prenda_logo_fotos',
    'tela_fotos_pedido',
];

foreach ($tablas_fotos as $tabla) {
    echo "📌 TABLA: $tabla\n";
    echo str_repeat("─", 80) . "\n";
    
    try {
        // Verificar si existe
        $exists = DB::select("SHOW TABLES LIKE '$tabla'");
        
        if (empty($exists)) {
            echo "❌ NO EXISTE\n\n";
            continue;
        }
        
        echo "✓ EXISTE\n\n";
        
        // Columnas
        $columns = DB::select("DESCRIBE $tabla");
        echo "📋 COLUMNAS:\n";
        foreach ($columns as $col) {
            echo "   • {$col->Field}: {$col->Type}" . ($col->Key ? " [{$col->Key}]" : "") . "\n";
        }
        
        // Foreign Keys
        echo "\n🔗 FOREIGN KEYS:\n";
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = '$tabla' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (!empty($fks)) {
            foreach ($fks as $fk) {
                echo "   ✓ {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        } else {
            echo "   ❌ SIN FOREIGN KEYS DEFINIDAS\n";
        }
        
        // Revisar si tiene columna prenda_pedido_id
        $has_prenda_col = DB::select("SHOW COLUMNS FROM $tabla WHERE Field = 'prenda_pedido_id'");
        
        if (!empty($has_prenda_col)) {
            echo "\n✓ Tiene columna 'prenda_pedido_id'\n";
            
            // Contar registros
            $count = DB::table($tabla)->count();
            echo "📊 Registros: $count\n";
        } else {
            echo "\n⚠️  NO tiene columna 'prenda_pedido_id'\n";
            
            // Mostrar la columna que relaciona a prendas
            $cols = DB::getSchemaBuilder()->getColumnListing($tabla);
            $prenda_cols = array_filter($cols, function($c) {
                return strpos($c, 'prenda') !== false || strpos($c, 'pedido') !== false || strpos($c, 'logo') !== false;
            });
            echo "   Columnas relacionadas: " . implode(", ", $prenda_cols) . "\n";
        }
        
        echo "\n";
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== ESTRUCTURA ESPERADA ===\n";
echo "Todas las tablas de fotos deberían tener:\n";
echo "  1. Columna: prenda_pedido_id (unsignedBigInteger)\n";
echo "  2. Foreign Key: prenda_pedido_id → prendas_pedido.id (onDelete: cascade)\n\n";
