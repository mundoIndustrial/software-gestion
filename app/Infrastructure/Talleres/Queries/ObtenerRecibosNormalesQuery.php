<?php

namespace App\Infrastructure\Talleres\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObtenerRecibosNormalesQuery
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
        $queryCostura = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->join('users as u', function ($join) {
                $join->whereRaw('LOWER(TRIM(u.name)) = LOWER(TRIM(ppren.encargado))');
            })
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->whereRaw("TRIM(COALESCE(ppren.encargado, '')) <> ''")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) <> 'sin asignar'")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) NOT LIKE 'modulo%'")
            ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode($this->rolTallerId)]);

        if (!empty($this->search)) {
            $queryCostura->where(function($q) {
                $q->where('crp.consecutivo_actual', 'like', "%{$this->search}%")
                  ->orWhere('clientes.nombre', 'like', "%{$this->search}%")
                  ->orWhere('pp.nombre_prenda', 'like', "%{$this->search}%");
            });
        }

        $recibosCostura = $queryCostura->select(
            'crp.id',
            'crp.consecutivo_actual as numero_recibo',
            'crp.pedido_produccion_id',
            'pp.id as prenda_id',
            'pp.nombre_prenda',
            'pp.descripcion as descripcion_prenda',
            'clientes.nombre as cliente',
            'crp.tipo_recibo',
            'ppren.encargado as taller_encargado',
            'ppt.talla as talla_nombre',
            'ppt.cantidad as cantidad_talla',
            DB::raw('0 as es_parcial')
        )->get();

        $queryBodega = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prenda_bodega as pb', 'crp.prenda_bodega_id', '=', 'pb.id')
            ->join('prenda_tallas_bodega as ptb', 'pb.id', '=', 'ptb.prenda_bodega_id')
            ->join('procesos_prenda as ppren', function ($join) {
                $join->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                    ->whereRaw("LOWER(TRIM(ppren.proceso)) = 'costura'");
            })
            ->join('users as u', function ($join) {
                $join->whereRaw('LOWER(TRIM(u.name)) = LOWER(TRIM(ppren.encargado))');
            })
            ->where('crp.tipo_recibo', 'CORTE-PARA-BODEGA')
            ->whereRaw("LOWER(TRIM(COALESCE(crp.area, ''))) = 'costura'")
            ->whereRaw("TRIM(COALESCE(ppren.encargado, '')) <> ''")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) <> 'sin asignar'")
            ->whereRaw("LOWER(TRIM(COALESCE(ppren.encargado, ''))) NOT LIKE 'modulo%'")
            ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode($this->rolTallerId)]);

        if (!empty($this->search)) {
            $queryBodega->where(function ($q) {
                $q->where('crp.consecutivo_actual', 'like', "%{$this->search}%")
                    ->orWhere('pb.nombre', 'like', "%{$this->search}%")
                    ->orWhere('pb.descripcion', 'like', "%{$this->search}%");
            });
        }

        $recibosBodega = $queryBodega->select(
            'crp.id',
            'crp.consecutivo_actual as numero_recibo',
            DB::raw('NULL as pedido_produccion_id'),
            DB::raw('NULL as prenda_id'),
            'pb.nombre as nombre_prenda',
            'pb.descripcion as descripcion_prenda',
            DB::raw("'Bodega' as cliente"),
            'crp.tipo_recibo',
            'ppren.encargado as taller_encargado',
            'ptb.talla as talla_nombre',
            'ptb.cantidad as cantidad_talla',
            DB::raw('0 as es_parcial')
        )->get();

        return $recibosCostura->concat($recibosBodega);
    }
}
