<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtener la estructura de la tabla
$columns = DB::select("DESCRIBE pedidos_produccion");

echo "=== ESTRUCTURA DE TABLA pedidos_produccion ===\n";
foreach ($columns as $column) {
    echo "Field: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Key: {$column->Key}, Default: {$column->Default}, Extra: {$column->Extra}\n";
}

// Obtener las keys foráneas
echo "\n=== FOREIGN KEYS ===\n";
$fks = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'pedidos_produccion' AND REFERENCED_TABLE_NAME IS NOT NULL");

foreach ($fks as $fk) {
    echo "FK: {$fk->CONSTRAINT_NAME}, Columna: {$fk->COLUMN_NAME}, Referencia: {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
}

// Verificar si existen las tablas que se referencian
echo "\n=== TABLAS EXISTENTES ===\n";
$tables = DB::select("SHOW TABLES");
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    echo "- $tableName\n";
}
