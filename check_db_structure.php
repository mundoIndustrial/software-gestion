<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Tablas relevantes para el análisis
$tablas = [
    'pedidos_produccion',
    'prendas_pedido',
    'prendas_ped',
    'prenda_fotos_ped',
    'prenda_telas_ped',
    'prenda_tela_fotos_ped',
    'prenda_tallas_ped',
    'prenda_variantes_ped',
    'logo_ped',
    'logo_fotos_ped',
    'prendas_cot',
    'prenda_fotos_cot',
    'prenda_telas_cot',
    'prenda_tela_fotos_cot',
];

echo "=== ESTRUCTURA DE TABLAS DE BASE DE DATOS ===\n\n";

foreach ($tablas as $tabla) {
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║ TABLA: $tabla\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n";
    
    try {
        // Columnas
        $columns = DB::select("DESCRIBE $tabla");
        
        if (empty($columns)) {
            echo "⚠️  La tabla NO existe en la BD\n\n";
            continue;
        }
        
        echo "📋 COLUMNAS:\n";
        echo str_pad("Campo", 25) . " | " . str_pad("Tipo", 25) . " | Nulo | Clave\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($columns as $col) {
            $campo = $col->Field;
            $tipo = $col->Type;
            $nulo = $col->Null === 'YES' ? 'SÍ' : 'NO';
            $clave = $col->Key ?: '-';
            
            echo str_pad($campo, 25) . " | " . str_pad($tipo, 25) . " | " . str_pad($nulo, 4) . " | $clave\n";
        }
        
        // Foreign Keys
        echo "\n🔗 RELACIONES (FOREIGN KEYS):\n";
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = '$tabla' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (!empty($fks)) {
            foreach ($fks as $fk) {
                echo "   • {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        } else {
            echo "   (Sin foreign keys definidas)\n";
        }
        
        // Índices
        echo "\n📑 ÍNDICES:\n";
        $indexes = DB::select("SHOW INDEXES FROM $tabla");
        
        $indexMap = [];
        foreach ($indexes as $idx) {
            if (!isset($indexMap[$idx->Key_name])) {
                $indexMap[$idx->Key_name] = [];
            }
            $indexMap[$idx->Key_name][] = $idx->Column_name;
        }
        
        foreach ($indexMap as $name => $columns) {
            echo "   • $name (" . implode(", ", $columns) . ")\n";
        }
        
        // Recuento de registros
        echo "\n📊 REGISTROS: ";
        $count = DB::table($tabla)->count();
        echo "$count\n\n";
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== RESUMEN DE RELACIONES ACTUALES ===\n\n";

// Analizar relaciones
$relaciones = DB::select("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME IN ('pedidos_produccion', 'prendas_pedido', 'prendas_ped', 'logo_ped')
    ORDER BY TABLE_NAME
");

foreach ($relaciones as $rel) {
    echo "→ {$rel->TABLE_NAME}.{$rel->COLUMN_NAME} apunta a {$rel->REFERENCED_TABLE_NAME}.{$rel->REFERENCED_COLUMN_NAME}\n";
}

echo "\n=== PROBLEMAS DETECTADOS ===\n\n";

// Revisar si hay conflicto entre prendas_pedido y prendas_ped
$prendas_pedido_exists = DB::select("SHOW TABLES LIKE 'prendas_pedido'");
$prendas_ped_exists = DB::select("SHOW TABLES LIKE 'prendas_ped'");

if ($prendas_pedido_exists && $prendas_ped_exists) {
    echo "⚠️  CONFLICTO: Existen AMBAS tablas:\n";
    echo "   - prendas_pedido (tabla antigua)\n";
    echo "   - prendas_ped (tabla nueva)\n";
    echo "   → DECISIÓN NECESARIA: ¿Cuál usar? ¿Consolidar?\n\n";
}

// Revisar si prendas_ped/prendas_pedido tiene relaciones con fotos
$prenda_table = $prendas_ped_exists ? 'prendas_ped' : 'prendas_pedido';
$foto_tables = DB::select("SHOW TABLES LIKE 'prenda_fotos_ped' OR LIKE 'prenda_fotos_pedido'");

if (!empty($foto_tables)) {
    echo "✓ Existen tablas de fotos para prendas\n\n";
} else {
    echo "⚠️  NO existen tablas de fotos vinculadas a prendas_ped\n\n";
}

// Revisar logo
$logo_exists = DB::select("SHOW TABLES LIKE 'logo_ped'");
$logo_fotos_exists = DB::select("SHOW TABLES LIKE 'logo_fotos_ped'");

if ($logo_exists && !$logo_fotos_exists) {
    echo "⚠️  PROBLEMA: logo_ped existe pero logo_fotos_ped NO existe\n\n";
}

// Revisar telas
$telas_exists = DB::select("SHOW TABLES LIKE 'prenda_telas_ped'");
$telas_fotos_exists = DB::select("SHOW TABLES LIKE 'prenda_tela_fotos_ped'");

if ($telas_exists && !$telas_fotos_exists) {
    echo "⚠️  PROBLEMA: prenda_telas_ped existe pero prenda_tela_fotos_ped NO existe\n\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
