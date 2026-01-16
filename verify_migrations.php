<?php
/**
 * Script de Verificación y Migración
 * Verifica estructura actual y ejecuta migraciones
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Conectar a la BD
$app->make('Illuminate\Database\ConnectionResolver');

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     SCRIPT DE VERIFICACIÓN - NORMALIZACIÓN DE PRENDAS      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. VERIFICAR ESTRUCTURA ACTUAL
echo "1️⃣  VERIFICANDO ESTRUCTURA ACTUAL DE prendas_pedido...\n";

if (!Schema::hasTable('prendas_pedido')) {
    echo "❌ ERROR: Tabla prendas_pedido no existe\n";
    exit(1);
}

$columns = Schema::getColumnListing('prendas_pedido');
echo "✅ Columnas encontradas:\n";
foreach ($columns as $col) {
    echo "   - $col\n";
}

// 2. VERIFICAR DATOS
echo "\n2️⃣  VERIFICANDO DATOS EN prendas_pedido...\n";

$count = DB::table('prendas_pedido')->count();
echo "✅ Total de registros: $count\n";

if ($count > 0) {
    $sample = DB::table('prendas_pedido')->first();
    echo "\n📋 Muestra de datos:\n";
    foreach ((array)$sample as $key => $value) {
        $val = is_null($value) ? 'NULL' : (is_array($value) ? json_encode($value) : substr((string)$value, 0, 50));
        echo "   $key: $val\n";
    }
}

// 3. VERIFICAR FKs EXISTENTES
echo "\n3️⃣  VERIFICANDO FOREIGN KEYS EXISTENTES...\n";

$fks = DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'prendas_pedido'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

if (!empty($fks)) {
    echo "✅ FKs encontradas:\n";
    foreach ($fks as $fk) {
        echo "   - {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})\n";
    }
} else {
    echo "✅ No hay FKs\n";
}

// 4. VERIFICAR SI prenda_variantes EXISTE
echo "\n4️⃣  VERIFICANDO TABLA prenda_variantes...\n";

if (Schema::hasTable('prenda_variantes')) {
    $varCount = DB::table('prenda_variantes')->count();
    echo "✅ Tabla existe con $varCount registros\n";
} else {
    echo "ℹ️  Tabla prenda_variantes NO existe (será creada)\n";
}

// 5. VERIFICAR MIGRACIONES
echo "\n5️⃣  VERIFICANDO ESTADO DE MIGRACIONES...\n";

$migrations = DB::table('migrations')
    ->where('migration', 'like', '%2026_01_16%')
    ->get();

foreach ($migrations as $m) {
    $status = "✅ RAN";
    echo "   $status: {$m->migration}\n";
}

$pendingMigrations = [
    '2026_01_16_normalize_prendas_pedido',
    '2026_01_16_create_prenda_variantes_table',
    '2026_01_16_migrate_prenda_variantes_data'
];

echo "\n   📋 Migraciones pendientes a ejecutar:\n";
foreach ($pendingMigrations as $mig) {
    $exists = DB::table('migrations')
        ->where('migration', $mig)
        ->exists();
    
    $status = $exists ? "✅ YA EJECUTADA" : "⏳ PENDIENTE";
    echo "      $status: $mig\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    VERIFICACIÓN COMPLETADA                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "▶️  Para ejecutar migraciones: php artisan migrate\n\n";
