<?php

namespace App\Infrastructure\Repositories\RegistrosOrdenes;

use App\Domain\RegistrosOrdenes\Contracts\RegistroOrdenRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * EloquentRegistroOrdenRepository
 * 
 * Implementación Eloquent del contrato RegistroOrdenRepository
 */
class EloquentRegistroOrdenRepository implements RegistroOrdenRepository
{
    public function buildBaseQuery()
    {
        return PedidoProduccion::query()
            ->with(['asesora', 'cotizacion.tipoCotizacion']);
    }

    public function obtenerPorId($id): ?PedidoProduccion
    {
        return PedidoProduccion::find($id);
    }

    public function obtenerPorNumero($numero): ?PedidoProduccion
    {
        return PedidoProduccion::where('numero_pedido', $numero)
            ->with(['asesora', 'cotizacion.tipoCotizacion', 'prendas.tallas'])
            ->first();
    }

    public function listarConFiltros(array $filters, $search = null, $page = 1, $perPage = 25): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery();

        if ($search) {
            $query->where('numero_pedido', 'like', "%{$search}%")
                ->orWhere('cliente', 'like', "%{$search}%");
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function obtenerValoresUnicos($column): array
    {
        return PedidoProduccion::distinct()
            ->pluck($column)
            ->filter()
            ->toArray();
    }

    public function obtenerPrendas($registroId, $conRelaciones = true)
    {
        $query = PrendaPedido::where('pedido_produccion_id', $registroId);

        if ($conRelaciones) {
            $query->with(['fotos', 'tallas', 'procesos.tipoProceso', 'procesos.imagenes']);
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function obtenerProcesosPrenda($prendaId)
    {
        return \App\Models\ProcesoPrenda::where('prenda_pedido_id', $prendaId)
            ->with('tipoProceso')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function obtenerAnchoMetraje($registroId)
    {
        return [
            'ancho' => \App\Models\PedidoAnchoGeneral::where('pedido_produccion_id', $registroId)->first(),
            'metraje' => \App\Models\PedidoMetrajeColor::where('pedido_produccion_id', $registroId)->get(),
        ];
    }
}
