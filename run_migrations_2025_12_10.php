<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

$migrations = [
    '2025_12_10_mark_old_migration',
    '2025_12_10_000002_create_prenda_telas_table',
    '2025_12_10_000003_remove_telas_from_variantes_prenda',
    '2025_12_10_000004_fix_prenda_telas_foreign_key',
    '2025_12_10_000005_rename_prenda_telas_table',
    '2025_12_10_000006_clean_prenda_telas_cotizacion',
    '2025_12_10_create_genero_prendas_table',
    '2025_12_10_create_prenda_fotos_cot_table',
    '2025_12_10_create_prenda_tallas_cot_table',
    '2025_12_10_create_prenda_telas_cot_table',
    '2025_12_10_create_prenda_variantes_cot_table',
    '2025_12_10_create_prendas_cot_table',
    '2025_12_10_create_tipo_prendas_table',
];

echo "Ejecutando migraciones de 2025_12_10...\n\n";

foreach ($migrations as $migration) {
    try {
        // Verificar si ya fue ejecutada
        $exists = DB::table('migrations')
            ->where('migration', $migration)
            ->exists();
        
        if ($exists) {
            echo "✅ $migration - YA EJECUTADA\n";
            continue;
        }
        
        // Ejecutar migración
        Artisan::call('migrate:refresh', ['--step' => true, '--force' => true]);
        echo "✅ $migration - EJECUTADA\n";
    } catch (\Exception $e) {
        echo "❌ $migration - ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Proceso completado\n";
