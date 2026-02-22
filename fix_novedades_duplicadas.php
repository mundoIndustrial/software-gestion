<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver todas las novedades actuales
$all = DB::table('prendas_pedido_novedades_recibo')->get();
echo "Total registros: " . count($all) . "\n\n";

foreach ($all as $n) {
    echo "ID: {$n->id} | prenda_id: {$n->prenda_pedido_id} | recibo: {$n->numero_recibo} | texto: " . substr($n->novedad_texto, 0, 30) . " | creado_por: {$n->creado_por} | creado_en: {$n->creado_en}\n";
}

// Eliminar duplicados: mantener solo el de menor ID por cada grupo (novedad_texto, numero_recibo, creado_por)
echo "\nBuscando duplicados...\n";
$groups = DB::table('prendas_pedido_novedades_recibo')
    ->select('novedad_texto', 'numero_recibo', 'creado_por', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
    ->groupBy('novedad_texto', 'numero_recibo', 'creado_por')
    ->havingRaw('COUNT(*) > 1')
    ->get();

echo "Grupos con duplicados: " . count($groups) . "\n";

$deletedTotal = 0;
foreach ($groups as $g) {
    $toDelete = DB::table('prendas_pedido_novedades_recibo')
        ->where('novedad_texto', $g->novedad_texto)
        ->where('numero_recibo', $g->numero_recibo)
        ->where('creado_por', $g->creado_por)
        ->where('id', '!=', $g->keep_id)
        ->pluck('id');
    
    echo "  Grupo '{$g->novedad_texto}' recibo={$g->numero_recibo}: mantengo id={$g->keep_id}, elimino IDs: " . $toDelete->implode(', ') . "\n";
    
    DB::table('prendas_pedido_novedades_recibo')
        ->whereIn('id', $toDelete->toArray())
        ->delete();
    
    $deletedTotal += count($toDelete);
}

echo "\n✅ Eliminados: {$deletedTotal} duplicados\n";
echo "Total final: " . DB::table('prendas_pedido_novedades_recibo')->count() . "\n";
