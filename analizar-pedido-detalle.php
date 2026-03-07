<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

$numeroPedido = $argv[1] ?? '37';

echo "=== ANÁLISIS DETALLADO DEL PEDIDO #{$numeroPedido} ===\n\n";

// Buscar el pedido
$pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

if (!$pedido) {
    echo "❌ Pedido #{$numeroPedido} no encontrado\n";
    exit(1);
}

echo "Pedido ID: {$pedido->id} | Número: {$pedido->numero_pedido} | Cliente: {$pedido->cliente}\n\n";

// Obtener bodega_detalles_talla con información cruzada
echo "=== REGISTROS EN BODEGA_DETALLES_TALLA ===\n\n";

$detalles = DB::table('bodega_detalles_talla as bdt')
    ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'bdt.prenda_id')
    ->leftJoin('epp', 'epp.id', '=', 'bdt.pedido_epp_id')
    ->where('bdt.pedido_produccion_id', $pedido->id)
    ->whereNull('bdt.deleted_at')
    ->select(
        'bdt.id',
        'bdt.area',
        'bdt.estado_bodega',
        'bdt.prenda_nombre as nombre_en_bodega',
        'bdt.talla',
        'bdt.cantidad',
        'bdt.prenda_id',
        'pp.nombre_prenda as nombre_en_prendas_pedido',
        'pp.de_bodega',
        'bdt.pedido_epp_id',
        'epp.nombre as nombre_epp'
    )
    ->get();

echo "Total registros: {$detalles->count()}\n\n";

// Agrupar por área
$costura = $detalles->where('area', 'Costura');
$epp = $detalles->where('area', 'EPP');

// COSTURA
if ($costura->count() > 0) {
    echo "📦 ÁREA COSTURA ({$costura->count()} registros):\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($costura as $detalle) {
        $estadoIcon = match($detalle->estado_bodega) {
            'Pendiente' => '⏳',
            'Entregado' => '✅',
            'Anulado' => '❌',
            default => '❓'
        };
        
        echo "{$estadoIcon} Estado: {$detalle->estado_bodega} | ID: {$detalle->id}\n";
        echo "   Nombre en bodega_detalles_talla: {$detalle->nombre_en_bodega}\n";
        echo "   Talla: {$detalle->talla} | Cantidad: {$detalle->cantidad}\n";
        
        if ($detalle->prenda_id) {
            echo "   ├─ Vinculado a prenda_id: {$detalle->prenda_id}\n";
            echo "   ├─ Nombre en prendas_pedido: " . ($detalle->nombre_en_prendas_pedido ?? 'N/A') . "\n";
            echo "   └─ de_bodega: " . ($detalle->de_bodega ? '✅ TRUE' : '❌ FALSE') . "\n";
            
            if (!$detalle->de_bodega && $detalle->estado_bodega == 'Pendiente') {
                echo "   ⚠️  PROBLEMA: Este registro está pendiente pero la prenda tiene de_bodega=false\n";
                echo "   ⚠️  No debería causar que aparezca en despacho/pendientes\n";
            }
        } else {
            echo "   ⚠️  Sin vinculación a prendas_pedido (prenda_id es NULL)\n";
        }
        echo "\n";
    }
}

// EPP
if ($epp->count() > 0) {
    echo "🛡️  ÁREA EPP ({$epp->count()} registros):\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($epp as $detalle) {
        $estadoIcon = match($detalle->estado_bodega) {
            'Pendiente' => '⏳',
            'Entregado' => '✅',
            'Anulado' => '❌',
            default => '❓'
        };
        
        echo "{$estadoIcon} Estado: {$detalle->estado_bodega} | ID: {$detalle->id}\n";
        echo "   EPP: " . ($detalle->nombre_epp ?? 'N/A') . "\n";
        echo "   Cantidad: {$detalle->cantidad}\n";
        echo "   pedido_epp_id: " . ($detalle->pedido_epp_id ?? 'NULL') . "\n";
        echo "\n";
    }
}

// CONCLUSIÓN
echo "\n" . str_repeat('=', 80) . "\n";
echo "=== CONCLUSIÓN: ¿DEBE APARECER EN /despacho/pendientes? ===\n";
echo str_repeat('=', 80) . "\n\n";

// Verificar si tiene prendas de bodega
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedido->id)
    ->whereNull('deleted_at')
    ->get(['id', 'nombre_prenda', 'de_bodega']);

$tienePrendasDeBodega = $prendas->where('de_bodega', 1)->count() > 0;

echo "1️⃣  Prendas del pedido:\n";
foreach ($prendas as $p) {
    echo "   - ID {$p->id}: {$p->nombre_prenda} | de_bodega=" . ($p->de_bodega ? 'true' : 'false') . "\n";
}
echo "   Total con de_bodega=true: " . $prendas->where('de_bodega', 1)->count() . "\n\n";

// Verificar costura pendiente
$costuraPendiente = $costura->where('estado_bodega', 'Pendiente');
echo "2️⃣  Registros Costura pendientes: {$costuraPendiente->count()}\n";
if ($costuraPendiente->count() > 0) {
    foreach ($costuraPendiente as $cp) {
        $vinculado = $cp->prenda_id ? "vinculado a prenda {$cp->prenda_id}" : "NO vinculado";
        $deBodega = $cp->de_bodega ? "(de_bodega=true)" : "(de_bodega=false)";
        echo "   - ID {$cp->id}: {$vinculado} {$deBodega}\n";
    }
}
echo "\n";

// Verificar EPP pendiente
$eppPendiente = $epp->where('estado_bodega', 'Pendiente');
echo "3️⃣  Registros EPP pendientes: {$eppPendiente->count()}\n\n";

// Decisión final
$debeAparecerPorCostura = $tienePrendasDeBodega && $costuraPendiente->count() > 0;
$debeAparecerPorEpp = $eppPendiente->count() > 0;

if ($debeAparecerPorCostura || $debeAparecerPorEpp) {
    echo "✅ SÍ DEBE APARECER en /despacho/pendientes porque:\n";
    if ($debeAparecerPorCostura) {
        echo "   ✓ Tiene prendas con de_bodega=true Y registros Costura pendientes\n";
    }
    if ($debeAparecerPorEpp) {
        echo "   ✓ Tiene EPP pendiente\n";
    }
} else {
    echo "❌ NO DEBE APARECER en /despacho/pendientes porque:\n";
    if (!$tienePrendasDeBodega) {
        echo "   ✗ No tiene prendas con de_bodega=true\n";
    }
    if ($costuraPendiente->count() == 0 && $eppPendiente->count() == 0) {
        echo "   ✗ No tiene registros pendientes en bodega\n";
    }
    if ($tienePrendasDeBodega && $costuraPendiente->count() == 0) {
        echo "   ✗ Aunque tiene prendas de_bodega=true, no hay nada pendiente de despachar\n";
    }
}

echo "\n";
