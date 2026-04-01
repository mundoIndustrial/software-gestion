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

        // Query 1: Procesos base sin parciales
        $queryProcesos = PedidosProcesosPrendaDetalle::query()
            ->selectRaw("
                pedidos_procesos_prenda_detalles.id,
                pedidos_procesos_prenda_detalles.prenda_pedido_id,
                pedidos_procesos_prenda_detalles.tipo_proceso_id,
                pedidos_procesos_prenda_detalles.estado,
                pedidos_procesos_prenda_detalles.numero_recibo,
                pedidos_procesos_prenda_detalles.tipo_recibo,
                pedidos_procesos_prenda_detalles.etiqueta_proceso,
                pedidos_procesos_prenda_detalles.notas_rechazo,
                pedidos_procesos_prenda_detalles.fecha_aprobacion,
                pedidos_procesos_prenda_detalles.aprobado_por,
                pedidos_procesos_prenda_detalles.datos_adicionales,
                pedidos_procesos_prenda_detalles.created_at,
                pedidos_procesos_prenda_detalles.updated_at,
                pedidos_procesos_prenda_detalles.deleted_at,
                MAX(palp.area) as area,
                MAX(palp.novedades) as novedades,
                MAX(palp.fechas_areas) as fechas_areas,
                crp.consecutivo_actual as numero_recibo_consecutivo,
                crp.created_at as fecha_creacion_recibo,
                NULL as fecha_activacion,
                0 as es_parcial,
                NULL as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->leftJoin('prenda_areas_logo_pedido as palp', function ($join) {
                $join->on('palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
                     ->whereNull('palp.pedido_parcial_id');
            })
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'pedidos_procesos_prenda_detalles.prenda_pedido_id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
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
            ->where('pedidos_procesos_prenda_detalles.estado', 'APROBADO')
            ->whereNotNull('crp.consecutivo_actual')
            ->whereNull('ppar.id')
            ->whereIn('pedidos_procesos_prenda_detalles.tipo_proceso_id', $tipoProcesoIds)
            ->groupBy('pedidos_procesos_prenda_detalles.id', 'crp.consecutivo_actual', 'crp.created_at', 'pp.pedido_produccion_id');

        // Query 2: Todos los parciales individuales
        $queryParciales = DB::table('pedidos_parciales as ppar')
            ->selectRaw("
                pedidos_procesos_prenda_detalles.id,
                ppar.prenda_pedido_id,
                pedidos_procesos_prenda_detalles.tipo_proceso_id,
                ppar.estado as estado,
                pedidos_procesos_prenda_detalles.numero_recibo,
                pedidos_procesos_prenda_detalles.tipo_recibo,
                pedidos_procesos_prenda_detalles.etiqueta_proceso,
                pedidos_procesos_prenda_detalles.notas_rechazo,
                pedidos_procesos_prenda_detalles.fecha_aprobacion,
                pedidos_procesos_prenda_detalles.aprobado_por,
                pedidos_procesos_prenda_detalles.datos_adicionales,
                pedidos_procesos_prenda_detalles.created_at,
                pedidos_procesos_prenda_detalles.updated_at,
                pedidos_procesos_prenda_detalles.deleted_at,
                MAX(palp.area) as area,
                MAX(palp.novedades) as novedades,
                MAX(palp.fechas_areas) as fechas_areas,
                ppar.consecutivo_actual as numero_recibo_consecutivo,
                ppar.created_at as fecha_creacion_recibo,
                ppar.fecha_activacion,
                1 as es_parcial,
                ppar.id as pedido_parcial_id,
                pp.pedido_produccion_id
            ")
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppar.prenda_pedido_id')
            ->join('pedidos_procesos_prenda_detalles', function ($join) use ($tipoReciboCase) {
                $join->on('pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'pp.id')
                    ->whereRaw("({$tipoReciboCase}) = ppar.tipo_recibo");
            })
            ->leftJoin('prenda_areas_logo_pedido as palp', function ($join) {
                $join->on('palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
                     ->on('palp.pedido_parcial_id', '=', 'ppar.id');
            })
            ->where('ppar.estado', 'APROBADO')
            ->where('ppar.activo', 1)
            ->whereNull('ppar.deleted_at')
            ->groupBy('ppar.id', 'pedidos_procesos_prenda_detalles.id', 'pp.pedido_produccion_id');

        // Combinar queries
        $results = $queryProcesos->unionAll($queryParciales)
            ->orderBy('numero_recibo_consecutivo', 'DESC')
            ->paginate($perPage);

        // Map items to ensure all attributes are available
        $results->getCollection()->transform(function ($item) {
            if (is_object($item)) {
                // Ensure pedido_parcial_id is accessible as an attribute
                if (!isset($item->pedido_parcial_id) && isset($item->attributes['pedido_parcial_id'])) {
                    $item->pedido_parcial_id = $item->attributes['pedido_parcial_id'];
                }
            }
            return $item;
        });

        return $results;
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
