<?php

namespace App\Application\EntregasTalleres\UseCases;

use Illuminate\Support\Facades\DB;

class BuscarRecibosTallerUseCase
{
    public function execute(string $term, int $limit = 0)
    {
        // 1. Recibos normales
        $queryNormales = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura')
            ->where(function($query) use ($term) {
                $query->where('crp.consecutivo_actual', 'LIKE', "%$term%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$term%");
            })
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'crp.tipo_recibo',
                DB::raw('0 as es_parcial')
            );

        if ($limit > 0) {
            $queryNormales->limit($limit);
        }

        $recibosNormales = $queryNormales->get();

        // 2. Recibos parciales (ReciboPorPartes)
        $queryParciales = DB::table('recibo_por_partes as rpp')
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                     ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where(function($query) use ($term) {
                $query->where('rpp.consecutivo_parcial', 'LIKE', "%$term%")
                      ->orWhere('ppren.encargado', 'LIKE', "%$term%");
            })
            ->select(
                'rpp.id', 
                'rpp.consecutivo_parcial as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'rpp.tipo_recibo',
                DB::raw('1 as es_parcial')
            );

        if ($limit > 0) {
            $queryParciales->limit($limit);
        }

        $recibosParciales = $queryParciales->get();

        return $recibosNormales->concat($recibosParciales)->map(function($r) {
            $r->numero_recibo = $r->numero_recibo + 0;
            return $r;
        });
    }
}
