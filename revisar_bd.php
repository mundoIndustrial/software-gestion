<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 REVISIÓN COMPLETA DE LA BASE DE DATOS\n";
echo str_repeat("=", 80) . "\n\n";

// Tablas a revisar
$tablasRevisar = [
    'prendas_cot',
    'prenda_fotos_cot',
    'prenda_telas_cot',
    'prenda_tallas_cot',
    'prenda_variantes_cot',
    'cotizaciones',
];

foreach ($tablasRevisar as $tabla) {
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "📋 TABLA: $tabla\n";
    echo str_repeat("-", 80) . "\n";
    
    if (!Schema::hasTable($tabla)) {
        echo "❌ LA TABLA NO EXISTE\n";
        continue;
    }
    
    echo "✅ LA TABLA EXISTE\n\n";
    
    // Obtener columnas
    $columns = DB::select("DESCRIBE $tabla");
    
    echo "📌 COLUMNAS:\n";
    foreach ($columns as $col) {
        $nullable = $col->Null === 'YES' ? '(nullable)' : '(required)';
        $key = $col->Key ? " [KEY: {$col->Key}]" : '';
        $default = $col->Default ? " [DEFAULT: {$col->Default}]" : '';
        echo "   • {$col->Field}: {$col->Type} {$nullable}{$key}{$default}\n";
    }
    
    // Obtener foreign keys
    $fks = DB::select("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$tabla' AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if (!empty($fks)) {
        echo "\n🔗 FOREIGN KEYS (RELACIONES):\n";
        foreach ($fks as $fk) {
            echo "   • {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
        }
    }
    
    // Contar registros
    $count = DB::table($tabla)->count();
    echo "\n📊 REGISTROS: $count\n";
}

echo "\n\n" . str_repeat("=", 80) . "\n";
echo "🔍 RESUMEN DE RELACIONES\n";
echo str_repeat("=", 80) . "\n\n";

$relaciones = [
    'cotizaciones' => [
        'prendas_cot' => 'cotizacion_id → id',
    ],
    'prendas_cot' => [
        'prenda_fotos_cot' => 'prenda_cot_id → id',
        'prenda_telas_cot' => 'prenda_cot_id → id',
        'prenda_tallas_cot' => 'prenda_cot_id → id',
        'prenda_variantes_cot' => 'prenda_cot_id → id',
    ],
];

foreach ($relaciones as $tabla => $rels) {
    echo "📦 $tabla\n";
    foreach ($rels as $tablaRel => $relacion) {
        echo "   └─ $tablaRel ($relacion)\n";
    }
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "✅ REVISIÓN COMPLETADA\n";
echo str_repeat("=", 80) . "\n\n";
