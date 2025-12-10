<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 VERIFICACIÓN DE ESTRUCTURA - prendas_cot y prenda_variantes_cot\n";
echo str_repeat("=", 80) . "\n\n";

// Verificar prendas_cot
echo "📋 TABLA: prendas_cot\n";
echo str_repeat("-", 80) . "\n";

if (Schema::hasTable('prendas_cot')) {
    $columns = DB::select("DESCRIBE prendas_cot");
    echo "✅ TABLA EXISTE\n\n";
    echo "COLUMNAS:\n";
    foreach ($columns as $col) {
        echo "   • {$col->Field}: {$col->Type}\n";
    }
} else {
    echo "❌ TABLA NO EXISTE\n";
}

echo "\n\n";

// Verificar prenda_variantes_cot
echo "📋 TABLA: prenda_variantes_cot\n";
echo str_repeat("-", 80) . "\n";

if (Schema::hasTable('prenda_variantes_cot')) {
    $columns = DB::select("DESCRIBE prenda_variantes_cot");
    echo "✅ TABLA EXISTE\n\n";
    echo "COLUMNAS:\n";
    foreach ($columns as $col) {
        echo "   • {$col->Field}: {$col->Type}\n";
    }
} else {
    echo "❌ TABLA NO EXISTE\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ VERIFICACIÓN COMPLETADA\n";
echo str_repeat("=", 80) . "\n\n";
