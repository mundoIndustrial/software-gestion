<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;

// Obtener el último pedido creado
$pedido = PedidoProduccion::orderBy('id', 'desc')->first();

if (!$pedido) {
    echo "❌ No hay pedidos en la base de datos\n";
    exit;
}

echo "📋 VERIFICACIÓN DEL ÚLTIMO PEDIDO\n";
echo "================================\n\n";
echo "Pedido ID: {$pedido->id}\n";
echo "Número Pedido: {$pedido->numero_pedido}\n";
echo "Cliente: {$pedido->cliente}\n";
echo "Cantidad Total en Pedido: {$pedido->cantidad_total}\n\n";

// Obtener prendas del pedido
$prendas = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();

echo "📦 DESGLOSE DE PRENDAS\n";
echo "=====================\n";

$totalCalculado = 0;
foreach ($prendas as $i => $prenda) {
    $cantidadesTalla = is_array($prenda->cantidad_talla) 
        ? $prenda->cantidad_talla 
        : json_decode($prenda->cantidad_talla, true);
    
    $sumaTallas = array_sum($cantidadesTalla ?? []);
    $totalCalculado += $sumaTallas;
    
    echo "\n{$i}. {$prenda->nombre_prenda}\n";
    echo "   - ID: {$prenda->id}\n";
    echo "   - Cantidad en BD: {$prenda->cantidad}\n";
    echo "   - cantidad_talla (JSON): " . json_encode($cantidadesTalla) . "\n";
    echo "   - Suma de tallas: {$sumaTallas}\n";
}

echo "\n\n📊 RESUMEN\n";
echo "==========\n";
echo "Total de prendas: " . $prendas->count() . "\n";
echo "Cantidad total en pedidos_produccion: {$pedido->cantidad_total}\n";
echo "Suma manual de prendas: {$totalCalculado}\n";
echo "¿Coinciden?: " . ($pedido->cantidad_total == $totalCalculado ? "✅ SÍ" : "❌ NO") . "\n";
