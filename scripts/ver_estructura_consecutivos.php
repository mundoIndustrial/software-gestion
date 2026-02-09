<?php

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "ESTRUCTURA DE LA TABLA consecutivos_recibos_pedidos\n";
echo str_repeat("=", 80) . "\n\n";

// Obtener la estructura de la tabla
$columns = DB::select("SHOW COLUMNS FROM consecutivos_recibos_pedidos");

echo "COLUMNAS:\n";
echo str_repeat("-", 80) . "\n";
foreach ($columns as $column) {
    echo "Campo: {$column->Field}\n";
    echo "  Tipo: {$column->Type}\n";
    echo "  Null: {$column->Null}\n";
    echo "  Key: {$column->Key}\n";
    echo "  Default: " . ($column->Default ?? 'NULL') . "\n";
    echo "  Extra: {$column->Extra}\n\n";
}

// Obtener los índices
echo "\nÍNDICES Y RESTRICCIONES:\n";
echo str_repeat("-", 80) . "\n";
$indexes = DB::select("SHOW INDEXES FROM consecutivos_recibos_pedidos");

$indexGroups = [];
foreach ($indexes as $idx) {
    $indexGroups[$idx->Key_name][] = $idx;
}

foreach ($indexGroups as $keyName => $columns) {
    $unique = $columns[0]->Non_unique == 0 ? 'UNIQUE' : 'INDEX';
    $cols = array_map(fn($c) => $c->Column_name, $columns);
    echo "{$unique}: {$keyName} (" . implode(', ', $cols) . ")\n";
}
