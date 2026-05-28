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
     * Obtener todos los encargados únicos de los recibos de costura
     * Devuelve SOLO los encargados que se muestran en la tabla (proceso más reciente de cada recibo)
     * Sincronizado con la lógica de recibos-costura-table.blade.php línea 350-386
     *
     * @return array
     */
    public function getEncargados(): array
    {
        \Log::info('[ConsecutivoReciboPedidoRepository::getEncargados] Iniciando obtención de encargados');

        $query = DB::table('procesos_prenda as pp')
            ->join('consecutivos_recibos_pedidos as crp', function($join) {
                $join->on('pp.numero_recibo', '=', 'crp.consecutivo_actual')
                    ->on('pp.prenda_pedido_id', '=', 'crp.prenda_id');
            })
            ->join('pedidos_produccion as pp_prod', 'pp_prod.id', '=', 'crp.pedido_produccion_id')
            ->whereColumn('pp.numero_pedido', '=', 'pp_prod.numero_pedido')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', true)
            ->whereNull('pp.deleted_at')
            // Solo obtener el proceso más reciente por recibo (basado en el ID más alto)
            ->whereRaw('pp.id IN (
                SELECT MAX(pp2.id)
                FROM procesos_prenda pp2
                WHERE pp2.numero_recibo = pp.numero_recibo
                AND pp2.prenda_pedido_id = pp.prenda_pedido_id
                AND pp2.numero_pedido = pp.numero_pedido
                AND pp2.deleted_at IS NULL
                GROUP BY pp2.numero_recibo, pp2.prenda_pedido_id, pp2.numero_pedido
            )')
            ->distinct();

        \Log::info('[ConsecutivoReciboPedidoRepository::getEncargados] SQL Query: ' . $query->toSql());
        \Log::info('[ConsecutivoReciboPedidoRepository::getEncargados] Query Bindings: ' . json_encode($query->getBindings()));

        $encargados = $query->pluck('pp.encargado')
            ->filter()
            ->values()
            ->toArray();

        \Log::info('[ConsecutivoReciboPedidoRepository::getEncargados] Encargados obtenidos: ' . json_encode($encargados));

        return $encargados;
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
            $query->where('consecutivos_recibos_pedidos.area', '!=', 'INSUMOS');
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
            $query->whereIn('consecutivos_recibos_pedidos.area', $filtros['area']);
        } elseif (!empty($filtros['__exclude_despacho_default'])) {
            // Vista "Todos": excluir despacho desde query para mantener paginacion consistente.
            $query->whereRaw("UPPER(TRIM(COALESCE(consecutivos_recibos_pedidos.area, ''))) <> 'DESPACHO'");
        }

        // Aplicar filtro de cliente
        if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
            $query->whereHas('pedido', function($q) use ($filtros) {
                $q->whereIn('cliente', $filtros['cliente']);
            });
        }

        // Aplicar filtro de encargado (solo procesos más recientes)
        if (isset($filtros['encargado']) && !empty($filtros['encargado'])) {
            \Log::info('[getConFiltros] Aplicando filtro de encargado', ['encargados' => $filtros['encargado']]);
            $encargadosNormalizados = array_values(array_filter(array_map(
                static fn ($encargado) => mb_strtolower(trim((string) $encargado)),
                (array) $filtros['encargado']
            ), static fn ($encargado) => $encargado !== ''));

            $query->join('procesos_prenda as pp_filter', function($join) {
                $join->on('pp_filter.numero_recibo', '=', 'consecutivos_recibos_pedidos.consecutivo_actual')
                    ->on('pp_filter.prenda_pedido_id', '=', 'consecutivos_recibos_pedidos.prenda_id');
            })
            ->join('pedidos_produccion as pp_prod_filter', 'pp_prod_filter.id', '=', 'consecutivos_recibos_pedidos.pedido_produccion_id')
            ->whereColumn('pp_filter.numero_pedido', '=', 'pp_prod_filter.numero_pedido')
            ->whereIn(DB::raw('LOWER(TRIM(pp_filter.encargado))'), $encargadosNormalizados)
            ->whereNull('pp_filter.deleted_at')
            // Solo procesos más recientes (mismo subquery que getEncargados)
            ->whereRaw('pp_filter.id IN (
                SELECT MAX(pp2.id)
                FROM procesos_prenda pp2
                WHERE pp2.numero_recibo = pp_filter.numero_recibo
                AND pp2.prenda_pedido_id = pp_filter.prenda_pedido_id
                AND pp2.numero_pedido = pp_filter.numero_pedido
                AND pp2.deleted_at IS NULL
                GROUP BY pp2.numero_recibo, pp2.prenda_pedido_id, pp2.numero_pedido
            )')
            ->select('consecutivos_recibos_pedidos.*')
            ->distinct();
            \Log::info('[getConFiltros] Filtro de encargado aplicado correctamente');
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
