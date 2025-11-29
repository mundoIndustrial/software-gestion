<?php

// Script para verificar los campos creados

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Usar el kernel de Laravel
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Ejecutar comando artisan para verificar
$status = $kernel->call('db:show', [
    '--table' => 'materiales_orden_insumos',
]);

exit($status);
