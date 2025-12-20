<?php
// Script para probar si LogoPedidos se está mostrando
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LogoPedido;

// Contar LogoPedidos
$count = LogoPedido::where('pedido_id', null)->count();
echo "LogoPedidos (pedido_id = null): $count\n";

// Ver los primeros 3
$logos = LogoPedido::where('pedido_id', null)->limit(3)->get();
foreach ($logos as $logo) {
    echo "- {$logo->numero_pedido} | Cliente: {$logo->cliente} | Creado: {$logo->created_at}\n";
}
?>
