<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Inicializar kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\PedidoProduccion;

echo "=== Verificando Estado del Pedido 45806 ===\n\n";

// Buscar el pedido 45806
$pedido = PedidoProduccion::where('numero_pedido', 45806)->first();

if (!$pedido) {
    echo "❌ Pedido 45806 no encontrado\n";
    exit(1);
}

echo "✅ Pedido encontrado:\n";
echo "   - ID: {$pedido->id}\n";
echo "   - Número: {$pedido->numero_pedido}\n";
echo "   - Cliente: {$pedido->cliente}\n";
echo "   - Estado (BD): '{$pedido->estado}'\n";
echo "   - Estado (trim): '" . trim($pedido->estado) . "'\n";
echo "   - Estado (uppercase): '" . strtoupper($pedido->estado) . "'\n";
echo "   - Estado length: " . strlen($pedido->estado) . "\n";

// Verificar si hay procesos
$procesosTotal = DB::table('pedidos_procesos_prenda_detalles')
    ->join('prendas_pedido', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
    ->where('prendas_pedido.pedido_produccion_id', $pedido->id)
    ->count();

echo "\n   - Total procesos en DB: {$procesosTotal}\n";

// Listar prendas
echo "\n📋 Prendas del pedido:\n";
foreach ($pedido->prendas as $prenda) {
    echo "   - Prenda #{$prenda->id}: {$prenda->nombre_prenda}\n";
    echo "     Procesos: " . $prenda->procesos->count() . "\n";
}

echo "\n";
