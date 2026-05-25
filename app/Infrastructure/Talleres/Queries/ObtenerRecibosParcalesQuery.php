<?php

namespace App\Infrastructure\Talleres\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObtenerRecibosParcalesQuery
{
    private int $rolTallerId;
    private ?string $search;

    public function __construct(int $rolTallerId, ?string $search = null)
    {
        $this->rolTallerId = $rolTallerId;
        $this->search = $search;
    }

    public function execute(): Collection
    {
        $query = DB::table('recibo_por_partes as rpp')
            ->leftJoin('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('recibos_por_partes_tallas as rpt', 'rpp.id', '=', 'rpt.recibo_por_partes_id')
            ->leftJoin('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->leftJoin('consecutivos_recibos_pedidos as crp_base', function ($join) {
                $join->on('rpp.pedido_produccion_id', '=', 'crp_base.pedido_produccion_id')
                    ->on('rpp.consecutivo_original', '=', 'crp_base.consecutivo_actual')
                    ->where('crp_base.tipo_recibo', '=', 'CORTE-PARA-BODEGA');
            })
            ->leftJoin('prenda_bodega as pb', 'crp_base.prenda_bodega_id', '=', 'pb.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->join('users as u', function ($join) {
                $join->whereRaw('LOWER(TRIM(u.name)) = LOWER(TRIM(ppren.encargado))');
            })
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA', 'CORTE-PARA-BODEGA'])
            ->whereRaw("TRIM(COALESCE(ppren.encargado, '')) <> ''")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) <> 'sin asignar'")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) NOT LIKE 'modulo%'")
            ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode($this->rolTallerId)])
            ;

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('rpp.consecutivo_parcial', 'like', "%{$this->search}%")
                  ->orWhere('clientes.nombre', 'like', "%{$this->search}%")
                  ->orWhere('pp.nombre_prenda', 'like', "%{$this->search}%")
                  ->orWhere('pb.nombre', 'like', "%{$this->search}%")
                  ->orWhere('pb.descripcion', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'rpp.id',
            'rpp.consecutivo_parcial as numero_recibo',
            'rpp.pedido_produccion_id',
            'rpp.prenda_pedido_id as prenda_id',
            DB::raw('COALESCE(pp.nombre_prenda, pb.nombre, "N/A") as nombre_prenda'),
            DB::raw('COALESCE(pp.descripcion, pb.descripcion, "N/A") as descripcion_prenda'),
            DB::raw('COALESCE(clientes.nombre, "Bodega") as cliente'),
            'rpp.tipo_recibo',
            'ppren.encargado as taller_encargado',
            'rpt.talla as talla_nombre',
            'rpt.cantidad as cantidad_talla',
            DB::raw('1 as es_parcial')
        )->get();
    }
}
