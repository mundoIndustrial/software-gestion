<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$numeroPedido = $argv[1] ?? '37';

echo "=== BODEGA_DETALLES_TALLA - PEDIDO #{$numeroPedido} ===\n\n";

// Buscar el pedido
$pedidoId = DB::table('pedidos_produccion')
    ->where('numero_pedido', $numeroPedido)
    ->value('id');

if (!$pedidoId) {
    echo "❌ Pedido #{$numeroPedido} no encontrado\n";
    exit(1);
}

echo "Pedido ID: {$pedidoId}\n\n";

// Obtener registros de bodega_detalles_talla
$detalles = DB::table('bodega_detalles_talla')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get();

echo "Total registros: {$detalles->count()}\n\n";

if ($detalles->isEmpty()) {
    echo "No hay registros en bodega_detalles_talla para este pedido\n";
    exit(0);
}

// Mostrar cada registro
foreach ($detalles as $i => $detalle) {
    echo "Registro #" . ($i + 1) . " (ID: {$detalle->id})\n";
    echo str_repeat('-', 60) . "\n";
    echo "  area: {$detalle->area}\n";
    echo "  estado_bodega: {$detalle->estado_bodega}\n";
    echo "  costura_estado: " . ($detalle->costura_estado ?? 'NULL') . "\n";
    echo "  epp_estado: " . ($detalle->epp_estado ?? 'NULL') . "\n";
    echo "  prenda_nombre: {$detalle->prenda_nombre}\n";
    echo "  talla: {$detalle->talla}\n";
    echo "  cantidad: {$detalle->cantidad}\n";
    echo "  prenda_id: " . ($detalle->prenda_id ?? 'NULL') . "\n";
    echo "  pedido_epp_id: " . ($detalle->pedido_epp_id ?? 'NULL') . "\n";
    echo "\n";
}

// Análisis
echo str_repeat('=', 60) . "\n";
echo "=== ANÁLISIS ===\n";
echo str_repeat('=', 60) . "\n\n";

$costura = $detalles->where('area', 'Costura');
$epp = $detalles->where('area', 'EPP');

echo "Por ÁREA:\n";
echo "  - Costura: {$costura->count()}\n";
echo "  - EPP: {$epp->count()}\n";
echo "  - Otro: " . $detalles->where('area', 'Otro')->count() . "\n\n";

echo "Por ESTADO_BODEGA:\n";
echo "  - Pendiente: " . $detalles->where('estado_bodega', 'Pendiente')->count() . "\n";
echo "  - Entregado: " . $detalles->where('estado_bodega', 'Entregado')->count() . "\n";
echo "  - Anulado: " . $detalles->where('estado_bodega', 'Anulado')->count() . "\n\n";

// Condiciones para aparecer en pendientes
$costuraPendiente = $detalles->where('area', 'Costura')->where('estado_bodega', 'Pendiente');
$eppPendiente = $detalles->where('area', 'EPP')->where('estado_bodega', 'Pendiente');

echo "CONDICIONES PARA APARECER EN /despacho/pendientes:\n\n";

echo "1) COSTURA PENDIENTE (area='Costura' AND estado_bodega='Pendiente'):\n";
echo "   Total: {$costuraPendiente->count()}\n";
if ($costuraPendiente->count() > 0) {
    foreach ($costuraPendiente as $c) {
        echo "   - ID {$c->id}: {$c->prenda_nombre} | Talla: {$c->talla} | prenda_id: " . ($c->prenda_id ?? 'NULL') . "\n";
    }
}
echo "\n";

echo "2) EPP PENDIENTE (area='EPP' AND estado_bodega='Pendiente'):\n";
echo "   Total: {$eppPendiente->count()}\n";
if ($eppPendiente->count() > 0) {
    foreach ($eppPendiente as $e) {
        echo "   - ID {$e->id}: {$e->prenda_nombre} | pedido_epp_id: " . ($e->pedido_epp_id ?? 'NULL') . "\n";
    }
}
echo "\n";

// Verificar prendas con de_bodega
echo "3) VERIFICAR PRENDAS DEL PEDIDO:\n";
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->whereNull('deleted_at')
    ->get(['id', 'nombre_prenda', 'de_bodega']);

echo "   Total prendas: {$prendas->count()}\n";
foreach ($prendas as $p) {
    $deBodegaIcon = $p->de_bodega ? '✅' : '❌';
    echo "   {$deBodegaIcon} Prenda ID {$p->id}: {$p->nombre_prenda} | de_bodega=" . ($p->de_bodega ? 'TRUE' : 'FALSE') . "\n";
}
echo "\n";

$tienePrendasDeBodega = $prendas->where('de_bodega', 1)->count() > 0;

// CONCLUSIÓN FINAL
echo str_repeat('=', 60) . "\n";
echo "=== CONCLUSIÓN FINAL ===\n";
echo str_repeat('=', 60) . "\n\n";

if ($costuraPendiente->count() > 0) {
    if ($tienePrendasDeBodega) {
        echo "✅ DEBE aparecer en /despacho/pendientes (COSTURA)\n";
        echo "   Razón: Tiene registros con area='Costura' + estado_bodega='Pendiente'\n";
        echo "          Y tiene prendas con de_bodega=true\n";
    } else {
        echo "❌ NO DEBE aparecer en /despacho/pendientes\n";
        echo "   Razón: Aunque tiene registros con area='Costura' + estado_bodega='Pendiente'\n";
        echo "          NO tiene ninguna prenda con de_bodega=true\n";
        echo "          (Las prendas de_bodega=false van a producción, no a despacho)\n";
        
        // Verificar vinculación
        echo "\n   Verificando vinculación de registros pendientes:\n";
        foreach ($costuraPendiente as $c) {
            if ($c->prenda_id) {
                $prenda = $prendas->firstWhere('id', $c->prenda_id);
                if ($prenda) {
                    $deBodega = $prenda->de_bodega ? 'TRUE' : 'FALSE';
                    echo "   - ID {$c->id} vinculado a prenda {$c->prenda_id} (de_bodega={$deBodega})\n";
                }
            } else {
                echo "   - ID {$c->id} NO vinculado a ninguna prenda (prenda_id=NULL)\n";
            }
        }
    }
} elseif ($eppPendiente->count() > 0) {
    echo "✅ DEBE aparecer en /despacho/pendientes (EPP)\n";
    echo "   Razón: Tiene registros con area='EPP' + estado_bodega='Pendiente'\n";
} else {
    echo "✅ NO DEBE aparecer en /despacho/pendientes\n";
    echo "   Razón: No tiene registros pendientes (estado_bodega='Pendiente')\n";
}

echo "\n";
