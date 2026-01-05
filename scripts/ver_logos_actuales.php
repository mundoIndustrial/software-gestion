<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== LOGO_PEDIDOS ACTUALES ===\n\n";

$logos = DB::table('logo_pedidos')
    ->orderBy('id', 'desc')
    ->limit(15)
    ->get(['id', 'numero_pedido', 'pedido_id', 'numero_pedido_cost', 'cotizacion_id', 'estado', 'created_at']);

echo "Total de registros: " . DB::table('logo_pedidos')->count() . "\n\n";

foreach ($logos as $logo) {
    echo sprintf(
        "ID: %-3s | Numero: %-30s | Pedido ID: %-6s | Num Cost: %-6s | Cot ID: %-4s | Estado: %-10s | Fecha: %s\n",
        $logo->id,
        $logo->numero_pedido ?: '(sin número)',
        $logo->pedido_id ?: 'NULL',
        $logo->numero_pedido_cost ?: 'NULL',
        $logo->cotizacion_id ?: 'NULL',
        $logo->estado ?: 'sin estado',
        $logo->created_at
    );
}

echo "\n";
