<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtener la estructura de procesos_prenda
echo "=== COLUMNAS EN procesos_prenda ===\n";
$columns = DB::select("DESCRIBE procesos_prenda");

foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}
