<?php

namespace App\Application\EntregasTalleres\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuscarRecibosTallerUseCase
{
    public function execute(?string $term = null, int $limit = 0, ?int $tallerId = null)
    {
        $term = trim((string) $term);
        $tallerNombre = null;

        if ($tallerId) {
            $tallerNombre = User::findOrFail($tallerId)->name;
        }

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
            ->select(
                'crp.id', 
                'crp.consecutivo_actual as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'crp.tipo_recibo',
                DB::raw('0 as es_parcial')
            )
            ->distinct();

        if ($tallerNombre) {
            $queryNormales->whereRaw('TRIM(ppren.encargado) = ?', [trim($tallerNombre)]);
        }

        if ($term !== '') {
            $queryNormales->where(function($query) use ($term) {
                $query->where('crp.consecutivo_actual', 'LIKE', "%{$term}%")
                      ->orWhere('ppren.encargado', 'LIKE', "%{$term}%")
                      ->orWhere('pp.nombre_prenda', 'LIKE', "%{$term}%");
            });
        }

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
            ->select(
                'rpp.id', 
                'rpp.consecutivo_parcial as numero_recibo', 
                'pp.nombre_prenda', 
                'ppren.encargado', 
                'rpp.tipo_recibo',
                DB::raw('1 as es_parcial')
            )
            ->distinct();

        if ($tallerNombre) {
            $queryParciales->whereRaw('TRIM(ppren.encargado) = ?', [trim($tallerNombre)]);
        }

        if ($term !== '') {
            $queryParciales->where(function($query) use ($term) {
                $query->where('rpp.consecutivo_parcial', 'LIKE', "%{$term}%")
                      ->orWhere('ppren.encargado', 'LIKE', "%{$term}%")
                      ->orWhere('pp.nombre_prenda', 'LIKE', "%{$term}%");
            });
        }

        if ($limit > 0) {
            $queryParciales->limit($limit);
        }

        $recibosParciales = $queryParciales->get();

        return $recibosNormales
            ->concat($recibosParciales)
            ->unique(function ($r) {
                return $r->id . '|' . $r->es_parcial;
            })
            ->values()
            ->map(function($r) {
            $r->numero_recibo = $r->numero_recibo + 0;
            return $r;
        });
    }
}
