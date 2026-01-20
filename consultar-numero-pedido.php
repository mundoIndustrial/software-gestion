<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\PedidoProduccion;

// Obtener el último número de pedido
$ultimoPedido = PedidoProduccion::orderBy('numero_pedido', 'desc')->first();

echo "=== DIAGNÓSTICO DE NÚMEROS DE PEDIDO ===\n\n";

if ($ultimoPedido) {
    echo "Último pedido encontrado:\n";
    echo "  ID: {$ultimoPedido->id}\n";
    echo "  Número: {$ultimoPedido->numero_pedido}\n";
    echo "  Cliente: {$ultimoPedido->cliente}\n";
    echo "  Fecha: {$ultimoPedido->created_at}\n\n";
    
    $proximoNumero = $ultimoPedido->numero_pedido + 1;
    echo "Próximo número a generar: {$proximoNumero}\n";
} else {
    echo "No hay pedidos en la base de datos\n";
}

// Contar todos los pedidos
$totalPedidos = PedidoProduccion::count();
echo "\nTotal de pedidos en BD: {$totalPedidos}\n";

// Buscar el pedido 45696 específicamente
$pedido45696 = PedidoProduccion::where('numero_pedido', 45696)->first();
if ($pedido45696) {
    echo "\n  Pedido 45696 SÍ existe:\n";
    echo "  ID: {$pedido45696->id}\n";
    echo "  Cliente: {$pedido45696->cliente}\n";
    echo "  Fecha creación: {$pedido45696->created_at}\n";
    echo "  Estado: {$pedido45696->estado}\n";
} else {
    echo "\n✓ Pedido 45696 NO existe\n";
}

// Mostrar los últimos 5 pedidos
echo "\nÚltimos 5 pedidos:\n";
$ultimos = PedidoProduccion::orderBy('numero_pedido', 'desc')->take(5)->get();
foreach ($ultimos as $p) {
    echo "  - Número: {$p->numero_pedido}, Cliente: {$p->cliente}, Fecha: {$p->created_at}\n";
}
