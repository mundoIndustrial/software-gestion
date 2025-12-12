<?php

// Cargar configuración de .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ Archivo .env no encontrado\n");
}

$env = parse_ini_file($envFile);

// Conexión directa a MySQL
try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']}",
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage() . "\n");
}

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║          ANÁLISIS COMPLETO DE BASE DE DATOS                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. TODAS LAS TABLAS
echo "📊 TODAS LAS TABLAS EN LA BASE DE DATOS:\n";
echo str_repeat("─", 70) . "\n";

$stmt = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
$allTables = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allTables as $table) {
    echo sprintf("  %-45s | %6d registros\n", $table['TABLE_NAME'], $table['TABLE_ROWS']);
}

echo "\n";

// 2. TABLAS RELACIONADAS CON COTIZACIONES
echo "🎯 TABLAS DE COTIZACIONES (Sistema DDD - terminan en '_cot'):\n";
echo str_repeat("─", 70) . "\n";

$cotTables = array_filter($allTables, function($t) {
    return strpos($t['TABLE_NAME'], '_cot') !== false || 
           strpos($t['TABLE_NAME'], 'cotizacion') !== false;
});

if (empty($cotTables)) {
    echo "  ❌ No se encontraron tablas de cotizaciones\n";
} else {
    foreach ($cotTables as $table) {
        echo sprintf("  ✅ %-43s | %6d registros\n", $table['TABLE_NAME'], $table['TABLE_ROWS']);
    }
}

echo "\n";

// 3. ESTRUCTURA DETALLADA DE CADA TABLA DE COTIZACIONES
echo "📋 ESTRUCTURA DETALLADA DE TABLAS DE COTIZACIONES:\n";
echo str_repeat("═", 70) . "\n";

foreach ($cotTables as $table) {
    $tableName = $table['TABLE_NAME'];
    echo "\n📌 Tabla: {$tableName} ({$table['TABLE_ROWS']} registros)\n";
    echo str_repeat("─", 70) . "\n";
    
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                           FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = ?
                           ORDER BY ORDINAL_POSITION");
    $stmt->execute([$tableName]);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        $nullable = $col['IS_NULLABLE'] === 'YES' ? '✓' : '✗';
        $key = $col['COLUMN_KEY'] ? "({$col['COLUMN_KEY']})" : '';
        $extra = $col['EXTRA'] ? " [{$col['EXTRA']}]" : '';
        
        echo sprintf("  %-30s | %-25s | NULL:%s %s%s\n", 
            $col['COLUMN_NAME'],
            $col['COLUMN_TYPE'],
            $nullable,
            $key,
            $extra
        );
    }
}

echo "\n";

// 4. TABLA PRINCIPAL: cotizaciones
echo "🔧 TABLA PRINCIPAL: 'cotizaciones'\n";
echo str_repeat("─", 70) . "\n";

$stmt = $pdo->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'cotizaciones'
                     ORDER BY ORDINAL_POSITION");
$cotizacionColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cotizacionColumns)) {
    echo "  ❌ Tabla 'cotizaciones' no existe\n";
} else {
    foreach ($cotizacionColumns as $col) {
        $nullable = $col['IS_NULLABLE'] === 'YES' ? '✓' : '✗';
        $key = $col['COLUMN_KEY'] ? "({$col['COLUMN_KEY']})" : '';
        $extra = $col['EXTRA'] ? " [{$col['EXTRA']}]" : '';
        
        echo sprintf("  %-30s | %-25s | NULL:%s %s%s\n", 
            $col['COLUMN_NAME'],
            $col['COLUMN_TYPE'],
            $nullable,
            $key,
            $extra
        );
    }
}

echo "\n";

// 5. VERIFICAR TABLAS ESPERADAS
echo "✅ VERIFICACIÓN DE TABLAS ESPERADAS (Sistema DDD):\n";
echo str_repeat("─", 70) . "\n";

$expectedTables = [
    'cotizaciones' => 'Tabla principal',
    'cotizacion_detalles' => 'Detalles de items',
    'historial_cambios_cotizaciones' => 'Historial de cambios',
    'cotizacion_aprobaciones' => 'Aprobaciones',
];

$existingNames = array_map(fn($t) => $t['TABLE_NAME'], $allTables);

foreach ($expectedTables as $tableName => $description) {
    $exists = in_array($tableName, $existingNames);
    $status = $exists ? '✅' : '❌';
    echo "  $status $tableName - $description\n";
}

echo "\n";

// 6. RELACIONES Y CLAVES FORÁNEAS
echo "🔗 RELACIONES Y CLAVES FORÁNEAS:\n";
echo str_repeat("─", 70) . "\n";

$stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                     FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND REFERENCED_TABLE_NAME IS NOT NULL
                     AND (TABLE_NAME LIKE '%cot%' OR TABLE_NAME LIKE '%cotizacion%')
                     ORDER BY TABLE_NAME");
$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($fks)) {
    echo "  ℹ️  No se encontraron claves foráneas en tablas de cotizaciones\n";
} else {
    foreach ($fks as $fk) {
        echo sprintf("  %s.%s → %s.%s\n",
            $fk['TABLE_NAME'],
            $fk['COLUMN_NAME'],
            $fk['REFERENCED_TABLE_NAME'],
            $fk['REFERENCED_COLUMN_NAME']
        );
    }
}

echo "\n";

// 7. MUESTRA DE DATOS DE COTIZACIONES
echo "📊 MUESTRA DE DATOS - Tabla 'cotizaciones':\n";
echo str_repeat("─", 70) . "\n";

$stmt = $pdo->query("SELECT * FROM cotizaciones LIMIT 3");
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($samples)) {
    echo "  ℹ️  No hay registros en la tabla\n";
} else {
    foreach ($samples as $i => $row) {
        echo "\n  Registro #" . ($i + 1) . ":\n";
        foreach ($row as $col => $val) {
            $displayVal = $val === null ? '(NULL)' : (strlen($val) > 50 ? substr($val, 0, 47) . '...' : $val);
            echo sprintf("    %-30s: %s\n", $col, $displayVal);
        }
    }
}

echo "\n╚════════════════════════════════════════════════════════════════╝\n\n";
