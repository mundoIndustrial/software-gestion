<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener el batch más alto
$max_batch = DB::selectOne('SELECT MAX(batch) as max_batch FROM migrations')?->max_batch ?? 0;

// Insertar la migración
DB::insert('INSERT INTO migrations (migration, batch) VALUES (?, ?)', [
    '2026_01_13_230854_update_registro_horas_huella_to_use_codigo_persona',
    $max_batch
]);

echo "✅ Migración marcada como ejecutada\n";
