<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;

// Obtener una orden y ver qué campos tiene
$orden = PedidoProduccion::first();

if ($orden) {
    echo "\n========== ATRIBUTOS DE LA ORDEN ==========\n\n";
    echo "ID: " . $orden->id . "\n";
    echo "PEDIDO: " . ($orden->pedido ?? 'NULL') . "\n";
    echo "NUMERO_PEDIDO: " . ($orden->numero_pedido ?? 'NULL') . "\n";
    echo "_PEDIDO: " . ($orden->_pedido ?? 'NULL') . "\n";
    echo "Todos los atributos:\n";
    print_r($orden->getAttributes());
} else {
    echo "No hay órdenes\n";
}
