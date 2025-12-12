<?php

require __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "\n=== ANÁLISIS DE BASE DE DATOS ===\n\n";

// 1. Obtener todas las tablas
echo "📊 TABLAS EXISTENTES:\n";
echo str_repeat("-", 80) . "\n";

$tables = DB::select("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH 
                      FROM INFORMATION_SCHEMA.TABLES 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      ORDER BY TABLE_NAME");

foreach ($tables as $table) {
    echo sprintf("%-40s | Registros: %6d | Tamaño: %s\n", 
        $table->TABLE_NAME, 
        $table->TABLE_ROWS,
        formatBytes($table->DATA_LENGTH)
    );
}

echo "\n";

// 2. Analizar tablas de cotizaciones
echo "🎯 TABLAS RELACIONADAS CON COTIZACIONES:\n";
echo str_repeat("-", 80) . "\n";

$cotizacionTables = array_filter($tables, function($t) {
    return stripos($t->TABLE_NAME, 'cotizacion') !== false || 
           stripos($t->TABLE_NAME, 'cot') !== false;
});

if (empty($cotizacionTables)) {
    echo "❌ No se encontraron tablas con 'cotizacion' o 'cot'\n";
} else {
    foreach ($cotizacionTables as $table) {
        echo "✅ " . $table->TABLE_NAME . " (" . $table->TABLE_ROWS . " registros)\n";
    }
}

echo "\n";

// 3. Detallar estructura de cada tabla de cotizaciones
echo "📋 ESTRUCTURA DE TABLAS DE COTIZACIONES:\n";
echo str_repeat("-", 80) . "\n";

foreach ($cotizacionTables as $table) {
    echo "\n📌 Tabla: {$table->TABLE_NAME}\n";
    echo str_repeat("~", 80) . "\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                           FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = ?
                           ORDER BY ORDINAL_POSITION", [$table->TABLE_NAME]);
    
    foreach ($columns as $col) {
        $nullable = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
        $key = $col->COLUMN_KEY ? "({$col->COLUMN_KEY})" : '';
        $extra = $col->EXTRA ? " [{$col->EXTRA}]" : '';
        
        echo sprintf("  %-30s | %-25s | NULL:%s %s%s\n", 
            $col->COLUMN_NAME,
            $col->COLUMN_TYPE,
            $nullable,
            $key,
            $extra
        );
    }
}

echo "\n";

// 4. Verificar tablas que DEBERÍAN existir según DDD
echo "🔍 VERIFICACIÓN DE TABLAS ESPERADAS (Sistema DDD):\n";
echo str_repeat("-", 80) . "\n";

$expectedTables = [
    'cotizaciones' => 'Tabla principal de cotizaciones',
    'cotizacion_detalles' => 'Detalles de items en cotizaciones',
    'historial_cambios_cotizaciones' => 'Historial de cambios en cotizaciones',
    'cotizacion_aprobaciones' => 'Aprobaciones de cotizaciones',
];

$existingTableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);

foreach ($expectedTables as $tableName => $description) {
    $exists = in_array($tableName, $existingTableNames);
    $status = $exists ? '✅' : '❌';
    echo "$status $tableName - $description\n";
}

echo "\n";

// 5. Mostrar campos de cotizaciones principal
echo "🔧 CAMPOS DE TABLA 'cotizaciones':\n";
echo str_repeat("-", 80) . "\n";

if (in_array('cotizaciones', $existingTableNames)) {
    $cotizacionColumns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                                     FROM INFORMATION_SCHEMA.COLUMNS 
                                     WHERE TABLE_SCHEMA = DATABASE() 
                                     AND TABLE_NAME = 'cotizaciones'
                                     ORDER BY ORDINAL_POSITION");
    
    foreach ($cotizacionColumns as $col) {
        $nullable = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
        $key = $col->COLUMN_KEY ? "({$col->COLUMN_KEY})" : '';
        $extra = $col->EXTRA ? " [{$col->EXTRA}]" : '';
        
        echo sprintf("  %-30s | %-25s | NULL:%s %s%s\n", 
            $col->COLUMN_NAME,
            $col->COLUMN_TYPE,
            $nullable,
            $key,
            $extra
        );
    }
} else {
    echo "❌ Tabla 'cotizaciones' no existe\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n\n";

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
