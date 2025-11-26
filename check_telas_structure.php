<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "=== Estructura de tabla telas_prenda ===\n";
$columns = Schema::getColumns('telas_prenda');
foreach ($columns as $column) {
    echo "Campo: {$column['name']} ({$column['type']})\n";
}
