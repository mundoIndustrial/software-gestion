<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\LogoPedido;

echo "\n========================================\n";
echo "  PRUEBA: NÚMERO DE PEDIDO MOSTRABLE\n";
echo "========================================\n\n";

// 1. Obtener un pedido NORMAL (sin LOGO)
echo "1️⃣  Buscando pedido NORMAL (sin LOGO)...\n";
$pedidoNormal = PedidoProduccion::whereDoesntHave('logoPedidos')->first();

if ($pedidoNormal) {
    echo "   ✅ Pedido encontrado: ID " . $pedidoNormal->id . "\n";
    echo "   Número en BD: " . $pedidoNormal->numero_pedido . "\n";
    echo "   Número mostrable: " . $pedidoNormal->numero_pedido_mostrable . "\n";
    echo "   ¿Es LOGO?: " . ($pedidoNormal->esLogo() ? 'SÍ' : 'NO') . "\n";
} else {
    echo "   ⚠️  No hay pedidos normales\n";
}

// 2. Obtener un pedido LOGO (si existe)
echo "\n2️⃣  Buscando pedido LOGO...\n";
$pedidoLogo = PedidoProduccion::whereHas('logoPedidos')->first();

if ($pedidoLogo) {
    echo "   ✅ Pedido encontrado: ID " . $pedidoLogo->id . "\n";
    echo "   Número en BD (pedidos_produccion): " . $pedidoLogo->numero_pedido . "\n";
    
    $logoPedido = $pedidoLogo->logoPedido();
    if ($logoPedido) {
        echo "   Número en BD (logo_pedidos): " . $logoPedido->numero_pedido . "\n";
        echo "   Número mostrable: " . $pedidoLogo->numero_pedido_mostrable . "\n";
        echo "   ¿Es LOGO?: " . ($pedidoLogo->esLogo() ? 'SÍ' : 'NO') . "\n";
    }
} else {
    echo "   ℹ️  No hay pedidos LOGO creados aún\n";
}

// 3. Crear un pedido LOGO de prueba
echo "\n3️⃣  Creando pedido LOGO de prueba...\n";

$pedido = PedidoProduccion::first();
if ($pedido) {
    try {
        $logoPedido = LogoPedido::create([
            'pedido_id' => $pedido->id,
            'logo_cotizacion_id' => 1,
            'numero_pedido' => LogoPedido::generarNumeroPedido(),
            'descripcion' => 'Test de numero mostrable',
            'tecnicas' => [],
            'ubicaciones' => [],
        ]);
        
        echo "   ✅ LogoPedido creado: ID " . $logoPedido->id . "\n";
        echo "   Número LOGO: " . $logoPedido->numero_pedido . "\n";
        
        // Recargar el pedido para que cargue la relación
        $pedido->refresh();
        
        echo "\n   📊 Verificación del pedido después de crear LOGO:\n";
        echo "      - Número en BD (pedidos_produccion): " . $pedido->numero_pedido . "\n";
        echo "      - ¿Es LOGO?: " . ($pedido->esLogo() ? 'SÍ' : 'NO') . "\n";
        echo "      - Número mostrable: " . $pedido->numero_pedido_mostrable . "\n";
        
        if ($pedido->numero_pedido_mostrable === $logoPedido->numero_pedido) {
            echo "      ✅ CORRECTO: Se está mostrando el número LOGO\n";
        } else {
            echo "      ❌ ERROR: Debería mostrar '" . $logoPedido->numero_pedido . "' pero muestra '" . $pedido->numero_pedido_mostrable . "'\n";
        }
        
    } catch (\Throwable $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ No hay pedidos disponibles para la prueba\n";
}

echo "\n✅ Prueba completada\n\n";
