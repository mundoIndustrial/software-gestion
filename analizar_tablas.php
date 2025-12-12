<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "\n=== ANÁLISIS DE TABLAS ===\n\n";

// 1. Analizar estructura de pedido_produccion
echo "1. TABLA: pedido_produccion\n";
echo "----------------------------\n";
$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'");
foreach ($columns as $col) {
    echo "  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})\n";
}

$sample = DB::table('pedidos_produccion')->first();
echo "\nPrimer registro:\n";
if ($sample) {
    foreach ((array)$sample as $key => $value) {
        echo "  $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . "\n";
    }
}

// Valores únicos en área
echo "\nValores de 'area':\n";
$areas = DB::table('pedidos_produccion')->distinct()->pluck('area')->all();
foreach ($areas as $area) {
    $count = DB::table('pedidos_produccion')->where('area', $area)->count();
    echo "  - '$area': $count registros\n";
}

echo "\n\n2. TABLA: registros_por_orden_bodega\n";
echo "-------------------------------------\n";
$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registros_por_orden_bodega'");
foreach ($columns as $col) {
    echo "  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})\n";
}

$sample = DB::table('registros_por_orden_bodega')->first();
echo "\nPrimer registro:\n";
if ($sample) {
    foreach ((array)$sample as $key => $value) {
        echo "  $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . "\n";
    }
}

echo "\nTotal registros: " . DB::table('registros_por_orden_bodega')->count() . "\n";

echo "\n\n3. TABLA: proceso_prendas\n";
echo "-------------------------\n";
$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proceso_prendas'");
foreach ($columns as $col) {
    echo "  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})\n";
}

$sample = DB::table('proceso_prendas')->first();
echo "\nPrimer registro:\n";
if ($sample) {
    foreach ((array)$sample as $key => $value) {
        echo "  $key: " . (is_null($value) ? 'NULL' : substr($value, 0, 50)) . "\n";
    }
}

// Valores únicos en área
echo "\nValores de 'area':\n";
$areas = DB::table('proceso_prendas')->distinct()->pluck('area')->all();
foreach ($areas as $area) {
    if (!is_null($area)) {
        $count = DB::table('proceso_prendas')->where('area', $area)->count();
        echo "  - '$area': $count registros\n";
    }
}

echo "\nTotal registros: " . DB::table('proceso_prendas')->count() . "\n";

echo "\n=== FIN ANÁLISIS ===\n\n";
