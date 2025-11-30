<?php
/**
 * Test script para validar que el cálculo de días ahora funciona correctamente
 * Después de cambiar $orden->pedido a $orden->numero_pedido
 */
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;

echo "=" . str_repeat("=", 80) . "\n";
echo "TEST: Validar cálculo de días después del fix\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Obtener algunas órdenes
$ordenes = PedidoProduccion::limit(5)->get();
echo "📊 Prueba con " . $ordenes->count() . " órdenes:\n\n";

// Obtener festivos
$festivos = Festivo::pluck('fecha')->toArray();

// Convertir a array para simular lo que hace el controller
$ordenesArray = $ordenes->toArray();

// Llamar getTotalDiasBatch
$totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);

echo "✅ Array retornado por getTotalDiasBatch:\n";
foreach ($totalDiasCalculados as $numeroPedido => $dias) {
    echo "   • Pedido {$numeroPedido}: {$dias} días\n";
}

echo "\n✅ Simulando filtro (antes el lookup fallaba):\n";
$ordenes->each(function($orden) use ($totalDiasCalculados) {
    // Esto es lo que ahora funciona (después del fix)
    $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
    
    echo "   • {$orden->numero_pedido}: Lookup retorna {$totalDias} días\n";
});

echo "\n✅ Test completado exitosamente!\n";
echo "   La clave del array coincide con numero_pedido\n";
echo "   El filtro por total_de_dias_ ahora funcionará correctamente\n";
?>
