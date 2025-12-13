<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ÍNDICES VERIFICADOS EN pedidos_produccion ===\n\n";

$indexes = DB::select("SHOW INDEX FROM pedidos_produccion");

foreach ($indexes as $idx) {
    echo "✓ Índice: {$idx->Key_name} - Columna: {$idx->Column_name}\n";
}

echo "\n✅ Total de índices: " . count($indexes) . "\n";
