<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Check tables
$tables = DB::select("SHOW TABLES");
echo "Tablas disponibles:\n";
foreach ($tables as $table) {
    $tableName = (array)$table;
    echo "  - " . reset($tableName) . "\n";
}
?>
