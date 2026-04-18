<?php

namespace App\Repositories;

use App\Models\ConsecutivoReciboPedido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->where('area', '!=', 'INSUMOS')
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
     * Obtener un recibo activo por ID y tipo (COSTURA, REFLECTIVO, etc.)
     * Carga la relación pedido para evitar queries adicionales.
     *
     * @param int $id
     * @param string $tipoRecibo
     * @return ConsecutivoReciboPedido|null
     */
    public function findByIdAndTipo(int $id, string $tipoRecibo): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::query()
            ->where('id', $id)
            ->where('tipo_recibo', $tipoRecibo)
            ->where('activo', 1)
            ->with(['pedido.prendas'])
            ->first();
    }

    /**
     * Recibos COSTURA en estado "En Ejecucion" / área "Corte" no vistos por el usuario.
     * Usa JOIN con pedidos_produccion para evitar N+1 queries.
     *
     * @param int $userId
     * @return Collection  colección de stdClass con campos: id, consecutivo_actual, cliente, numero_pedido, created_at
     */
    public function findEjecutandoEnCorteNoVistosPorUsuario(int $userId): Collection
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.estado', 'En Ejecucion')
            ->where('crp.area', 'Corte')
            ->where('crp.area', '!=', 'INSUMOS')
            ->where('crp.activo', 1)
            ->whereNotIn('crp.id', function ($q) use ($userId) {
                $q->select('consecutivo_recibo_id')
                    ->from('recibos_usuario_vistos')
                    ->where('user_id', $userId)
                    ->where('tipo_recibo', 'COSTURA');
            })
            ->select([
                'crp.id',
                'crp.consecutivo_actual',
                'crp.created_at',
                'pp.cliente',
                'pp.numero_pedido',
            ])
            ->get();
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
            ->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS')
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
            ->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS')
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
            ->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS')
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
            ->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS')
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
            ->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS')
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->distinct()
            ->pluck('pedidos_produccion.dia_de_entrega')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Obtener recibos de costura con filtros aplicados
     * 
     * @param string $tipoRecibo Tipo de recibo (COSTURA, REFLECTIVO, etc.)
     * @param array $filtros Array con filtros a aplicar
     * @param int $perPage Número de registros por página (default: todos sin paginación)
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Pagination\LengthAwarePaginator
     */
    public function getConFiltros(string $tipoRecibo, array $filtros = [], int $perPage = 0)
    {
        $query = ConsecutivoReciboPedido::query()
            ->where('tipo_recibo', $tipoRecibo)
            ->where('activo', true)
            ->with([
                'pedido',
                'prenda.tallas',
                'prenda.coloresTelas'
            ]);

        // Excluir INSUMOS solo para COSTURA (no aplica a REFLECTIVO u otros)
        if ($tipoRecibo === 'COSTURA') {
            $query->where('area', '!=', 'INSUMOS');
        }

        // Filtros especiales para revisor_entregas
        $user = auth()->user();
        if ($user && $user->hasRole('revisor_entregas')) {
            // Excluir áreas: corte, insumos, creacion orden
            $query->whereNotIn(DB::raw('LOWER(TRIM(area))'), ['corte', 'insumos', 'creacion orden']);
        } else {
            // Aplicar filtro de estado para otros roles (solo para COSTURA)
            if ($tipoRecibo === 'COSTURA') {
                if (isset($filtros['estado']) && !empty($filtros['estado'])) {
                    $query->whereIn('estado', $filtros['estado']);
                } elseif (empty($filtros)) {
                    // Si no hay filtros, excluir PENDIENTE_INSUMOS (solo para COSTURA)
                    $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
                }
            } elseif (isset($filtros['estado']) && !empty($filtros['estado'])) {
                // Para otros tipos de recibo, aplicar filtro de estado solo si se proporciona
                $query->whereIn('estado', $filtros['estado']);
            }
        }

        // Aplicar filtro de número de recibo (para todos)
        if (isset($filtros['numero_recibo']) && !empty($filtros['numero_recibo'])) {
            $query->where(function($q) use ($filtros) {
                foreach ($filtros['numero_recibo'] as $numero) {
                    $q->orWhere('consecutivo_actual', 'LIKE', '%' . $numero . '%');
                }
            });
        }

        // Aplicar filtro de área
        if (isset($filtros['area']) && !empty($filtros['area'])) {
            $query->whereIn('area', $filtros['area']);
        }

        // Aplicar filtro de cliente
        if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
            $query->whereHas('pedido', function($q) use ($filtros) {
                $q->whereIn('cliente', $filtros['cliente']);
            });
        }

        // Aplicar filtro de novedades
        if (isset($filtros['novedades']) && !empty($filtros['novedades'])) {
            $query->whereIn('novedades', $filtros['novedades']);
        }

        // Aplicar búsqueda general (por número de recibo o cliente)
        if (isset($filtros['search']) && !empty($filtros['search'])) {
            $searchTerm = $filtros['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('consecutivo_actual', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhereHas('pedido', function($pq) use ($searchTerm) {
                      $pq->where('cliente', 'LIKE', '%' . $searchTerm . '%');
                  });
            });
        }

        if ($tipoRecibo === 'COSTURA') {
            $query->orderByRaw('CASE WHEN aprobado_insumos_en IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('aprobado_insumos_en', 'desc')
                ->orderBy('consecutivo_actual', 'desc');
        } else {
            $query->orderBy('consecutivo_actual', 'desc');
        }

        // Aplicar paginación si se especifica perPage
        if ($perPage > 0) {
            return $query->paginate($perPage);
        }

        return $query->get();
    }
}
