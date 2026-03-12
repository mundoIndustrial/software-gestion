<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COSTURA PENDIENTE - DETALLES ===\n\n";

// Ver los pedidos con area=Costura y estado_bodega=Pendiente
$cosuturaPendiente = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->select('numero_pedido', 'prenda_nombre', 'cantidad', 'asesor', 'empresa', 'area', 'estado_bodega')
    ->distinct('numero_pedido')
    ->get();

echo "Total de números de pedido distintos con Costura Pendiente: " . $cosuturaPendiente->count() . "\n\n";

foreach ($cosuturaPendiente as $row) {
    echo "Pedido #{$row->numero_pedido}\n";
    echo "  - Prenda: {$row->prenda_nombre}\n";
    echo "  - Cantidad: {$row->cantidad}\n";
    echo "  - Asesor: {$row->asesor}\n";
    echo "  - Empresa: {$row->empresa}\n";
    echo "  - Área: {$row->area}\n";
    echo "  - Estado: {$row->estado_bodega}\n\n";
}

// Ver qué pasa si anulamos pedidos
echo "\n=== ESTADO DE PEDIDOS EN PEDIDOS_PRODUCCION ===\n\n";

$numerosPedido = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->distinct('numero_pedido')
    ->pluck('numero_pedido');

$estadosPedidos = DB::table('pedidos_produccion')
    ->whereIn('numero_pedido', $numerosPedido)
    ->select('numero_pedido', 'estado', 'cliente')
    ->get();

foreach ($estadosPedidos as $row) {
    echo "Pedido #{$row->numero_pedido}\n";
    echo "  - Estado: {$row->estado}\n";
    echo "  - Cliente: {$row->cliente}\n\n";
}

// Ver qué registros están siendo excluidos por estar anulados
echo "\n=== REGISTROS ANULADOS ===\n\n";

$anulados = DB::table('bodega_detalles_talla as bdt')
    ->join('pedidos_produccion as pp', 'bdt.numero_pedido', '=', 'pp.numero_pedido')
    ->where('bdt.area', 'Costura')
    ->where('bdt.estado_bodega', 'Pendiente')
    ->where('pp.estado', 'Anulada')
    ->select('bdt.numero_pedido', 'pp.estado')
    ->distinct('bdt.numero_pedido')
    ->get();

echo "Registros Costura Pendiente con pedido Anulado: " . $anulados->count() . "\n\n";
foreach ($anulados as $row) {
    echo "  - Pedido #{$row->numero_pedido}: Estado = {$row->estado}\n";
}
