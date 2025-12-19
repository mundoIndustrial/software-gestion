<?php

// Cargar autoload de Laravel
require __DIR__ . '/vendor/autoload.php';

// Crear la aplicación de Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Hacer que la aplicación esté disponible
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Usar la base de datos
use Illuminate\Support\Facades\DB;

DB::table('numero_secuencias')->insertOrIgnore([
    'tipo' => 'logo_pedidos',
    'siguiente' => 1,
    'created_at' => now(),
    'updated_at' => now()
]);

echo "✅ Secuencia logo_pedidos creada o ya existe\n";
?>
