<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ANÁLISIS DE MIGRACIONES ===\n\n";

// Obtener todas las migraciones pendientes
$migracionesPendientes = DB::table('migrations')
    ->where('batch', 0)
    ->orWhereNull('batch')
    ->pluck('migration')
    ->toArray();

$migracionesProblematicas = [];

// Migraciones que intentan crear tablas que ya existen
$tablasExistentes = [
    '2024_11_10_000001_create_ordenes_asesores_table' => 'ordenes_asesores',
    '2024_11_10_000002_create_productos_pedido_table' => 'productos_pedido',
];

// Migraciones que intentan agregar columnas que ya existen
$columnasExistentes = [
    '2025_11_10_150547_add_detailed_fields_to_productos_pedido_table' => ['productos_pedido', ['tela', 'color', 'referencia']],
];

echo "📋 MIGRACIONES PENDIENTES: " . count($migracionesPendientes) . "\n\n";

// Verificar tablas que ya existen
echo "🔍 Verificando tablas existentes...\n";
foreach ($tablasExistentes as $migracion => $tabla) {
    if (Schema::hasTable($tabla)) {
        echo "⚠️  Tabla '$tabla' ya existe - Migración: $migracion\n";
        $migracionesProblematicas[] = $migracion;
    }
}

// Verificar columnas que ya existen
echo "\n🔍 Verificando columnas existentes...\n";
foreach ($columnasExistentes as $migracion => $info) {
    [$tabla, $columnas] = $info;
    if (Schema::hasTable($tabla)) {
        foreach ($columnas as $columna) {
            if (Schema::hasColumn($tabla, $columna)) {
                echo "⚠️  Columna '$columna' ya existe en tabla '$tabla' - Migración: $migracion\n";
                if (!in_array($migracion, $migracionesProblematicas)) {
                    $migracionesProblematicas[] = $migracion;
                }
            }
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "Total migraciones problemáticas: " . count($migracionesProblematicas) . "\n";

if (!empty($migracionesProblematicas)) {
    echo "\n✅ Marcando migraciones problemáticas como ejecutadas...\n";
    foreach ($migracionesProblematicas as $migracion) {
        $existe = DB::table('migrations')->where('migration', $migracion)->exists();
        if (!$existe) {
            DB::table('migrations')->insert([
                'migration' => $migracion,
                'batch' => 1
            ]);
            echo "✅ Marcada: $migracion\n";
        }
    }
}

echo "\n✅ Análisis completado\n";
