<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Ejecutar la migración específica
$kernel->call('migrate', [
    '--path' => 'database/migrations/2026_01_16_migrate_prenda_variantes_data.php'
]);
