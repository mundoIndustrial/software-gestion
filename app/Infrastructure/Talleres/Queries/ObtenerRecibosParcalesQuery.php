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
            ->join('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->join('recibos_por_partes_tallas as rpt', 'rpp.id', '=', 'rpt.recibo_por_partes_id')
            ->join('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('rpp.prenda_pedido_id', '=', 'ppren.prenda_pedido_id')
                     ->on('rpp.consecutivo_parcial', '=', 'ppren.numero_recibo_parcial')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->join('users as u', 'ppren.encargado', '=', 'u.name')
            ->whereIn('rpp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode($this->rolTallerId)]);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('rpp.consecutivo_parcial', 'like', "%{$this->search}%")
                  ->orWhere('clientes.nombre', 'like', "%{$this->search}%")
                  ->orWhere('pp.nombre_prenda', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'rpp.id',
            'rpp.consecutivo_parcial as numero_recibo',
            'pp.nombre_prenda',
            'pp.descripcion as descripcion_prenda',
            'clientes.nombre as cliente',
            'rpp.tipo_recibo',
            'ppren.encargado as taller_encargado',
            'rpt.talla as talla_nombre',
            'rpt.cantidad as cantidad_talla',
            DB::raw('1 as es_parcial')
        )->get();
    }
}
