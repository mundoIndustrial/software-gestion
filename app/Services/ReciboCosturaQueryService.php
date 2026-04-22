<?php

namespace App\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Repositories\ConsecutivoReciboPedidoRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * ReciboCosturaQueryService
 * 
 * Servicio especializado para consultas de recibos de costura
 * Responsabilidades:
 * - Aplicar filtros dinamicos
 * - Mapear datos a estructura esperada por frontend
 * - Calcular campos derivados (dias, cantidad, etc.)
 */
class ReciboCosturaQueryService
{
    protected $cacheService;
    protected $repository;

    public function __construct(CacheCalculosService $cacheService, ConsecutivoReciboPedidoRepository $repository)
    {
        $this->cacheService = $cacheService;
        $this->repository = $repository;
    }

    /**
     * Obtener query base para recibos de costura
     * Delega al Repository
     * 
     * @return Builder
     */
    public function getBaseQuery(): Builder
    {
        return $this->repository->buildBaseQuery();
    }

    /**
     * Aplicar filtros a la query
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Helper para procesar valores (pueden venir como array o como string separado por comas)
        $processValues = function($value) {
            if (is_array($value)) return $value;
            if (is_string($value) && str_contains($value, ',')) {
                return explode(',', $value);
            }
            return (array) $value;
        };

        // Filtros especiales para revisor_entregas
        $user = auth()->user();
        if ($user && $user->hasRole('revisor_entregas')) {
            // Excluir Areas: corte, insumos, creacion orden
            $query->whereNotIn(DB::raw('LOWER(TRIM(area))'), ['corte', 'insumos', 'creacion orden']);
        } else {
            // Filtro por estado (solo para otros roles)
            if (!empty($filters['estado'])) {
                $query->whereIn('estado', $processValues($filters['estado']));
            }

            // Filtro por Area (solo para otros roles)
            if (!empty($filters['area'])) {
                $query->whereIn('area', $processValues($filters['area']));
            }
        }

        // Filtro por numero de recibo
        if (!empty($filters['numero_recibo'])) {
            $query->whereIn('consecutivo_actual', $processValues($filters['numero_recibo']));
        }

        // Filtro por cliente (en la relacion pedido)
        if (!empty($filters['cliente'])) {
            $query->whereHas('pedido', function (Builder $q) use ($filters, $processValues) {
                $q->whereIn('cliente', $processValues($filters['cliente']));
            });
        }

        // Filtro por dia de entrega
        if (!empty($filters['dia_entrega'])) {
            $query->whereHas('pedido', function (Builder $q) use ($filters, $processValues) {
                // Convertir di­a de semana (nombre) a fecha
                $q->whereIn('dia_de_entrega', $processValues($filters['dia_entrega']));
            });
        }

        // Filtro por encargado
        if (!empty($filters['encargado'])) {
            $query->whereExists(function ($q) use ($filters, $processValues) {
                $q->select(DB::raw(1))
                    ->from('procesos_prenda')
                    ->join('pedidos_produccion', 'pedidos_produccion.numero_pedido', '=', 'procesos_prenda.numero_pedido')
                    ->whereColumn('procesos_prenda.numero_recibo', 'consecutivos_recibos_pedidos.consecutivo_actual')
                    ->whereColumn('pedidos_produccion.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                    ->whereColumn('procesos_prenda.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                    ->whereIn('procesos_prenda.encargado', $processValues($filters['encargado']))
                    ->whereNull('procesos_prenda.deleted_at');
            });
        }

        // Filtro por rango de fecha de creacion
        if (!empty($filters['fecha_creacion_desde'])) {
            $query->whereDate('created_at', '>=', $filters['fecha_creacion_desde']);
        }
        if (!empty($filters['fecha_creacion_hasta'])) {
            $query->whereDate('created_at', '<=', $filters['fecha_creacion_hasta']);
        }

        return $query;
    }

    /**
     * Obtener recibos paginados y mapeados
     * 
     * @param Builder $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedRecibos(Builder $query, int $perPage = 25): LengthAwarePaginator
    {
        $recibos = $query->paginate($perPage);
        
        // Mapear datos para el frontend
        $recibos->getCollection()->transform(function ($recibo) {
            return $this->mapRecibo($recibo);
        });

        return $recibos;
    }

    /**
     * Mapear datos de un recibo a estructura para frontend
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return array
     */
    public function mapRecibo(ConsecutivoReciboPedido $recibo): array
    {
        $pedido = $recibo->pedido;
        $prenda = $recibo->prenda;
        $metaParcial = $this->resolvePartialMeta($recibo);

        // Calcular cantidad total de la prenda
        $cantidadTotal = 0;
        if ($prenda && $prenda->tallas) {
            $cantidadTotal = $prenda->tallas->sum('cantidad');
        }

        // Construir descripcion detallada
        $descripcion = $this->buildDescripcion($prenda);

        // Obtener el encargado mas reciente
        $encargado = $this->getEncargado($pedido, $prenda, (int) $recibo->consecutivo_actual);

        // Calcular dias transcurridos
        $diasCalculados = $this->calculateDays($recibo);

        return [
            'id' => $recibo->id,
            'numero' => $recibo->consecutivo_actual,
            'pedido_id' => $pedido->id ?? null,
            'prenda_id' => $prenda->id ?? null,
            'estado' => $recibo->estado,
            'area' => $recibo->area,
            'dias_desde_creacion' => $diasCalculados,
            'nombre_prenda' => $prenda->nombre_prenda ?? 'N/A',
            'cliente' => $pedido->cliente ?? 'N/A',
            'cantidad' => $cantidadTotal,
            'descripcion' => $descripcion,
            'encargado' => $encargado,
            'fecha_creacion' => $recibo->created_at?->format('d/m/Y H:i') ?? 'N/A',
            'novedades' => $recibo->notas ?? '',
            
            // Informacion completa para detalles
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'estado_pedido' => $pedido->estado ?? 'N/A',
            'dia_entrega' => $pedido->dia_de_entrega ?? '-',
            'fecha_creacion_orden' => $pedido->created_at?->format('d/m/Y') ?? 'N/A',
            'tipo_recibo' => $recibo->tipo_recibo,
            'activo' => $recibo->activo,
            'es_parcial' => (bool) ($metaParcial['es_parcial'] ?? false),
            'pedido_parcial_id' => $metaParcial['pedido_parcial_id'] ?? null,
        ];
    }

