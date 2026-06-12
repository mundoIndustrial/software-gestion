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
                $join->on('rpp.consecutivo_original', '=', 'crp_base.consecutivo_actual')
                    ->where('crp_base.tipo_recibo', '=', 'CORTE-PARA-BODEGA')
                    ->whereColumn('crp_base.prenda_bodega_id', 'rpp.prenda_pedido_id');
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
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN NULL ELSE rpp.pedido_produccion_id END as pedido_produccion_id"),
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN NULL ELSE rpp.prenda_pedido_id END as prenda_id"),
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN COALESCE(pb.id, crp_base.prenda_bodega_id) ELSE NULL END as prenda_bodega_id"),
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN COALESCE(pb.nombre, 'N/A') ELSE COALESCE(pp.nombre_prenda, 'N/A') END as nombre_prenda"),
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN COALESCE(pb.descripcion, 'N/A') ELSE COALESCE(pp.descripcion, 'N/A') END as descripcion_prenda"),
            DB::raw("CASE WHEN UPPER(TRIM(rpp.tipo_recibo)) = 'CORTE-PARA-BODEGA' THEN 'Bodega' ELSE COALESCE(clientes.nombre, 'Bodega') END as cliente"),
            'rpp.tipo_recibo',
            'rpp.estado',
            'ppren.encargado as taller_encargado',
            DB::raw('COALESCE(ppren.fecha_de_asignacion_encargado, ppren.created_at) as fecha_salida'),
            'rpt.talla as talla_nombre',
            'rpt.genero as genero_nombre',
            'rpt.color_nombre as color_nombre',
            'rpt.cantidad as cantidad_talla',
            DB::raw('1 as es_parcial')
        )->get();
    }
}
