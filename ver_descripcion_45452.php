<?php
// Cargar Laravel
require __DIR__ . '/bootstrap/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Database\Capsule\Manager as DB;

// Obtener pedido 45452
$pedido = DB::table('pedidos_produccion')
    ->where('numero_pedido', 45452)
    ->first();

if ($pedido) {
    echo "PEDIDO 45452 - DESCRIPCIÓN GUARDADA EN BD:\n";
    echo "==========================================\n\n";
    echo $pedido->descripcion_prendas;
    echo "\n\n";
    echo "==========================================\n";
    echo "Longitud: " . strlen($pedido->descripcion_prendas) . " caracteres\n";
} else {
    echo "Pedido 45452 no encontrado\n";
}
