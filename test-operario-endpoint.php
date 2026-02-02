<?php

/**
 * TEST: Comparar endpoints
 * - GET /pedidos-public/3/recibos-datos (Referencia)
 * - GET /api/operario/pedido/45807 (Nuevo)
 * 
 * Ambos deben retornar la misma información
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST ENDPOINTS ===\n\n";

// Obtener ID del pedido desde número
$pedido = \App\Models\PedidoProduccion::where('numero_pedido', 45807)->first();
if (!$pedido) {
    echo "❌ Pedido 45807 no encontrado\n";
    exit;
}

echo "✅ Pedido encontrado:\n";
echo "   - ID: {$pedido->id}\n";
echo "   - Número: {$pedido->numero_pedido}\n";
echo "   - Cliente: {$pedido->cliente}\n\n";

// TEST 1: Obtener datos con /pedidos-public/{id}/recibos-datos (referencia)
echo "1️⃣  ENDPOINT REFERENCIA: /pedidos-public/{$pedido->id}/recibos-datos\n";
$response1 = (new \App\Http\Controllers\Api_temp\PedidoController(
    app(\App\Application\Pedidos\UseCases\CrearPedidoUseCase::class),
    app(\App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase::class),
    app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class),
    app(\App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase::class),
    app(\App\Application\Pedidos\UseCases\CancelarPedidoUseCase::class),
    app(\App\Domain\Pedidos\Repositories\PedidoRepository::class)
))->obtenerDetalleCompleto($pedido->id);

$data1 = json_decode($response1->getContent(), true);
echo "   Status: " . $response1->getStatusCode() . "\n";
echo "   Success: " . ($data1['success'] ? '✅' : '❌') . "\n";
echo "   Prendas: " . count($data1['data']['prendas'] ?? []) . "\n";
if (!empty($data1['data']['prendas'])) {
    $prenda = $data1['data']['prendas'][0];
    echo "   - Primera prenda: " . ($prenda['nombre'] ?? 'N/A') . "\n";
    echo "   - Recibos: " . json_encode($prenda['recibos'] ?? []) . "\n";
}

echo "\n2️⃣  ENDPOINT NUEVO: /api/operario/pedido/45807\n";

// TEST 2: Obtener datos con nuevo endpoint
$response2 = (new \App\Infrastructure\Http\Controllers\Operario\OperarioController(
    app(\App\Application\Operario\Services\ObtenerPedidosOperarioService::class),
    app(\App\Domain\Operario\Repositories\OperarioRepository::class),
    app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class)
))->getPedidoData(45807);

$data2 = json_decode($response2->getContent(), true);
echo "   Status: " . $response2->getStatusCode() . "\n";
echo "   Success: " . ($data2['success'] ? '✅' : '❌') . "\n";
echo "   Prendas: " . count($data2['data']['prendas'] ?? []) . "\n";
if (!empty($data2['data']['prendas'])) {
    $prenda = $data2['data']['prendas'][0];
    echo "   - Primera prenda: " . ($prenda['nombre'] ?? 'N/A') . "\n";
    echo "   - Recibos: " . json_encode($prenda['recibos'] ?? []) . "\n";
}

echo "\n3️⃣  COMPARACIÓN\n";
$match = json_encode($data1['data']) === json_encode($data2['data']);
echo ($match ? "✅" : "❌") . " Los datos coinciden: " . ($match ? 'SÍ' : 'NO') . "\n";

if (!$match) {
    echo "\nDiferencias encontradas:\n";
    $diff1 = array_diff_key($data1['data'], $data2['data']);
    $diff2 = array_diff_key($data2['data'], $data1['data']);
    
    if (!empty($diff1)) {
        echo "En endpoint 1 pero no en 2: " . implode(", ", array_keys($diff1)) . "\n";
    }
    if (!empty($diff2)) {
        echo "En endpoint 2 pero no en 1: " . implode(", ", array_keys($diff2)) . "\n";
    }
}

echo "\n✅ TEST COMPLETADO\n";
