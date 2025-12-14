<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'cotizaciones',
    'pedidos_produccion',
    'clientes',
    'users'
];

echo "\n=== ANÁLISIS DE ESTRUCTURA DE TABLAS ===\n\n";

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "❌ Tabla '$table' NO existe\n\n";
        continue;
    }

    echo "📋 Tabla: $table\n";
    echo str_repeat("─", 80) . "\n";

    // Obtener columnas
    $columns = Schema::getColumns($table);
    
    echo sprintf("%-20s | %-15s | %-10s | %-20s\n", "COLUMNA", "TIPO", "NULLABLE", "DEFAULT");
    echo str_repeat("─", 80) . "\n";

    foreach ($columns as $column) {
        $name = $column['name'];
        $type = $column['type'];
        $nullable = $column['nullable'] ? 'SÍ' : 'NO';
        $default = $column['default'] ?? '-';

        printf("%-20s | %-15s | %-10s | %-20s\n", $name, $type, $nullable, $default);
    }

    // Contar registros
    $count = DB::table($table)->count();
    echo "\n📊 Total de registros: $count\n";

    // Obtener índices
    $indexes = Schema::getIndexes($table);
    if (!empty($indexes)) {
        echo "\n🔑 Índices:\n";
        foreach ($indexes as $index) {
            echo "  • " . $index['name'] . " (" . implode(", ", $index['columns']) . ")\n";
        }
    }

    echo "\n" . str_repeat("═", 80) . "\n\n";
}

echo "✅ Análisis completado\n";