    /**
     * Determina si este consecutivo corresponde a un pedido parcial activado.
     *
     * @param ConsecutivoReciboPedido $recibo
     * @return array{es_parcial: bool, pedido_parcial_id: ?int}
     */
    private function resolvePartialMeta(ConsecutivoReciboPedido $recibo): array
    {
        if (!$recibo->pedido_produccion_id || !$recibo->prenda_id || !$recibo->tipo_recibo || $recibo->consecutivo_actual === null) {
            return ['es_parcial' => false, 'pedido_parcial_id' => null];
        }

        $row = DB::table('pedidos_parciales')
            ->where('pedido_produccion_id', (int) $recibo->pedido_produccion_id)
            ->where('prenda_pedido_id', (int) $recibo->prenda_id)
            ->whereRaw('UPPER(tipo_recibo) = ?', [strtoupper((string) $recibo->tipo_recibo)])
            ->where('consecutivo_actual', $recibo->consecutivo_actual)
            ->orderByDesc('id')
            ->first(['id']);

        if (!$row) {
            return ['es_parcial' => false, 'pedido_parcial_id' => null];
        }

        return [
            'es_parcial' => true,
            'pedido_parcial_id' => (int) $row->id,
        ];
    }

    /**
     * Construir descripcion detallada de la prenda
     * 
     * @param PrendaPedido|null $prenda
     * @return string
     */
    private function buildDescripcion(?object $prenda): string
    {
        if (!$prenda) {
            return 'N/A';
        }

        $partes = [];

        // Nombre de prenda
        $partes[] = "PRENDA: " . ($prenda->nombre_prenda ?? 'N/A');

        // Telas y colores
        if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
            $telaColorStr = $prenda->coloresTelas->map(function ($ct) {
                $tela = $ct->tela ?? 'N/A';
                $color = $ct->color ?? 'N/A';
                return "{$tela} / {$color}";
            })->implode(', ');
            $partes[] = "TELAS: {$telaColorStr}";
        }

        // Tallas con cantidades
        if ($prenda->tallas && $prenda->tallas->count() > 0) {
            $tallasStr = $prenda->tallas->map(function ($t) {
                return "{$t->talla}: {$t->cantidad}";
            })->implode(', ');
            $partes[] = "TALLAS: {$tallasStr}";
        }

        return implode(' | ', $partes);
    }

    /**
     * Obtener el encargado mas reciente de la prenda
     * 
     * @param PedidoProduccion|null $pedido
     * @param PrendaPedido|null $prenda
     * @return string
     */
    private function getEncargado(?object $pedido, ?object $prenda, int $consecutivoActual): string
    {
        if (!$prenda || !$pedido) {
            return '-';
        }

        $proceso = \DB::table('procesos_prenda')
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where('prenda_pedido_id', $prenda->id)
            ->where('numero_recibo', $consecutivoActual)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->first();

        return $proceso?->encargado ?? '-';
    }

    /**
     * Calcular dias habiles transcurridos desde creacion.
     * Excluye fines de semana y festivos colombianos (via cmixin/business-day co-national).
     * El conteo empieza desde el dia siguiente a la creacion: si se crea un viernes,
     * el primer dia habil es el lunes siguiente = 1 dia.
     */
    private function calculateDays(ConsecutivoReciboPedido $recibo): int
    {
        $fechaCreacion = $recibo->created_at;
        if (!$fechaCreacion) {
            return 0;
        }

        $inicioConteo = $fechaCreacion->copy()->startOfDay()->addDay();
        $hoy = now()->startOfDay();

        if ($inicioConteo->gt($hoy)) {
            return 0;
        }

        $diasHabiles = 0;
        $fecha = $inicioConteo->copy();

        while ($fecha->lte($hoy)) {
            if ($fecha->isBusinessDay()) {
                $diasHabiles++;
            }
            $fecha->addDay();
        }

        return $diasHabiles;
    }
    /**
     * Obtener opciones para filtros dinamicos
     * 
     * @return array
     */
    public function getFilterOptions(): array
    {
        return [
            'estados' => $this->repository->getEstados(),
            'areas' => $this->repository->getAreas(),
            'numeros_recibo' => $this->repository->getNumerosRecibo(),
            'clientes' => $this->repository->getClientes(),
            'dias_entrega' => $this->repository->getDiasEntrega(),
            'encargados' => $this->repository->getEncargados(),
        ];
    }
}
