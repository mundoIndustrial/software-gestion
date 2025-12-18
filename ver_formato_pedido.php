<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📌 FORMATO EXACTO DE NÚMEROS DE PEDIDOS\n";
echo "════════════════════════════════════════\n\n";

$pedidos = DB::table('pedidos_produccion')
    ->select('id', 'numero_pedido')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($pedidos as $p) {
    echo "ID: " . str_pad($p->id, 5) . " → numero_pedido: " . $p->numero_pedido . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Tipo de dato: " . gettype($pedidos[0]->numero_pedido) . "\n";
echo "Valor exacto del último: '" . $pedidos[0]->numero_pedido . "'\n";
echo str_repeat("=", 50) . "\n\n";
