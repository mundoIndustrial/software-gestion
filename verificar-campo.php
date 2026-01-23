<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver información de la columna
$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='pedidos_produccion' AND COLUMN_NAME='estado'");

echo "Información de la columna estado:\n";
foreach ($columns as $col) {
    echo "  - Type: " . $col->COLUMN_TYPE . "\n";
    echo "  - Nullable: " . $col->IS_NULLABLE . "\n";
    echo "  - Default: " . $col->COLUMN_DEFAULT . "\n";
}
