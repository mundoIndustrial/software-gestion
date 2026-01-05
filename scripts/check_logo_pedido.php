<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$resultado = DB::table('logo_pedidos')
    ->where('pedido_id', 2552)
    ->orWhere('id', 2552)
    ->get(['id', 'pedido_id', 'numero_pedido', 'numero_pedido_cost']);

echo "Resultados para pedido_id=2552 o id=2552:\n";
echo json_encode($resultado->toArray(), JSON_PRETTY_PRINT);
echo "\n";
