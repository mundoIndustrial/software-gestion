<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Migraciones que ya existen en la BD pero están pendientes
$migracionesProblematicas = [
    '2024_11_10_000001_create_ordenes_asesores_table',
    '2024_11_10_000002_create_productos_pedido_table',
];

foreach ($migracionesProblematicas as $migracion) {
    // Verificar si ya está en la tabla migrations
    $existe = DB::table('migrations')->where('migration', $migracion)->exists();
    
    if (!$existe) {
        // Marcar como ejecutada
        DB::table('migrations')->insert([
            'migration' => $migracion,
            'batch' => 1
        ]);
        echo "✅ Marcada como ejecutada: $migracion\n";
    } else {
        echo "⚠️ Ya existe: $migracion\n";
    }
}

echo "\n✅ Migraciones problemáticas marcadas como ejecutadas\n";
