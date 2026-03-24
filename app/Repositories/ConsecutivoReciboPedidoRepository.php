<?php

namespace App\Repositories;

use App\Models\ConsecutivoReciboPedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ConsecutivoReciboPedidoRepository
 * 
 * Responsabilidades:
 * - Construcción de queries base
 * - Acceso a datos de recibos
 * - Aplicación de relaciones eager loading
 */
class ConsecutivoReciboPedidoRepository
{
    /**
     * Construir query base para recibos de costura
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildBaseQuery(): Builder
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = ConsecutivoReciboPedido::query()
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->with([
                'pedido',
                'prenda.tallas',
                'prenda.coloresTelas'
            ])
            ->orderBy('created_at', 'desc');

        return $query;
    }

    /**
     * Obtener todos los recibos paginados sin filtros
     * 
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildBaseQuery()->paginate($perPage);
    }

    /**
     * Obtener un recibo por ID
     * 
     * @param int $id
     * @return ConsecutivoReciboPedido|null
     */
    public function findById(int $id): ?ConsecutivoReciboPedido
    {
        return $this->buildBaseQuery()->find($id);
    }

    /**
     * Obtener todos los estados únicos de recibos de costura
     * 
     * @return array
     */
    public function getEstados(): array
    {
        return ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->distinct()
            ->pluck('estado')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Obtener todas las áreas únicas de recibos de costura
     * 
     * @return array
     */
    public function getAreas(): array
    {
        return ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->distinct()
            ->pluck('area')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Obtener todos los números de recibo únicos de costura
     * 
     * @return array
     */
    public function getNumerosRecibo(): array
    {
        return ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->distinct()
            ->pluck('consecutivo_actual')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Obtener todos los clientes únicos de recibos de costura
     * 
     * @return array
     */
    public function getClientes(): array
    {
        return ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->distinct()
            ->pluck('pedidos_produccion.cliente')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Obtener todos los días de entrega únicos de recibos de costura
     * 
     * @return array
     */
    public function getDiasEntrega(): array
    {
        return ConsecutivoReciboPedido::where('tipo_recibo', 'COSTURA')
            ->where('activo', true)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->distinct()
            ->pluck('pedidos_produccion.dia_de_entrega')
            ->filter()
            ->values()
            ->toArray();
    }
}
