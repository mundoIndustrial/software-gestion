<?php

namespace App\Infrastructure\Persistence\Eloquent\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\ProcesoPrendaDetalleReadRepositoryInterface;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ProcesoPrendaDetalleReadRepository implements ProcesoPrendaDetalleReadRepositoryInterface
{
    public function paginarRecibosAprobados(array $tipoProcesoIds, ?string $search, bool $soloMinimalRole, ?string $areaFija, int $perPage = 20): LengthAwarePaginator
    {
        $query = PedidosProcesosPrendaDetalle::query()
            ->select([
                'pedidos_procesos_prenda_detalles.*',
                'palp.area as area',
                'palp.novedades as novedades',
                'palp.fechas_areas as fechas_areas',
            ])
            ->leftJoin('prenda_areas_logo_pedido as palp', 'palp.proceso_prenda_detalle_id', '=', 'pedidos_procesos_prenda_detalles.id')
            ->with([
                'tipoProceso',
                'prenda.pedidoProduccion.cliente',
                'prenda.pedidoProduccion.asesora',
            ])
            ->whereIn('tipo_proceso_id', $tipoProcesoIds)
            ->where('estado', 'APROBADO')
            ->whereNotNull('numero_recibo');

        if ($soloMinimalRole && $areaFija) {
            $query->where('palp.area', $areaFija);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_recibo', 'like', "%{$search}%")
                    ->orWhereHas('prenda.pedidoProduccion.cliente', function ($subQ) use ($search) {
                        $subQ->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');

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
