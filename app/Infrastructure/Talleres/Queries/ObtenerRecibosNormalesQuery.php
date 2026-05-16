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
        $query = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->join('pedidos_produccion as ppro', 'crp.pedido_produccion_id', '=', 'ppro.id')
            ->join('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->join('procesos_prenda as ppren', function($join) {
                $join->on('ppro.numero_pedido', '=', 'ppren.numero_pedido')
                     ->on('crp.consecutivo_actual', '=', 'ppren.numero_recibo')
                     ->where('ppren.proceso', '=', 'Costura');
            })
            ->join('users as u', 'ppren.encargado', '=', 'u.name')
            ->whereIn('crp.tipo_recibo', ['REFLECTIVO', 'COSTURA'])
            ->where('crp.area', '=', 'Costura')
            ->whereRaw("JSON_CONTAINS(u.roles_ids, ?)", [json_encode($this->rolTallerId)]);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('crp.consecutivo_actual', 'like', "%{$this->search}%")
                  ->orWhere('clientes.nombre', 'like', "%{$this->search}%")
                  ->orWhere('pp.nombre_prenda', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'crp.id',
            'crp.consecutivo_actual as numero_recibo',
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
    }
}
