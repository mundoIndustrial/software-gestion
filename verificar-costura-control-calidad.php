<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

echo "=== VERIFICANDO RECIBOS DE COSTURA EN CONTROL DE CALIDAD ===\n\n";

// 1. Ver si existen procesos de Control de Calidad
echo "1. Procesos de Control de Calidad pendientes:\n";
$procesosCC = DB::table('procesos_prenda')
    ->where('proceso', 'Control de Calidad')
    ->where('estado_proceso', 'Pendiente')
    ->select('id', 'numero_pedido', 'numero_recibo', 'prenda_pedido_id', 'proceso', 'estado_proceso')
    ->get();

echo "   Total encontrados: " . count($procesosCC) . "\n";
if (count($procesosCC) > 0) {
    foreach ($procesosCC as $p) {
        echo "   - Recibo {$p->numero_recibo}, Pedido {$p->numero_pedido}, Prenda {$p->prenda_pedido_id}\n";
    }
}
echo "\n";

// 2. Ver recibos de tipo COSTURA en consecutivos_recibos_pedidos
echo "2. Recibos de tipo COSTURA activos en consecutivos_recibos_pedidos:\n";
$recibos = DB::table('consecutivos_recibos_pedidos')
    ->where('tipo_recibo', 'COSTURA')
    ->where('activo', 1)
    ->select('id', 'pedido_produccion_id', 'prenda_id', 'tipo_recibo', 'consecutivo_actual', 'area')
    ->limit(10)
    ->get();

echo "   Total encontrados (mostrando 10): " . count($recibos) . "\n";
if (count($recibos) > 0) {
    foreach ($recibos as $r) {
        echo "   - Consecutivo {$r->consecutivo_actual}, Tipo {$r->tipo_recibo}, Área {$r->area}\n";
    }
}
echo "\n";

// 3. JOIN: Procesos CC con Recibos de COSTURA
echo "3. JOIN entre procesos_prenda (CC) y consecutivos_recibos_pedidos (COSTURA):\n";
$joined = DB::table('procesos_prenda as pp')
    ->join('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
    ->join('consecutivos_recibos_pedidos as crp', function($join) {
        $join->on('crp.pedido_produccion_id', '=', 'p.id')
            ->on('crp.consecutivo_actual', '=', DB::raw('CAST(pp.numero_recibo AS UNSIGNED)'));
    })
    ->where('pp.proceso', 'Control de Calidad')
    ->where('pp.estado_proceso', 'Pendiente')
    ->where('crp.tipo_recibo', 'COSTURA')
    ->where('crp.activo', 1)
    ->select([
        'pp.numero_recibo',
        'p.numero_pedido',
        'p.cliente',
        'pp.proceso',
        'crp.tipo_recibo',
        'crp.area as area_recibo'
    ])
    ->get();

echo "   Total encontrados: " . count($joined) . "\n";
if (count($joined) > 0) {
    foreach ($joined as $j) {
        echo "   ✓ Recibo {$j->numero_recibo}, Pedido {$j->numero_pedido}, Cliente {$j->cliente}\n";
        echo "     Tipo: {$j->tipo_recibo}, Área: {$j->area_recibo}\n";
    }
} else {
    echo "   ✗ NO hay coincidencias. Verificando por qué...\n\n";
    
    // Debug: ver si los numero_recibo matchean
    echo "4. DEBUG - Comparación de numero_recibo:\n";
    $debug = DB::table('procesos_prenda as pp')
        ->join('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
        ->leftJoin('consecutivos_recibos_pedidos as crp', function($join) {
            $join->on('crp.pedido_produccion_id', '=', 'p.id');
        })
        ->where('pp.proceso', 'Control de Calidad')
        ->where('pp.estado_proceso', 'Pendiente')
        ->where('crp.tipo_recibo', 'COSTURA')
        ->select([
            'pp.numero_recibo as pp_recibo',
            'crp.consecutivo_actual as crp_consecutivo',
            'pp.numero_pedido',
            'p.id as pedido_id',
            'crp.pedido_produccion_id'
        ])
        ->limit(5)
        ->get();
    
    if (count($debug) > 0) {
        foreach ($debug as $d) {
            echo "   Proceso recibo: {$d->pp_recibo}, Consecutivo: {$d->crp_consecutivo}\n";
            echo "   Pedido PP: {$d->numero_pedido}, Pedido_id: {$d->pedido_id}, crp.pedido_id: {$d->pedido_produccion_id}\n";
        }
    }
}

echo "\n=== FIN DE VERIFICACIÓN ===\n";
?>
