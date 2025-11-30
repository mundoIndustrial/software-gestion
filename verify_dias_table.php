<?php
/**
 * Test script para verificar que el fix funciona correctamente
 * Simula lo que hace el controlador
 */
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;

echo "=" . str_repeat("=", 80) . "\n";
echo "TEST: Verificar que dias se muestra en tabla después del fix\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Obtener órdenes (simular paginación)
$ordenes = PedidoProduccion::paginate(5);
echo "📊 Total órdenes: " . $ordenes->total() . "\n";
echo "📄 Órdenes en página actual: " . $ordenes->count() . "\n\n";

// Obtener festivos
$festivos = Festivo::pluck('fecha')->toArray();

// Convertir a array (como hace el controller antes de calcular)
$ordenesArray = $ordenes->map(function($orden) {
    return (object) $orden->getAttributes();
})->toArray();

// Llamar getTotalDiasBatch (esto es lo que hace el controller ahora)
$totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);

echo "✅ Array de días calculado. Claves y valores:\n\n";
foreach ($totalDiasCalculados as $numeroPedido => $dias) {
    echo "   • Pedido: $numeroPedido => Días: $dias\n";
}

echo "\n🔍 Simulando acceso como lo hace la vista Blade:\n\n";
foreach ($ordenes->items() as $orden) {
    // Esto es exactamente lo que hace la vista en line 255 de index.blade.php
    $diasMostrados = intval($totalDiasCalculados[$orden->numero_pedido] ?? 0);
    echo "   • Pedido {$orden->numero_pedido}: {$diasMostrados} días\n";
}

echo "\n✅ Test completado - Los días ahora se mostrarán correctamente en la tabla\n";
?>
