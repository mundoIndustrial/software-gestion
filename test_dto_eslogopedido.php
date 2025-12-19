<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\DTOs\CrearPedidoProduccionDTO;

echo "\n========================================\n";
echo "  PRUEBA: DTO esLogoPedido()\n";
echo "========================================\n\n";

// Test 1: Pedido NORMAL (con prendas, sin logo)
echo "1️⃣  Test Pedido NORMAL (con prendas):\n";
$dtoPrendas = new CrearPedidoProduccionDTO(
    cotizacionId: 1,
    prendasData: [
        ['index' => 0, 'cantidad' => 5, 'tela' => 'Algodón'],
    ],
    logo: null
);

echo "   esLogoPedido(): " . ($dtoPrendas->esLogoPedido() ? 'SÍ' : 'NO') . "\n";
echo "   ✅ Esperado: NO\n\n";

// Test 2: Pedido LOGO (sin prendas, con logo)
echo "2️⃣  Test Pedido LOGO (sin prendas, con logo):\n";
$dtoLogo = new CrearPedidoProduccionDTO(
    cotizacionId: 1,
    prendasData: [],
    logo: [
        'id' => 1,
        'descripcion' => 'Logo test',
    ]
);

echo "   esLogoPedido(): " . ($dtoLogo->esLogoPedido() ? 'SÍ' : 'NO') . "\n";
echo "   ✅ Esperado: SÍ\n\n";

// Test 3: Pedido LOGO con prendas (ambos)
echo "3️⃣  Test Pedido con Prendas y Logo:\n";
$dtoMixto = new CrearPedidoProduccionDTO(
    cotizacionId: 1,
    prendasData: [
        ['index' => 0, 'cantidad' => 5, 'tela' => 'Algodón'],
    ],
    logo: [
        'id' => 1,
        'descripcion' => 'Logo test',
    ]
);

echo "   esLogoPedido(): " . ($dtoMixto->esLogoPedido() ? 'SÍ' : 'NO') . "\n";
echo "   ✅ Esperado: NO (tiene prendas)\n\n";

// Test 4: Pedido vacío
echo "4️⃣  Test Pedido vacío:\n";
$dtoVacio = new CrearPedidoProduccionDTO(
    cotizacionId: 1,
    prendasData: [],
    logo: null
);

echo "   esLogoPedido(): " . ($dtoVacio->esLogoPedido() ? 'SÍ' : 'NO') . "\n";
echo "   ✅ Esperado: NO (no tiene ni prendas ni logo)\n\n";

echo "✅ Pruebas completadas\n\n";
