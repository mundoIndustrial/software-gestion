<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

// Obtener el último pedido
$pedido = PedidoProduccion::orderBy('id', 'desc')->first();

if (!$pedido) {
    echo "No hay pedidos";
    exit;
}

echo "ÚLTIMO PEDIDO CREADO:\n";
echo "=====================\n";
echo "ID: " . $pedido->id . "\n";
echo "Número: " . $pedido->numero_pedido . "\n";
echo "Estado: " . $pedido->estado . "\n";
echo "Descripción Pedido: " . ($pedido->descripcion ?? "VACIA") . "\n\n";

echo "PRENDAS DEL PEDIDO:\n";
echo "==================\n";
$prendas = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();
echo "Total prendas: " . $prendas->count() . "\n\n";

foreach ($prendas as $prenda) {
    echo "Prenda: " . $prenda->nombre_prenda . "\n";
    echo "  Descripción: " . ($prenda->descripcion ?? "VACIA") . "\n";
    echo "  Variaciones: " . ($prenda->descripcion_variaciones ?? "VACIA") . "\n";
    echo "  Cantidad: " . $prenda->cantidad . "\n";
    echo "  Tallas: " . ($prenda->cantidad_talla ?? "VACIA") . "\n";
    echo "\n";
}
