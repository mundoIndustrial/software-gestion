<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🚀 Ejecutando migraciones de 2025_12_10...\n\n";

$migraciones = [
    '2025_12_10_mark_old_migration' => 'database/migrations/2025_12_10_mark_old_migration.php',
    '2025_12_10_000002_create_prenda_telas_table' => 'database/migrations/2025_12_10_000002_create_prenda_telas_table.php',
    '2025_12_10_000003_remove_telas_from_variantes_prenda' => 'database/migrations/2025_12_10_000003_remove_telas_from_variantes_prenda.php',
    '2025_12_10_000004_fix_prenda_telas_foreign_key' => 'database/migrations/2025_12_10_000004_fix_prenda_telas_foreign_key.php',
    '2025_12_10_000005_rename_prenda_telas_table' => 'database/migrations/2025_12_10_000005_rename_prenda_telas_table.php',
    '2025_12_10_000006_clean_prenda_telas_cotizacion' => 'database/migrations/2025_12_10_000006_clean_prenda_telas_cotizacion.php',
    '2025_12_10_create_genero_prendas_table' => 'database/migrations/2025_12_10_create_genero_prendas_table.php',
    '2025_12_10_create_prenda_fotos_cot_table' => 'database/migrations/2025_12_10_create_prenda_fotos_cot_table.php',
    '2025_12_10_create_prenda_tallas_cot_table' => 'database/migrations/2025_12_10_create_prenda_tallas_cot_table.php',
    '2025_12_10_create_prenda_telas_cot_table' => 'database/migrations/2025_12_10_create_prenda_telas_cot_table.php',
    '2025_12_10_create_prenda_variantes_cot_table' => 'database/migrations/2025_12_10_create_prenda_variantes_cot_table.php',
    '2025_12_10_create_prendas_cot_table' => 'database/migrations/2025_12_10_create_prendas_cot_table.php',
    '2025_12_10_create_tipo_prendas_table' => 'database/migrations/2025_12_10_create_tipo_prendas_table.php',
];

$batch = DB::table('migrations')->max('batch') ?? 0;
$batch++;
$ejecutadas = 0;
$errores = 0;

foreach ($migraciones as $nombre => $archivo) {
    try {
        // Verificar si ya fue ejecutada
        $existe = DB::table('migrations')
            ->where('migration', $nombre)
            ->exists();
        
        if ($existe) {
            echo "⏭️  $nombre - YA EJECUTADA\n";
            continue;
        }
        
        // Cargar y ejecutar migración
        if (file_exists($archivo)) {
            $migration = require $archivo;
            $migration->up();
            
            // Registrar en BD
            DB::table('migrations')->insert([
                'migration' => $nombre,
                'batch' => $batch
            ]);
            
            echo "✅ $nombre - EJECUTADA\n";
            $ejecutadas++;
        } else {
            echo "⚠️  $nombre - ARCHIVO NO ENCONTRADO\n";
        }
    } catch (\Exception $e) {
        echo "❌ $nombre - ERROR: " . substr($e->getMessage(), 0, 80) . "...\n";
        $errores++;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMEN:\n";
echo "   ✅ Ejecutadas: $ejecutadas\n";
echo "   ❌ Errores: $errores\n";
echo "   📦 Batch: $batch\n";
echo "=" . str_repeat("=", 59) . "\n";
