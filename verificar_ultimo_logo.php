<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logoPedidos = DB::table('logo_pedidos')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get(['id', 'numero_pedido', 'pedido_id', 'cliente', 'created_at']);

echo "=== ÚLTIMOS 5 LOGO PEDIDOS ===\n";
foreach ($logoPedidos as $lp) {
    $fechaHora = \Carbon\Carbon::parse($lp->created_at)->format('Y-m-d H:i:s');
    echo "ID: {$lp->id} | Número: {$lp->numero_pedido} | Pedido FK: {$lp->pedido_id} | Cliente: {$lp->cliente} | Creado: {$fechaHora}\n";
}
