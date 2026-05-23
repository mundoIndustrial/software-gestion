<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rolTallerId = DB::table('roles')->where('name', 'taller')->value('id');

if (!$rolTallerId) {
    echo "No existe rol 'taller' en tabla roles.\n";
    exit(1);
}

echo "=== DEBUG TALLERES RECIBOS ===\n";
echo "Rol taller ID: {$rolTallerId}\n\n";

// -------- NORMALES: COSTURA/REFLECTIVO --------
$qNormalesBase = DB::table('consecutivos_recibos_pedidos as crp')
    ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
    ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
    ->join('procesos_prenda as ppren', function ($join) {
        $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
            ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo');
    })
    ->join('users as u', 'ppren.encargado', '=', 'u.name')
    ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA']);

$qNormalesArea = (clone $qNormalesBase)
    ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'");

$qNormalesProceso = (clone $qNormalesArea)
    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");

$qNormalesRol = (clone $qNormalesProceso)
    ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode((int) $rolTallerId)]);

echo "[NORMALES COSTURA/REFLECTIVO]\n";
echo "Base (tipo): " . $qNormalesBase->count(DB::raw('distinct crp.id')) . "\n";
echo " + area=costura: " . $qNormalesArea->count(DB::raw('distinct crp.id')) . "\n";
echo " + proceso=costura: " . $qNormalesProceso->count(DB::raw('distinct crp.id')) . "\n";
echo " + rol taller: " . $qNormalesRol->count(DB::raw('distinct crp.id')) . "\n";

$ejNormales = (clone $qNormalesProceso)
    ->select('crp.id', 'crp.consecutivo_actual', 'crp.tipo_recibo', 'crp.area', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->groupBy('crp.id', 'crp.consecutivo_actual', 'crp.tipo_recibo', 'crp.area', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->orderByDesc('crp.id')
    ->limit(10)
    ->get();

echo "Ejemplos (sin filtro rol, con area+proceso):\n";
foreach ($ejNormales as $r) {
    echo "- id={$r->id} recibo={$r->consecutivo_actual} tipo={$r->tipo_recibo} area={$r->area} proceso={$r->proceso} encargado={$r->encargado}\n";
}
echo "\n";

// -------- NORMALES: CORTE-PARA-BODEGA --------
$qBodegaBase = DB::table('consecutivos_recibos_pedidos as crp')
    ->join('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
    ->join('procesos_prenda as ppren', 'crp.consecutivo_actual', '=', 'ppren.numero_recibo')
    ->join('users as u', 'ppren.encargado', '=', 'u.name')
    ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA');

$qBodegaArea = (clone $qBodegaBase)
    ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'");

$qBodegaProceso = (clone $qBodegaArea)
    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");

$qBodegaRol = (clone $qBodegaProceso)
    ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode((int) $rolTallerId)]);

echo "[NORMALES CORTE-PARA-BODEGA]\n";
echo "Base (tipo): " . $qBodegaBase->count(DB::raw('distinct crp.id')) . "\n";
echo " + area=costura: " . $qBodegaArea->count(DB::raw('distinct crp.id')) . "\n";
echo " + proceso=costura: " . $qBodegaProceso->count(DB::raw('distinct crp.id')) . "\n";
echo " + rol taller: " . $qBodegaRol->count(DB::raw('distinct crp.id')) . "\n";

$ejBodega = (clone $qBodegaProceso)
    ->select('crp.id', 'crp.consecutivo_actual', 'crp.tipo_recibo', 'crp.area', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->groupBy('crp.id', 'crp.consecutivo_actual', 'crp.tipo_recibo', 'crp.area', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->orderByDesc('crp.id')
    ->limit(10)
    ->get();

echo "Ejemplos bodega (sin filtro rol, con area+proceso):\n";
foreach ($ejBodega as $r) {
    echo "- id={$r->id} recibo={$r->consecutivo_actual} tipo={$r->tipo_recibo} area={$r->area} proceso={$r->proceso} encargado={$r->encargado}\n";
}
echo "\n";

// -------- PARCIALES --------
$qParcialesBase = DB::table('recibo_por_partes as rpp')
    ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
    ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
    ->join('procesos_prenda as ppren', function ($join) {
        $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
            ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
            ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial');
    })
    ->join('users as u', 'ppren.encargado', '=', 'u.name')
    ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA', 'CORTE-PARA-BODEGA']);

$qParcialesProceso = (clone $qParcialesBase)
    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");

$qParcialesRol = (clone $qParcialesProceso)
    ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode((int) $rolTallerId)]);

echo "[PARCIALES]\n";
echo "Base (tipo): " . $qParcialesBase->count(DB::raw('distinct rpp.id')) . "\n";
echo " + proceso=costura: " . $qParcialesProceso->count(DB::raw('distinct rpp.id')) . "\n";
echo " + rol taller: " . $qParcialesRol->count(DB::raw('distinct rpp.id')) . "\n";

$ejParciales = (clone $qParcialesProceso)
    ->select('rpp.id', 'rpp.consecutivo_parcial', 'rpp.tipo_recibo', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->groupBy('rpp.id', 'rpp.consecutivo_parcial', 'rpp.tipo_recibo', 'ppren.proceso', 'ppren.encargado', 'u.roles_ids')
    ->orderByDesc('rpp.id')
    ->limit(10)
    ->get();

echo "Ejemplos parciales (sin filtro rol, con proceso):\n";
foreach ($ejParciales as $r) {
    echo "- id={$r->id} parcial={$r->consecutivo_parcial} tipo={$r->tipo_recibo} proceso={$r->proceso} encargado={$r->encargado}\n";
}
echo "\n";

echo "=== FIN DEBUG ===\n";

