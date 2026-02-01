#!/usr/bin/env php
<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔍 VALIDACIÓN DE ESTRUCTURA vs MODELOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Validar EntregaPedidoCostura
echo "📌 Validando: EntregaPedidoCostura\n";
echo "   Tabla: entregas_pedido_costura\n";
echo "   Clase: App\\Models\\EntregaPedidoCostura\n\n";

try {
    $model = new \App\Models\EntregaPedidoCostura();
    echo "   ✓ Modelo cargado correctamente\n";
    echo "   Columnas del fillable: " . json_encode($model->getFillable()) . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error al cargar modelo: " . $e->getMessage() . "\n\n";
}

// Obtener estructura real de la tabla
$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
                      FROM information_schema.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'entregas_pedido_costura'
                      ORDER BY ORDINAL_POSITION");

echo "   Columnas reales en BD:\n";
foreach($columns as $col) {
    $null = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
    echo "     • {$col->COLUMN_NAME} ({$col->COLUMN_TYPE}) - Nullable: $null\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📌 Validando: EntregaPrendaPedido\n";
echo "   Tabla: entrega_prenda_pedido\n";
echo "   Clase: App\\Models\\EntregaPrendaPedido\n\n";

try {
    $model = new \App\Models\EntregaPrendaPedido();
    echo "   ✓ Modelo cargado correctamente\n";
    echo "   Columnas del fillable: " . json_encode($model->getFillable()) . "\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error al cargar modelo: " . $e->getMessage() . "\n\n";
}

$columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
                      FROM information_schema.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'entrega_prenda_pedido'
                      ORDER BY ORDINAL_POSITION");

echo "   Columnas reales en BD:\n";
foreach($columns as $col) {
    $null = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
    echo "     • {$col->COLUMN_NAME} ({$col->COLUMN_TYPE}) - Nullable: $null\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Validación completada\n";
echo "═══════════════════════════════════════════════════════════════\n";
