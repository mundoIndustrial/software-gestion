<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Obtener la conexión a BD
$connection = $app['db']->connection();

// Ejecutar query para ver columnas
$columns = $connection->select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'materiales_orden_insumos' AND TABLE_SCHEMA = DATABASE()");

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║  COLUMNAS DE LA TABLA materiales_orden_insumos                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$nuevasColumnas = ['fecha_orden', 'fecha_pago', 'fecha_despacho', 'observaciones', 'dias_demora'];
$encontradas = [];

foreach ($columns as $col) {
    $colName = $col->COLUMN_NAME;
    
    if (in_array($colName, $nuevasColumnas)) {
        $encontradas[] = $colName;
        echo "✅ " . str_pad($colName, 25) . " | Tipo: " . str_pad($col->COLUMN_TYPE, 15) . " | Nulo: " . ($col->IS_NULLABLE === 'YES' ? 'SÍ' : 'NO') . "\n";
    }
}

echo "\n" . str_repeat("─", 66) . "\n";
echo "📊 RESUMEN:\n";
echo "   Columnas encontradas: " . count($encontradas) . " / " . count($nuevasColumnas) . "\n\n";

if (count($encontradas) === count($nuevasColumnas)) {
    echo "✅ ¡TODAS LAS COLUMNAS SE CREARON CORRECTAMENTE!\n\n";
} else {
    echo "⚠️  Columnas faltantes:\n";
    foreach ($nuevasColumnas as $col) {
        if (!in_array($col, $encontradas)) {
            echo "   ❌ " . $col . "\n";
        }
    }
    echo "\n";
}

echo str_repeat("═", 66) . "\n";
echo "✅ Verificación completada\n";
echo str_repeat("═", 66) . "\n\n";
