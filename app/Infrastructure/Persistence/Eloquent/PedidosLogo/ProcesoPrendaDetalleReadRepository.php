<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProcesoPrendaDetalleReadRepository implements ProcesoPrendaDetalleReadRepositoryInterface
{
    public function paginarRecibosAprobados(array $tipoProcesoIds, ?string $search, bool $soloMinimalRole, ?string $areaFija, int $perPage = 20): LengthAwarePaginator
    {
        $tipoReciboCase = "CASE pedidos_procesos_prenda_detalles.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";

        $query = PedidosProcesosPrendaDetalle::query()
            ->select([
                'pedidos_procesos_prenda_detalles.*',
                'palp.area as area',
                'palp.novedades as novedades',
                'palp.fechas_areas as fechas_areas',
                DB::raw('COALESCE(ppar.consecutivo_actual, crp.consecutivo_actual) as numero_recibo_consecutivo'),
                DB::raw('COALESCE(ppar.created_at, crp.created_at) as fecha_creacion_recibo'),
                DB::raw('CASE WHEN ppar.id IS NOT NULL THEN 1 ELSE 0 END as es_parcial'),
                DB::raw('ppar.id as pedido_parcial_id'),
            ])
            ->leftJoin('prenda_areas_logo_pedido as palp', 'palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'pedidos_procesos_prenda_detalles.prenda_pedido_id')
            ->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('crp.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('crp.prenda_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->leftJoin('pedidos_parciales as ppar', function ($join) use ($tipoReciboCase) {
                $join->on('ppar.pedido_produccion_id', '=', 'pp.pedido_produccion_id')
                    ->on('ppar.prenda_pedido_id', '=', 'pp.id')
                    ->where('ppar.estado', 'APROBADO')
                    ->where('ppar.activo', 1)
                    ->whereNull('ppar.deleted_at')
                    ->whereRaw("ppar.tipo_recibo = ({$tipoReciboCase})");
            })
            ->with([
                'tipoProceso',
                'prenda.pedidoProduccion.cliente',
                'prenda.pedidoProduccion.asesora',
            ])
            ->whereIn('tipo_proceso_id', $tipoProcesoIds)
            ->where(function ($q) {
                $q->whereNotNull('ppar.id')
                    ->orWhere(function ($q2) {
                        $q2->where('pedidos_procesos_prenda_detalles.estado', 'APROBADO')
                            ->whereNotNull('crp.consecutivo_actual');
                    });
            });

        if ($soloMinimalRole && $areaFija) {
            $query->where('palp.area', $areaFija);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('crp.consecutivo_actual', 'like', "%{$search}%")
                    ->orWhere('ppar.consecutivo_actual', 'like', "%{$search}%")
                    ->orWhereHas('prenda.pedidoProduccion.cliente', function ($subQ) use ($search) {
                        $subQ->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderByRaw('COALESCE(ppar.consecutivo_actual, crp.consecutivo_actual) DESC');

        return $query->paginate($perPage);
    }

    public function obtenerPedidoProduccionIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        $proceso = PedidosProcesosPrendaDetalle::with('prenda.pedidoProduccion')
            ->select(['id', 'prenda_pedido_id', 'tipo_proceso_id'])
            ->find($procesoPrendaDetalleId);

        return $proceso?->prenda?->pedidoProduccion?->id;
    }

    public function obtenerPrendaPedidoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::query()
            ->where('id', $procesoPrendaDetalleId)
            ->value('prenda_pedido_id');
    }

    public function obtenerTipoProcesoIdPorProceso(int $procesoPrendaDetalleId): ?int
    {
        return PedidosProcesosPrendaDetalle::query()
            ->where('id', $procesoPrendaDetalleId)
            ->value('tipo_proceso_id');
    }
}
