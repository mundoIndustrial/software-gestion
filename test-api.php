<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PedidoProduccion;

// Verificar datos pendiente_cartera
echo "=== DATOS EN BD ===\n";
$pedidos = PedidoProduccion::where('estado', 'pendiente_cartera')->get();
echo "Total con estado pendiente_cartera: " . $pedidos->count() . "\n";

foreach ($pedidos as $p) {
    echo "ID: " . $p->id . " | Número: " . $p->numero_pedido . " | Cliente: " . $p->cliente . " | Estado: " . $p->estado . "\n";
}

echo "\n=== SIMULANDO API ===\n";
$estadosPendientes = ['pendiente_cartera'];
$resultado = PedidoProduccion::whereIn('estado', $estadosPendientes)
    ->orderBy('fecha_de_creacion_de_orden', 'desc')
    ->get();

$data = $resultado->map(function($pedido) {
    return [
        'id' => $pedido->id,
        'numero' => $pedido->numero_pedido,
        'numero_pedido' => $pedido->numero_pedido,
        'cliente_nombre' => $pedido->cliente,
        'cliente' => $pedido->cliente,
        'monto_total' => 0,
        'estado' => $pedido->estado,
        'created_at' => $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at
    ];
});

echo "Response API:\n";
echo json_encode([
    'success' => true,
    'data' => $data
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
