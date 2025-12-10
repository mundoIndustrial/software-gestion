<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "🚀 Ejecutando migraciones pendientes de 2025_12_10...\n\n";

$migraciones = [
    '2025_12_10_create_prenda_fotos_cot_table' => 'database/migrations/2025_12_10_create_prenda_fotos_cot_table.php',
    '2025_12_10_create_prenda_tallas_cot_table' => 'database/migrations/2025_12_10_create_prenda_tallas_cot_table.php',
    '2025_12_10_create_prenda_telas_cot_table' => 'database/migrations/2025_12_10_create_prenda_telas_cot_table.php',
    '2025_12_10_create_prenda_variantes_cot_table' => 'database/migrations/2025_12_10_create_prenda_variantes_cot_table.php',
    '2025_12_10_create_prendas_cot_table' => 'database/migrations/2025_12_10_create_prendas_cot_table.php',
];

$batch = DB::table('migrations')->max('batch') ?? 0;
$batch++;
$ejecutadas = 0;
$errores = 0;
$errores_list = [];

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
            $errores++;
            $errores_list[] = "$nombre: Archivo no encontrado";
        }
    } catch (\Exception $e) {
        echo "❌ $nombre\n";
        echo "   ERROR: " . substr($e->getMessage(), 0, 100) . "...\n";
        $errores++;
        $errores_list[] = "$nombre: " . substr($e->getMessage(), 0, 80);
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESUMEN:\n";
echo "   ✅ Ejecutadas: $ejecutadas\n";
echo "   ❌ Errores: $errores\n";
echo "   📦 Batch: $batch\n";

if (!empty($errores_list)) {
    echo "\n📋 ERRORES DETALLADOS:\n";
    foreach ($errores_list as $error) {
        echo "   - $error\n";
    }
}

echo str_repeat("=", 70) . "\n";

// Mostrar tablas creadas
echo "\n📊 TABLAS CREADAS:\n";
$tablas = [
    'prendas_cot',
    'prenda_fotos_cot',
    'prenda_tallas_cot',
    'prenda_telas_cot',
    'prenda_variantes_cot'
];

foreach ($tablas as $tabla) {
    if (Schema::hasTable($tabla)) {
        $count = DB::table($tabla)->count();
        echo "   ✅ $tabla (registros: $count)\n";
    } else {
        echo "   ❌ $tabla - NO EXISTE\n";
    }
}

echo "\n✅ Proceso completado\n";
