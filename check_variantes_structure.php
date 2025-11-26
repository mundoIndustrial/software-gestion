<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Estructura de tabla variantes_prenda ===\n";
$columns = Schema::getColumns('variantes_prenda');
foreach ($columns as $column) {
    echo "Campo: {$column['name']}\n";
    echo "  Tipo: {$column['type']}\n";
    echo "  Nullable: " . ($column['nullable'] ? 'SI' : 'NO') . "\n";
    echo "\n";
}
