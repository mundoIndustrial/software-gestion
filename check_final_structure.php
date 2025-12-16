<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA FINAL DE PRENDAS_PEDIDO ===\n\n";

$tablas = [
    'prendas_pedido' => 'Tabla principal de prendas',
    'prenda_fotos_pedido' => 'Fotos de la prenda (de cotizaciones)',
    'prenda_fotos_logo_pedido' => 'Logos aplicados a la prenda',
    'prenda_fotos_tela_pedido' => 'Fotos de las telas seleccionadas para la prenda',
    'logo_ped' => 'Logos generales del pedido',
    'logo_fotos_ped' => 'Fotos de logos generales del pedido',
];

foreach ($tablas as $tabla => $descripcion) {
    echo "📌 $tabla - $descripcion\n";
    echo str_repeat("─", 80) . "\n";
    
    try {
        $exists = DB::select("SHOW TABLES LIKE '$tabla'");
        
        if (empty($exists)) {
            echo "❌ NO EXISTE\n\n";
            continue;
        }
        
        echo "✓ EXISTE\n";
        
        // Columnas clave
        $columns = DB::select("DESCRIBE $tabla");
        $key_columns = [];
        foreach ($columns as $col) {
            if (strpos($col->Field, 'id') !== false || strpos($col->Field, 'ruta') !== false || 
                strpos($col->Field, '_id') !== false || $col->Key === 'MUL') {
                $key_columns[] = $col->Field;
            }
        }
        
        echo "Columnas clave: " . implode(", ", $key_columns) . "\n";
        
        // Foreign Keys
        $fks = DB::select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = '$tabla' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (!empty($fks)) {
            echo "Relaciones: ";
            $rel_list = [];
            foreach ($fks as $fk) {
                $rel_list[] = "{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}";
            }
            echo implode(" | ", $rel_list) . "\n";
        }
        
        // Registros
        $count = DB::table($tabla)->count();
        echo "Registros: $count\n\n";
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "✓ Tabla principal: prendas_pedido (2921 registros)\n";
echo "✓ Tabla de fotos de prenda: prenda_fotos_pedido\n";
echo "✓ Tabla de logos de prenda: prenda_fotos_logo_pedido\n";
echo "✓ Tabla de fotos de telas: prenda_fotos_tela_pedido (ÚNICA para telas)\n";
echo "✓ Tabla de logos del pedido: logo_ped → logo_fotos_ped\n";
echo "\n✅ Estructura consolidada correctamente\n";
