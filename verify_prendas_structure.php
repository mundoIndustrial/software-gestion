<?php

/**
 * Script de Verificación y Ejecución de Migraciones
 * Normalización de Prendas - 16 de Enero, 2026
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Necesitamos inicializar las facades correctamente
$app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "\n========================================\n";
echo "🔍 VERIFICACIÓN DE TABLA prendas_pedido\n";
echo "========================================\n\n";

try {
    // Conectar a BD
    $pdo = new \PDO('mysql:host=127.0.0.1;dbname=mundo_bd', 'root', '123456');
    
    // 1. Verificar columnas
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'prendas_pedido' AND TABLE_SCHEMA = 'mundo_bd'");
    $columns = $result->fetchAll(\PDO::FETCH_COLUMN);
    
    echo "📋 Columnas actuales:\n";
    foreach ($columns as $col) {
        echo "  - $col\n";
    }
    
    echo "\n========================================\n";
    echo "✅ ANÁLISIS DE SITUACIÓN\n";
    echo "========================================\n\n";
    
    $hasPedidoProduccionId = in_array('pedido_produccion_id', $columns);
    $hasNumeroPedido = in_array('numero_pedido', $columns);
    $hasColorId = in_array('color_id', $columns);
    $hasTelaid = in_array('tela_id', $columns);
    
    echo "✓ pedido_produccion_id existe: " . ($hasPedidoProduccionId ? "SÍ ✅" : "NO ❌") . "\n";
    echo "✓ numero_pedido existe: " . ($hasNumeroPedido ? "SÍ ✅" : "NO ❌") . "\n";
    echo "✓ color_id existe: " . ($hasColorId ? "SÍ ✅" : "NO ❌") . "\n";
    echo "✓ tela_id existe: " . ($hasTelaid ? "SÍ ✅" : "NO ❌") . "\n";
    
    // Contar registros
    $result = $pdo->query("SELECT COUNT(*) FROM prendas_pedido");
    $count = $result->fetchColumn();
    echo "\n📊 Registros en prendas_pedido: $count\n";
    
    // Verificar prenda_variantes
    $result = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_NAME = 'prenda_variantes' AND TABLE_SCHEMA = 'mundo_bd'");
    $tableExists = (int)$result->fetchColumn() > 0;
    echo "📊 Tabla prenda_variantes existe: " . ($tableExists ? "SÍ ✅" : "NO ❌") . "\n";
    
    echo "\n========================================\n";
    echo "🚀 PRÓXIMO PASO\n";
    echo "========================================\n\n";
    
    if (!$hasPedidoProduccionId) {
        echo "❌ ESTADO: Migraciones NO se han ejecutado\n";
        echo "\nEjecutar:\n";
        echo "  php artisan migrate\n";
    } elseif ($hasNumeroPedido) {
        echo "⚠️  ESTADO: pedido_produccion_id existe pero numero_pedido aún no se eliminó\n";
        echo "\nPosible que la migración esté incompleta.\n";
        echo "Ejecutar:\n";
        echo "  php artisan migrate\n";
    } else {
        echo "✅ ESTADO: Normalización completada\n";
        echo "\nPrenda_variantes: " . ($tableExists ? "✅ Existe" : "❌ NO existe") . "\n";
    }
    
    echo "\n========================================\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
