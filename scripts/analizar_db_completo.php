<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           ANÁLISIS COMPLETO DE BASE DE DATOS - MUNDO INDUSTRIAL           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// ============================================================================
// 1. INFORMACIÓN GENERAL DE LA BASE DE DATOS
// ============================================================================
echo "📊 INFORMACIÓN GENERAL\n";
echo str_repeat("=", 80) . "\n";

$dbName = DB::select("SELECT DATABASE() as db")[0]->db;
$dbSize = DB::select("SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb 
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()")[0]->size_mb;

$tableCount = DB::select("SELECT COUNT(*) as count 
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()")[0]->count;

echo "Base de datos: $dbName\n";
echo "Tamaño total: {$dbSize} MB\n";
echo "Total de tablas: $tableCount\n";
echo "\n";

// ============================================================================
// 2. LISTADO DE TODAS LAS TABLAS CON ESTADÍSTICAS
// ============================================================================
echo "📋 TODAS LAS TABLAS (ordenadas por tamaño)\n";
echo str_repeat("=", 80) . "\n";

$tables = DB::select("SELECT 
    TABLE_NAME, 
    TABLE_ROWS, 
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
    ROUND(DATA_LENGTH / 1024 / 1024, 2) as data_mb,
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) as index_mb,
    ENGINE,
    TABLE_COLLATION
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC");

printf("%-40s | %10s | %10s | %10s | %10s\n", 
    "TABLA", "REGISTROS", "TAMAÑO", "DATOS", "ÍNDICES");
echo str_repeat("-", 80) . "\n";

foreach ($tables as $table) {
    printf("%-40s | %10s | %7s MB | %7s MB | %7s MB\n", 
        $table->TABLE_NAME, 
        number_format($table->TABLE_ROWS),
        $table->size_mb,
        $table->data_mb,
        $table->index_mb
    );
}
echo "\n";

// ============================================================================
// 3. ANÁLISIS DE TABLAS DE COTIZACIONES
// ============================================================================
echo "🎯 TABLAS RELACIONADAS CON COTIZACIONES\n";
echo str_repeat("=", 80) . "\n";

$cotizacionTables = array_filter($tables, function($t) {
    return stripos($t->TABLE_NAME, 'cotizacion') !== false || 
           stripos($t->TABLE_NAME, 'cot') !== false;
});

if (empty($cotizacionTables)) {
    echo "❌ No se encontraron tablas relacionadas con cotizaciones\n";
} else {
    foreach ($cotizacionTables as $table) {
        echo "✅ {$table->TABLE_NAME}\n";
        echo "   └─ Registros: " . number_format($table->TABLE_ROWS) . "\n";
        echo "   └─ Tamaño: {$table->size_mb} MB\n";
    }
}
echo "\n";

// ============================================================================
// 4. ANÁLISIS DE TABLAS DE PEDIDOS
// ============================================================================
echo "📦 TABLAS RELACIONADAS CON PEDIDOS\n";
echo str_repeat("=", 80) . "\n";

$pedidoTables = array_filter($tables, function($t) {
    return stripos($t->TABLE_NAME, 'pedido') !== false || 
           stripos($t->TABLE_NAME, 'ped') !== false;
});

if (empty($pedidoTables)) {
    echo "❌ No se encontraron tablas relacionadas con pedidos\n";
} else {
    foreach ($pedidoTables as $table) {
        echo "✅ {$table->TABLE_NAME}\n";
        echo "   └─ Registros: " . number_format($table->TABLE_ROWS) . "\n";
        echo "   └─ Tamaño: {$table->size_mb} MB\n";
    }
}
echo "\n";

// ============================================================================
// 5. ESTRUCTURA DETALLADA DE TABLAS PRINCIPALES
// ============================================================================
echo "🔍 ESTRUCTURA DETALLADA DE TABLAS PRINCIPALES\n";
echo str_repeat("=", 80) . "\n";

$mainTables = [
    'cotizaciones',
    'prendas_cot',
    'logo_cotizaciones',
    'reflectivo_cotizacion',
    'pedido_produccion',
    'prendas_pedido',
    'logo_pedido'
];

foreach ($mainTables as $tableName) {
    if (!Schema::hasTable($tableName)) {
        echo "\n❌ Tabla '$tableName' NO EXISTE\n";
        continue;
    }
    
    echo "\n📌 TABLA: $tableName\n";
    echo str_repeat("~", 80) . "\n";
    
    $columns = DB::select("SELECT 
        COLUMN_NAME, 
        COLUMN_TYPE, 
        IS_NULLABLE, 
        COLUMN_KEY, 
        COLUMN_DEFAULT,
        EXTRA,
        COLUMN_COMMENT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = ?
    ORDER BY ORDINAL_POSITION", [$tableName]);
    
    printf("%-30s | %-20s | %-8s | %-5s | %-10s\n", 
        "COLUMNA", "TIPO", "NULL", "KEY", "EXTRA");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($columns as $col) {
        $nullable = $col->IS_NULLABLE === 'YES' ? 'SÍ' : 'NO';
        $key = $col->COLUMN_KEY ?: '-';
        $extra = $col->EXTRA ?: '-';
        
        printf("%-30s | %-20s | %-8s | %-5s | %-10s\n", 
            $col->COLUMN_NAME,
            substr($col->COLUMN_TYPE, 0, 20),
            $nullable,
            $key,
            substr($extra, 0, 10)
        );
    }
}
echo "\n";

// ============================================================================
// 6. ANÁLISIS DE RELACIONES (FOREIGN KEYS)
// ============================================================================
echo "🔗 RELACIONES (FOREIGN KEYS)\n";
echo str_repeat("=", 80) . "\n";

$foreignKeys = DB::select("SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME");

if (empty($foreignKeys)) {
    echo "⚠️  No se encontraron foreign keys definidas\n";
} else {
    $currentTable = '';
    foreach ($foreignKeys as $fk) {
        if ($currentTable !== $fk->TABLE_NAME) {
            if ($currentTable !== '') echo "\n";
            echo "📋 {$fk->TABLE_NAME}:\n";
            $currentTable = $fk->TABLE_NAME;
        }
        echo "   └─ {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    }
}
echo "\n";

// ============================================================================
// 7. ANÁLISIS DE ÍNDICES
// ============================================================================
echo "📇 ÍNDICES DEFINIDOS\n";
echo str_repeat("=", 80) . "\n";

$indexes = DB::select("SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
    NON_UNIQUE,
    INDEX_TYPE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('" . implode("','", $mainTables) . "')
GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE, INDEX_TYPE
ORDER BY TABLE_NAME, INDEX_NAME");

$currentTable = '';
foreach ($indexes as $idx) {
    if ($currentTable !== $idx->TABLE_NAME) {
        if ($currentTable !== '') echo "\n";
        echo "📋 {$idx->TABLE_NAME}:\n";
        $currentTable = $idx->TABLE_NAME;
    }
    $type = $idx->NON_UNIQUE == 0 ? 'UNIQUE' : 'INDEX';
    echo "   └─ [{$type}] {$idx->INDEX_NAME} ({$idx->columns})\n";
}
echo "\n";

// ============================================================================
// 8. ANÁLISIS DE DATOS - COTIZACIONES
// ============================================================================
echo "📊 ANÁLISIS DE DATOS - COTIZACIONES\n";
echo str_repeat("=", 80) . "\n";

if (Schema::hasTable('cotizaciones')) {
    $cotStats = DB::select("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN tipo = 'P' THEN 1 END) as tipo_prenda,
        COUNT(CASE WHEN tipo = 'L' THEN 1 END) as tipo_logo,
        COUNT(CASE WHEN tipo = 'PL' THEN 1 END) as tipo_combinado,
        COUNT(CASE WHEN tipo = 'R' THEN 1 END) as tipo_reflectivo,
        COUNT(CASE WHEN estado = 'borrador' THEN 1 END) as borradores,
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado = 'aprobado' THEN 1 END) as aprobadas,
        COUNT(CASE WHEN estado = 'rechazado' THEN 1 END) as rechazadas
    FROM cotizaciones")[0];
    
    echo "Total cotizaciones: " . number_format($cotStats->total) . "\n";
    echo "\nPor tipo:\n";
    echo "  └─ Prenda (P): " . number_format($cotStats->tipo_prenda) . "\n";
    echo "  └─ Logo (L): " . number_format($cotStats->tipo_logo) . "\n";
    echo "  └─ Combinado (PL): " . number_format($cotStats->tipo_combinado) . "\n";
    echo "  └─ Reflectivo (R): " . number_format($cotStats->tipo_reflectivo) . "\n";
    echo "\nPor estado:\n";
    echo "  └─ Borradores: " . number_format($cotStats->borradores) . "\n";
    echo "  └─ Pendientes: " . number_format($cotStats->pendientes) . "\n";
    echo "  └─ Aprobadas: " . number_format($cotStats->aprobadas) . "\n";
    echo "  └─ Rechazadas: " . number_format($cotStats->rechazadas) . "\n";
} else {
    echo "❌ Tabla 'cotizaciones' no existe\n";
}
echo "\n";

// ============================================================================
// 9. ANÁLISIS DE DATOS - PEDIDOS
// ============================================================================
echo "📦 ANÁLISIS DE DATOS - PEDIDOS\n";
echo str_repeat("=", 80) . "\n";

if (Schema::hasTable('pedido_produccion')) {
    $pedStats = DB::select("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
        COUNT(CASE WHEN estado = 'en_proceso' THEN 1 END) as en_proceso,
        COUNT(CASE WHEN estado = 'completado' THEN 1 END) as completados,
        SUM(cantidad_total) as cantidad_total_prendas
    FROM pedido_produccion")[0];
    
    echo "Total pedidos: " . number_format($pedStats->total) . "\n";
    echo "Cantidad total de prendas: " . number_format($pedStats->cantidad_total_prendas ?? 0) . "\n";
    echo "\nPor estado:\n";
    echo "  └─ Pendientes: " . number_format($pedStats->pendientes) . "\n";
    echo "  └─ En proceso: " . number_format($pedStats->en_proceso) . "\n";
    echo "  └─ Completados: " . number_format($pedStats->completados) . "\n";
} else {
    echo "❌ Tabla 'pedido_produccion' no existe\n";
}
echo "\n";

// ============================================================================
// 10. ANÁLISIS DE INTEGRIDAD - REGISTROS HUÉRFANOS
// ============================================================================
echo "🔍 ANÁLISIS DE INTEGRIDAD - REGISTROS HUÉRFANOS\n";
echo str_repeat("=", 80) . "\n";

// Prendas sin cotización
if (Schema::hasTable('prendas_cot') && Schema::hasTable('cotizaciones')) {
    $orphanPrendas = DB::select("SELECT COUNT(*) as count 
        FROM prendas_cot p 
        LEFT JOIN cotizaciones c ON p.cotizacion_id = c.id 
        WHERE c.id IS NULL")[0]->count;
    
    if ($orphanPrendas > 0) {
        echo "⚠️  Prendas sin cotización: $orphanPrendas\n";
    } else {
        echo "✅ Todas las prendas tienen cotización asociada\n";
    }
}

// Logos sin cotización
if (Schema::hasTable('logo_cotizaciones') && Schema::hasTable('cotizaciones')) {
    $orphanLogos = DB::select("SELECT COUNT(*) as count 
        FROM logo_cotizaciones l 
        LEFT JOIN cotizaciones c ON l.cotizacion_id = c.id 
        WHERE c.id IS NULL")[0]->count;
    
    if ($orphanLogos > 0) {
        echo "⚠️  Logos sin cotización: $orphanLogos\n";
    } else {
        echo "✅ Todos los logos tienen cotización asociada\n";
    }
}

// Variantes sin prenda
if (Schema::hasTable('variantes_prendas_cot') && Schema::hasTable('prendas_cot')) {
    $orphanVariantes = DB::select("SELECT COUNT(*) as count 
        FROM variantes_prendas_cot v 
        LEFT JOIN prendas_cot p ON v.prenda_cot_id = p.id 
        WHERE p.id IS NULL")[0]->count;
    
    if ($orphanVariantes > 0) {
        echo "⚠️  Variantes sin prenda: $orphanVariantes\n";
    } else {
        echo "✅ Todas las variantes tienen prenda asociada\n";
    }
}

// Tallas sin prenda
if (Schema::hasTable('talla_prenda_cot') && Schema::hasTable('prendas_cot')) {
    $orphanTallas = DB::select("SELECT COUNT(*) as count 
        FROM talla_prenda_cot t 
        LEFT JOIN prendas_cot p ON t.prenda_cot_id = p.id 
        WHERE p.id IS NULL")[0]->count;
    
    if ($orphanTallas > 0) {
        echo "⚠️  Tallas sin prenda: $orphanTallas\n";
    } else {
        echo "✅ Todas las tallas tienen prenda asociada\n";
    }
}

echo "\n";

// ============================================================================
// 11. ANÁLISIS DE IMÁGENES
// ============================================================================
echo "🖼️  ANÁLISIS DE IMÁGENES\n";
echo str_repeat("=", 80) . "\n";

$imageTables = [
    'prenda_fotos_cot' => 'Fotos de prendas',
    'prenda_tela_fotos_cot' => 'Fotos de telas',
    'logo_fotos_cot' => 'Fotos de logos',
    'reflectivo_fotos_cotizacion' => 'Fotos de reflectivos'
];

foreach ($imageTables as $table => $description) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "✅ $description ($table): " . number_format($count) . " imágenes\n";
    } else {
        echo "❌ $description ($table): NO EXISTE\n";
    }
}
echo "\n";

// ============================================================================
// 12. ANÁLISIS DE CAMPOS JSON
// ============================================================================
echo "📝 ANÁLISIS DE CAMPOS JSON\n";
echo str_repeat("=", 80) . "\n";

$jsonFields = [
    'cotizaciones' => ['especificaciones', 'telas_multiples', 'genero'],
    'prendas_cot' => ['genero', 'telas_multiples'],
    'reflectivo_cotizacion' => ['especificaciones']
];

foreach ($jsonFields as $table => $fields) {
    if (!Schema::hasTable($table)) continue;
    
    echo "📋 $table:\n";
    foreach ($fields as $field) {
        $columns = DB::select("SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?", [$table, $field]);
        
        if (!empty($columns)) {
            $nullCount = DB::table($table)->whereNull($field)->count();
            $notNullCount = DB::table($table)->whereNotNull($field)->count();
            echo "   └─ $field: $notNullCount con datos, $nullCount NULL\n";
        } else {
            echo "   └─ $field: ❌ NO EXISTE\n";
        }
    }
}
echo "\n";

// ============================================================================
// 13. TABLAS SIN USAR (sin registros)
// ============================================================================
echo "🗑️  TABLAS VACÍAS (sin registros)\n";
echo str_repeat("=", 80) . "\n";

$emptyTables = array_filter($tables, function($t) {
    return $t->TABLE_ROWS == 0;
});

if (empty($emptyTables)) {
    echo "✅ Todas las tablas tienen registros\n";
} else {
    foreach ($emptyTables as $table) {
        echo "⚠️  {$table->TABLE_NAME}\n";
    }
}
echo "\n";

// ============================================================================
// 14. RESUMEN Y RECOMENDACIONES
// ============================================================================
echo "💡 RESUMEN Y RECOMENDACIONES\n";
echo str_repeat("=", 80) . "\n";

$recommendations = [];

// Verificar foreign keys
if (empty($foreignKeys)) {
    $recommendations[] = "⚠️  No hay foreign keys definidas. Considerar agregar constraints para integridad referencial.";
}

// Verificar tablas vacías
if (count($emptyTables) > 5) {
    $recommendations[] = "⚠️  Hay " . count($emptyTables) . " tablas vacías. Considerar eliminar tablas no utilizadas.";
}

// Verificar índices en tablas grandes
foreach ($tables as $table) {
    if ($table->TABLE_ROWS > 10000) {
        $tableIndexes = array_filter($indexes, function($idx) use ($table) {
            return $idx->TABLE_NAME === $table->TABLE_NAME;
        });
        if (count($tableIndexes) < 2) {
            $recommendations[] = "⚠️  Tabla '{$table->TABLE_NAME}' tiene " . number_format($table->TABLE_ROWS) . " registros pero pocos índices.";
        }
    }
}

if (empty($recommendations)) {
    echo "✅ La base de datos está en buen estado general.\n";
} else {
    foreach ($recommendations as $rec) {
        echo "$rec\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                         FIN DEL ANÁLISIS COMPLETO                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
