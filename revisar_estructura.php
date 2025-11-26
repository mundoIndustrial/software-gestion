<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ESTRUCTURA ACTUAL procesos_prenda ===\n\n";

$cols = Schema::getColumnListing('procesos_prenda');
echo "Columnas:\n";
foreach($cols as $col) {
    echo "  - $col\n";
}

echo "\n\nDetalles de columnas:\n";
$details = DB::select("DESCRIBE procesos_prenda");
foreach($details as $col) {
    echo "  {$col->Field}: {$col->Type}" . ($col->Key ? " ({$col->Key})" : "") . "\n";
}

echo "\n\nForeign Keys:\n";
$fks = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'procesos_prenda' AND REFERENCED_TABLE_NAME IS NOT NULL");
if(count($fks) > 0) {
    foreach($fks as $fk) {
        echo "  - {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}\n";
    }
} else {
    echo "  (ninguno)\n";
}
?>
