<?php

namespace App\Application\Lavanderia\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ObtenerOrdenesSeguimientoLavanderiaUseCase
{
    public function execute(int $page = 1, int $perPage = 25, string $search = ''): LengthAwarePaginator
    {
        $query = DB::table('lavanderia_movimiento_recibos as lmr')
            ->join('lavanderia_movimientos as lm', 'lm.id', '=', 'lmr.lavanderia_movimiento_id')
            ->join('consecutivos_recibos_pedidos as crp', 'crp.id', '=', 'lmr.consecutivo_recibo_pedido_id')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->leftJoin('clientes as c', 'c.id', '=', 'pp.cliente_id')
            ->leftJoin('prendas_pedido as pr', 'pr.id', '=', 'crp.prenda_id')
            ->leftJoin('prenda_bodega as pb', 'pb.id', '=', 'crp.prenda_bodega_id')
            ->selectRaw('
                crp.id as recibo_id,
                crp.consecutivo_actual as numero_recibo,
                crp.tipo_recibo,
                COALESCE(c.nombre, pp.cliente, "Sin cliente") as cliente,
                CASE
                    WHEN crp.tipo_recibo = "CORTE-PARA-BODEGA"
                        THEN COALESCE(pb.nombre, "Sin prenda")
                    ELSE COALESCE(pr.nombre_prenda, "Sin prenda")
                END as prenda,
                MAX(lm.fecha_movimiento) as ultima_fecha_movimiento
            ')
            ->groupBy(
                'crp.id',
                'crp.consecutivo_actual',
                'crp.tipo_recibo',
                'c.nombre',
                'pp.cliente',
                'pb.nombre',
                'pr.nombre_prenda'
            );

        if (trim($search) !== '') {
            $searchTerm = '%' . trim($search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('crp.consecutivo_actual', 'LIKE', $searchTerm)
                    ->orWhere('c.nombre', 'LIKE', $searchTerm)
                    ->orWhere('pp.cliente', 'LIKE', $searchTerm)
                    ->orWhere('pr.nombre_prenda', 'LIKE', $searchTerm)
                    ->orWhere('pb.nombre', 'LIKE', $searchTerm)
                    ->orWhere('crp.tipo_recibo', 'LIKE', $searchTerm);
            });
        }

        $paginator = $query
            ->orderByRaw('MAX(lm.fecha_movimiento) DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($item) {
            $numeroRecibo = (int) ($item->numero_recibo ?? 0);
            $tipoRecibo = (string) ($item->tipo_recibo ?? '');
            
            // Si es CORTE-PARA-BODEGA, mostrar "BODEGA" como cliente
            $cliente = (string) ($item->cliente ?? 'Sin cliente');
            if ($tipoRecibo === 'CORTE-PARA-BODEGA') {
                $cliente = 'BODEGA';
            }

            return [
                'recibo_id' => (int) ($item->recibo_id ?? 0),
                'numero_recibo' => $numeroRecibo,
                'tipo_recibo' => $tipoRecibo,
                'numero_recibo_tipo' => '#' . $numeroRecibo . '-' . $tipoRecibo,
                'cliente' => $cliente,
                'prenda' => (string) ($item->prenda ?? 'Sin prenda'),
                'ultima_fecha_movimiento' => $item->ultima_fecha_movimiento ?? null,
            ];
        })->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}
