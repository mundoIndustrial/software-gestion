<?php

// Script para verificar las columnas creadas

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$db = $app->make(\Illuminate\Database\DatabaseManager::class);

// Obtener las columnas de la tabla
$columns = $db->select("DESCRIBE materiales_orden_insumos");

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  VERIFICACIÓN DE COLUMNAS - materiales_orden_insumos           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$nuevasColumnas = ['fecha_orden', 'fecha_pago', 'fecha_despacho', 'observaciones', 'dias_demora'];
$columnasEncontradas = [];

foreach ($columns as $column) {
    $columnName = $column->Field;
    
    if (in_array($columnName, $nuevasColumnas)) {
        $columnasEncontradas[] = $columnName;
        echo "✅ " . str_pad($columnName, 25) . " | Tipo: " . $column->Type . " | Nulo: " . ($column->Null === 'YES' ? 'SÍ' : 'NO') . "\n";
    }
}

echo "\n" . str_repeat("─", 66) . "\n";
echo "📊 RESUMEN:\n";
echo "   Total de nuevas columnas encontradas: " . count($columnasEncontradas) . " / " . count($nuevasColumnas) . "\n";

if (count($columnasEncontradas) === count($nuevasColumnas)) {
    echo "\n✅ ¡MIGRACIÓN EJECUTADA CORRECTAMENTE!\n";
    echo "   Todas las columnas se crearon exitosamente.\n";
} else {
    echo "\n⚠️  Columnas faltantes:\n";
    foreach ($nuevasColumnas as $col) {
        if (!in_array($col, $columnasEncontradas)) {
            echo "   ❌ " . $col . "\n";
        }
    }
}

echo "\n" . str_repeat("─", 66) . "\n";
echo "\n📋 TODAS LAS COLUMNAS DE LA TABLA:\n\n";

foreach ($columns as $column) {
    echo "   • " . str_pad($column->Field, 30) . " | " . str_pad($column->Type, 20) . " | Nulo: " . str_pad($column->Null, 3) . "\n";
}

echo "\n" . str_repeat("═", 66) . "\n";
echo "✅ Verificación completada\n";
echo str_repeat("═", 66) . "\n\n";
