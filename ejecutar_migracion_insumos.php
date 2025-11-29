<?php

// Script para ejecutar solo la migración de insumos

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Ejecutar la migración específica
$status = $kernel->call('migrate', [
    '--path' => 'database/migrations/2025_11_29_000002_add_columns_to_materiales_orden_insumos.php',
    '--force' => true,
]);

echo "\n✅ Migración ejecutada con código: " . $status . "\n";

exit($status);
