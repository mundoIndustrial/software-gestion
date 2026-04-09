<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pedidoId = 156;

echo "=== ANÁLISIS DETALLADO PEDIDO #156 ===\n\n";

// Items en prendas_pedido
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get();

echo "Items en prendas_pedido:\n";
foreach ($prendas as $prenda) {
    echo "  - ID {$prenda->id}: {$prenda->nombre_prenda}\n";
}
echo "Total prendas: " . $prendas->count() . "\n\n";

// Items en pedido_epp
$epps = DB::table('pedido_epp')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get();

echo "Items en pedido_epp:\n";
foreach ($epps as $epp) {
    echo "  - ID {$epp->id}: EPP ID {$epp->epp_id}\n";
}
echo "Total EPPs: " . $epps->count() . "\n\n";

// Total items del pedido
$totalItems = $prendas->count() + $epps->count();
echo "Total items del pedido: $totalItems\n\n";

// Items en bodega_detalles_talla
$bodega = DB::table('bodega_detalles_talla')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get();

echo "Items en bodega_detalles_talla:\n";
foreach ($bodega as $item) {
    $tipo = $item->prenda_id ? "Prenda ID {$item->prenda_id}" : "EPP ID {$item->pedido_epp_id}";
    echo "  - {$tipo}: {$item->prenda_nombre} - Estado: {$item->estado_bodega}\n";
}
echo "Total en bodega: " . $bodega->count() . "\n\n";

// Verificar cuáles items no tienen registro en bodega
echo "=== ITEMS SIN REGISTRO EN BODEGA ===\n";
$prendaIds = $bodega->filter(fn($b) => $b->prenda_id)->pluck('prenda_id')->toArray();
$eppIds = $bodega->filter(fn($b) => $b->pedido_epp_id)->pluck('pedido_epp_id')->toArray();

$prendasSinBodega = $prendas->whereNotIn('id', $prendaIds);
$eppsSinBodega = $epps->whereNotIn('id', $eppIds);

echo "Prendas sin registro: " . $prendasSinBodega->count() . "\n";
foreach ($prendasSinBodega as $prenda) {
    echo "  - ID {$prenda->id}: {$prenda->nombre_prenda}\n";
}

echo "EPPs sin registro: " . $eppsSinBodega->count() . "\n";
foreach ($eppsSinBodega as $epp) {
    echo "  - ID {$epp->id}: EPP ID {$epp->epp_id}\n";
}

echo "\n=== CONCLUSIÓN ===\n";
$todosRegistrados = $bodega->count() === $totalItems;
echo "¿Todos los items tendrían registro? " . ($todosRegistrados ? "SÍ" : "NO") . "\n";

if ($bodega->count() > 0) {
    $todosEntregados = $bodega->every(fn($b) => $b->estado_bodega === 'Entregado');
    echo "¿Todos los registrados están Entregado? " . ($todosEntregados ? "SÍ" : "NO") . "\n";
    echo "\n¿Debería salir notificación? " . ($todosRegistrados && $todosEntregados ? "SÍ" : "NO") . "\n";
} else {
    echo "\nNo hay registros en bodega aún.\n";
}
