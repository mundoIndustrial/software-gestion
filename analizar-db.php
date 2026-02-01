<?php
/**
 * Script para analizar la estructura de la BD
 * Busca todas las tablas y las relacionadas con entregas
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "═══════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS COMPLETO DE LA BASE DE DATOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Obtener todas las tablas
echo "📊 TODAS LAS TABLAS EN LA BD:\n";
echo "───────────────────────────────────────────────────────────────\n";

$tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
$tableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);

foreach ($tableNames as $table) {
    echo "  • $table\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";

// 2. Buscar tablas de entregas
echo "🎯 TABLAS RELACIONADAS CON ENTREGAS:\n";
echo "───────────────────────────────────────────────────────────────\n";

$entregaTables = array_filter($tableNames, function($table) {
    return stripos($table, 'entrega') !== false;
});

if (empty($entregaTables)) {
    echo "❌ NO se encontraron tablas con 'entrega' en el nombre\n";
} else {
    foreach ($entregaTables as $table) {
        echo "\n📋 Tabla: $table\n";
        echo "   Estructura:\n";
        
        $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                              FROM information_schema.COLUMNS 
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
                              ORDER BY ORDINAL_POSITION");
        
        foreach ($columns as $col) {
            $null = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
            $key = $col->COLUMN_KEY ? "[{$col->COLUMN_KEY}]" : '';
            $extra = $col->EXTRA ? "({$col->EXTRA})" : '';
            echo "      - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} | Null:$null $key $extra\n";
        }
        
        // Contar registros
        $count = DB::table($table)->count();
        echo "      Registros: $count\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";

// 3. Buscar otras tablas relacionadas (por keywords)
echo "🔍 TABLAS RELACIONADAS (por keywords):\n";
echo "───────────────────────────────────────────────────────────────\n";

$keywords = ['prenda', 'pedido', 'produccion', 'proceso', 'corte', 'costura', 'bodega'];
$relatedTables = [];

foreach ($keywords as $keyword) {
    $found = array_filter($tableNames, fn($t) => stripos($t, $keyword) !== false);
    if (!empty($found)) {
        foreach ($found as $table) {
            if (!in_array($table, $relatedTables)) {
                $relatedTables[$keyword][] = $table;
            }
        }
    }
}

foreach ($keywords as $keyword) {
    if (isset($relatedTables[$keyword])) {
        echo "\n🏷️  Keyword: '$keyword'\n";
        foreach ($relatedTables[$keyword] as $table) {
            echo "     • $table\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";

// 4. Mostrar relaciones (Foreign Keys)
echo "🔗 FOREIGN KEYS (Relaciones):\n";
echo "───────────────────────────────────────────────────────────────\n";

$fks = DB::select("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME,
        CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, COLUMN_NAME
");

if (empty($fks)) {
    echo "❌ No hay foreign keys definidas\n";
} else {
    $currentTable = '';
    foreach ($fks as $fk) {
        if ($fk->TABLE_NAME !== $currentTable) {
            echo "\n📌 {$fk->TABLE_NAME}:\n";
            $currentTable = $fk->TABLE_NAME;
        }
        echo "     {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n\n";

// 5. Tablas vacías vs con datos
echo "📈 ESTADÍSTICAS:\n";
echo "───────────────────────────────────────────────────────────────\n";

$stats = [];
foreach ($tableNames as $table) {
    $count = DB::table($table)->count();
    $stats[$table] = $count;
}

arsort($stats);

echo "Tablas con datos (ordenadas por cantidad):\n";
foreach ($stats as $table => $count) {
    $bar = str_repeat('█', min($count / 100, 50));
    echo sprintf("  %-40s %8d registros %s\n", $table, $count, $bar);
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Análisis completado\n";
echo "═══════════════════════════════════════════════════════════════\n";
