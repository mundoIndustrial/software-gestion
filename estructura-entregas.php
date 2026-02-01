#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$entregaTables = [
    'entrega_bodega_corte',
    'entrega_pedido_corte',
    'entrega_prenda_pedido',
    'entregas_bodega_costura',
    'entregas_pedido_costura'
];

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📋 ESTRUCTURA DE TABLAS DE ENTREGAS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach($entregaTables as $table) {
    echo "┌─ TABLE: $table\n";
    echo "│\n";
    
    $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA 
                          FROM information_schema.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'
                          ORDER BY ORDINAL_POSITION");
    
    foreach($columns as $col) {
        $null = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
        $key = $col->COLUMN_KEY ? "[{$col->COLUMN_KEY}]" : '';
        $extra = $col->EXTRA ? " {$col->EXTRA}" : '';
        echo "│  • {$col->COLUMN_NAME} : {$col->COLUMN_TYPE} | $null $key$extra\n";
    }
    
    $count = DB::table($table)->count();
    echo "│\n";
    echo "│  Registros: $count\n";
    echo "└──────────────────────────────────────────────────────────\n\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Análisis completado\n";
echo "═══════════════════════════════════════════════════════════════\n";
