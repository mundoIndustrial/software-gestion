<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE PRENDAS POR PEDIDO ===\n\n";

// 1. Obtener todos los pedidos con numero_pedido
$pedidosConNumero = PedidoProduccion::whereNotNull('numero_pedido')
    ->orderBy('numero_pedido', 'asc')
    ->get();

echo "📊 Pedidos con numero_pedido: {$pedidosConNumero->count()}\n\n";

foreach ($pedidosConNumero as $pedido) {
    echo str_repeat("=", 60) . "\n";
    echo "📋 Pedido #{$pedido->numero_pedido} | Cliente: {$pedido->cliente} | Estado: {$pedido->estado}\n";
    echo str_repeat("-", 60) . "\n";
    
    // 2. Contar prendas asociadas
    $prendasCount = $pedido->prendas()->count();
    echo "👕 Prendas asociadas: {$prendasCount}\n";
    
    if ($prendasCount > 0) {
        echo "   Detalles de prendas:\n";
        foreach ($pedido->prendas as $prenda) {
            echo "   - ID: {$prenda->id} | Nombre: {$prenda->nombre_prenda} | De bodega: " . ($prenda->de_bodega ? 'SÍ' : 'NO') . "\n";
        }
    }
    
    // 3. Verificar si es PENDIENTE_INSUMOS y tiene prendas de bodega
    if ($pedido->estado === 'PENDIENTE_INSUMOS') {
        $prendasBodega = $pedido->prendas()->where('de_bodega', true)->count();
        echo "🏪 Prendas de bodega (para PENDIENTE_INSUMOS): {$prendasBodega}\n";
        
        if ($prendasBodega === 0) {
            echo "⚠️  ESTE PEDIDO NO CUMPLE LA CONDICIÓN PENDIENTE_INSUMOS\n";
        }
    }
    
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "🔍 VERIFICACIÓN DE CONDICIÓN PENDIENTE_INSUMOS:\n";
echo str_repeat("-", 60) . "\n";

// 4. Simular la consulta exacta que está en el código
$pendientesInsumos = PedidoProduccion::whereNotNull('numero_pedido')
    ->where('estado', 'PENDIENTE_INSUMOS')
    ->whereHas('prendas', function ($prendasQuery) {
        $prendasQuery->where('de_bodega', true);
    })
    ->get();

echo "📈 Pedidos PENDIENTE_INSUMOS con prendas de bodega: {$pendientesInsumos->count()}\n";

foreach ($pendientesInsumos as $pedido) {
    echo "✅ Pedido #{$pedido->numero_pedido} - {$pedido->cliente}\n";
}

// 5. Verificar qué pedidos PENDIENTE_INSUMOS no cumplen la condición
$pendientesInsumosTodos = PedidoProduccion::whereNotNull('numero_pedido')
    ->where('estado', 'PENDIENTE_INSUMOS')
    ->get();

$noCumplen = $pendientesInsumosTodos->diff($pendientesInsumos);

if ($noCumplen->count() > 0) {
    echo "\n❌ Pedidos PENDIENTE_INSUMOS que NO cumplen la condición (sin prendas de bodega):\n";
    foreach ($noCumplen as $pedido) {
        echo "❌ Pedido #{$pedido->numero_pedido} - {$pedido->cliente}\n";
        $prendasNoBodega = $pedido->prendas()->where('de_bodega', false)->get();
        foreach ($prendasNoBodega as $prenda) {
            echo "   - Prenda: {$prenda->nombre_prenda} (de_bodega: NO)\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🏁 FIN DE VERIFICACIÓN\n";
