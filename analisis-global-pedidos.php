<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ANÁLISIS GLOBAL DE PEDIDOS EN BODEGA ===\n\n";

// Todos los pedidos con registros pendientes de Costura
$pedidosConPendientes = DB::table('bodega_detalles_talla')
    ->where('area', 'Costura')
    ->where('estado_bodega', 'Pendiente')
    ->whereNull('deleted_at')
    ->distinct()
    ->pluck('pedido_produccion_id');

echo "Pedidos con registros pendientes de Costura: {$pedidosConPendientes->count()}\n\n";

if ($pedidosConPendientes->isEmpty()) {
    echo "No hay pedidos con registros pendientes\n";
    exit(0);
}

foreach ($pedidosConPendientes as $pedidoId) {
    $pedido = DB::table('pedidos_produccion')
        ->where('id', $pedidoId)
        ->first(['id', 'numero_pedido', 'cliente', 'estado']);
    
    if (!$pedido) continue;
    
    echo str_repeat('=', 80) . "\n";
    echo "Pedido #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
    echo "Cliente: {$pedido->cliente}\n";
    echo "Estado: {$pedido->estado}\n";
    echo str_repeat('-', 80) . "\n";
    
    // Prendas del pedido
    $prendas = DB::table('prendas_pedido')
        ->where('pedido_produccion_id', $pedidoId)
        ->whereNull('deleted_at')
        ->get(['id', 'nombre_prenda', 'de_bodega']);
    
    echo "Prendas:\n";
    foreach ($prendas as $p) {
        $icon = $p->de_bodega ? '✅' : '❌';
        echo "  {$icon} ID {$p->id}: {$p->nombre_prenda} (de_bodega=" . ($p->de_bodega ? 'TRUE' : 'FALSE') . ")\n";
    }
    
    // Registros pendientes
    $pendientes = DB::table('bodega_detalles_talla')
        ->where('pedido_produccion_id', $pedidoId)
        ->where('area', 'Costura')
        ->where('estado_bodega', 'Pendiente')
        ->whereNull('deleted_at')
        ->get();
    
    echo "\nRegistros pendientes de Costura: {$pendientes->count()}\n";
    foreach ($pendientes as $reg) {
        $prenda = $prendas->firstWhere('id', $reg->prenda_id);
        if ($prenda) {
            $deBodega = $prenda->de_bodega;
            $icon = $deBodega ? '✅' : '❌';
            echo "  {$icon} ID {$reg->id}: {$reg->prenda_nombre} (Talla: {$reg->talla}) -> prenda_id={$reg->prenda_id} (de_bodega=" . ($deBodega ? 'TRUE' : 'FALSE') . ")\n";
        } else {
            echo "  ⚠️  ID {$reg->id}: {$reg->prenda_nombre} -> prenda_id={$reg->prenda_id} (no encontrada)\n";
        }
    }
    
    // Conclusión
    $tienePrendasDeBodega = $prendas->where('de_bodega', 1)->count() > 0;
    $pendientesConDeBodegaTrue = 0;
    
    foreach ($pendientes as $reg) {
        $prenda = $prendas->firstWhere('id', $reg->prenda_id);
        if ($prenda && $prenda->de_bodega) {
            $pendientesConDeBodegaTrue++;
        }
    }
    
    echo "\nConclusión:\n";
    if ($pendientesConDeBodegaTrue > 0) {
        echo "  ✅ DEBE aparecer en /despacho/pendientes\n";
        echo "     Tiene {$pendientesConDeBodegaTrue} registro(s) pendiente(s) vinculado(s) a prendas con de_bodega=TRUE\n";
    } else {
        echo "  ❌ NO DEBE aparecer en /despacho/pendientes\n";
        if (!$tienePrendasDeBodega) {
            echo "     Ninguna prenda tiene de_bodega=TRUE\n";
        } else {
            echo "     Tiene prendas con de_bodega=TRUE pero los registros pendientes son de prendas con de_bodega=FALSE\n";
        }
    }
    echo "\n";
}

echo "\n=== FIN DEL ANÁLISIS ===\n";
