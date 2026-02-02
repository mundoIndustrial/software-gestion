<?php

/**
 * TEST: Buscar TODAS las fotos en prenda_fotos_pedido
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Todas las fotos en prenda_fotos_pedido ===\n\n";

$todas = \DB::table('prenda_fotos_pedido')->get();
echo "Total fotos en BD: " . count($todas) . "\n\n";

// Agrupar por prenda
$porPrenda = \DB::table('prenda_fotos_pedido')
    ->join('prendas_pedido', 'prenda_fotos_pedido.prenda_pedido_id', '=', 'prendas_pedido.id')
    ->select('prendas_pedido.id', 'prendas_pedido.nombre_prenda', 'prendas_pedido.pedido_produccion_id', \DB::raw('COUNT(*) as total'))
    ->groupBy('prendas_pedido.id', 'prendas_pedido.nombre_prenda', 'prendas_pedido.pedido_produccion_id')
    ->get();

echo "Fotos por prenda:\n";
foreach ($porPrenda as $row) {
    echo "Pedido {$row->pedido_produccion_id}: {$row->nombre_prenda} ({$row->total} fotos)\n";
}

echo "\nDetalles completos:\n";
$detalles = \DB::table('prenda_fotos_pedido')
    ->join('prendas_pedido', 'prenda_fotos_pedido.prenda_pedido_id', '=', 'prendas_pedido.id')
    ->select('prendas_pedido.pedido_produccion_id', 'prendas_pedido.nombre_prenda', 'prenda_fotos_pedido.ruta_webp', 'prenda_fotos_pedido.orden')
    ->orderBy('prendas_pedido.nombre_prenda')
    ->get();

foreach ($detalles as $row) {
    echo "Pedido {$row->pedido_produccion_id} - {$row->nombre_prenda} (orden {$row->orden}): \n  " . substr($row->ruta_webp, 0, 60) . "\n";
}

echo "\n✅ TEST COMPLETADO\n";
