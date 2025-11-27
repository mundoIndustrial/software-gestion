<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE prendas_pedido ===\n\n";

$columns = DB::select("DESCRIBE prendas_pedido");
foreach ($columns as $col) {
    echo "{$col->Field}: {$col->Type}\n";
}

echo "\n=== CAMPOS QUE PODRÍAN TENER DESCRIPCIÓN/VARIACIONES ===\n\n";

$descripcionFields = array_filter($columns, function($col) {
    $field = strtolower($col->Field);
    return strpos($field, 'descrip') !== false || 
           strpos($field, 'varia') !== false || 
           strpos($field, 'talla') !== false ||
           strpos($field, 'tamaño') !== false;
});

foreach ($descripcionFields as $col) {
    echo "  - {$col->Field}\n";
}

echo "\n=== EJEMPLO DE DATOS ===\n\n";

$ejemplo = DB::table('prendas_pedido')
    ->first();

if ($ejemplo) {
    echo "Primer registro:\n";
    foreach ((array)$ejemplo as $key => $value) {
        if ($value !== null && strlen((string)$value) < 100) {
            echo "  {$key}: {$value}\n";
        }
    }
}
?>
