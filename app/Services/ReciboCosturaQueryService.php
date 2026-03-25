<?php

namespace App\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Repositories\ConsecutivoReciboPedidoRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ReciboCosturaQueryService
 * 
 * Servicio especializado para consultas de recibos de costura
 * Responsabilidades:
 * - Aplicar filtros dinámicos
 * - Mapear datos a estructura esperada por frontend
 * - Calcular campos derivados (días, cantidad, etc.)
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
        // Filtro por estado
        if (!empty($filters['estado'])) {
            $query->whereIn('estado', (array) $filters['estado']);
        }

        // Filtro por área
        if (!empty($filters['area'])) {
            $query->whereIn('area', (array) $filters['area']);
        }

        // Filtro por número de recibo
        if (!empty($filters['numero_recibo'])) {
            $query->whereIn('consecutivo_actual', (array) $filters['numero_recibo']);
        }

        // Filtro por cliente (en la relación pedido)
        if (!empty($filters['cliente'])) {
            $query->whereHas('pedido', function (Builder $q) use ($filters) {
                $q->whereIn('cliente', (array) $filters['cliente']);
            });
        }

        // Filtro por día de entrega
        if (!empty($filters['dia_entrega'])) {
            $query->whereHas('pedido', function (Builder $q) use ($filters) {
                // Convertir día de semana (nombre) a fecha
                $q->whereIn('dia_de_entrega', (array) $filters['dia_entrega']);
            });
        }

        // Filtro por rango de fecha de creación
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

        // Calcular cantidad total de la prenda
        $cantidadTotal = 0;
        if ($prenda && $prenda->tallas) {
            $cantidadTotal = $prenda->tallas->sum('cantidad');
        }

        // Construir descripción detallada
        $descripcion = $this->buildDescripcion($prenda);

        // Obtener el encargado más reciente
        $encargado = $this->getEncargado($pedido, $prenda);

        // Calcular días transcurridos
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
            
            // Información completa para detalles
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'estado_pedido' => $pedido->estado ?? 'N/A',
            'dia_entrega' => $pedido->dia_de_entrega ?? '-',
            'fecha_creacion_orden' => $pedido->created_at?->format('d/m/Y') ?? 'N/A',
            'tipo_recibo' => $recibo->tipo_recibo,
            'activo' => $recibo->activo,
        ];
    }

    /**
     * Construir descripción detallada de la prenda
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
     * Obtener el encargado más reciente de la prenda
     * 
     * @param PedidoProduccion|null $pedido
     * @param PrendaPedido|null $prenda
     * @return string
     */
    private function getEncargado(?object $pedido, ?object $prenda): string
    {
        if (!$prenda || !$pedido) {
            return '-';
        }

        // Buscar proceso más reciente para esta prenda en este pedido
        // procesos_prenda usa: numero_pedido (no pedido_produccion_id) y prenda_pedido_id
        $procesoMasReciente = \DB::table('procesos_prenda')
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where('prenda_pedido_id', $prenda->id)
            ->where('proceso', 'COSTURA')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($procesoMasReciente) {
            return $procesoMasReciente->encargado ?? '-';
        }

        return '-';
    }

    /**
     * Calcular días transcurridos desde creación
     * 
     * @param ConsecutivoReciboPedido $recibo
     * @return int
     */
    private function calculateDays(ConsecutivoReciboPedido $recibo): int
    {
        $fechaCreacion = $recibo->created_at;
        if (!$fechaCreacion) {
            return 0;
        }

        // Obtener festivos
        $festivos = \App\Models\Festivo::pluck('fecha')->toArray();

        // Calcular días hábiles
        $dias = 0;
        $fecha = $fechaCreacion->clone();
        $hoy = now();

        while ($fecha->format('Y-m-d') <= $hoy->format('Y-m-d')) {
            // No contar domingos (0) ni sábados (6)
            if ($fecha->dayOfWeek !== 0 && $fecha->dayOfWeek !== 6) {
                // No contar festivos
                if (!in_array($fecha->format('Y-m-d'), $festivos)) {
                    $dias++;
                }
            }
            $fecha->addDay();
        }

        return max(0, $dias - 1); // -1 para NO contar el día de creación
    }

    /**
     * Obtener opciones para filtros dinámicos
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
        ];
    }
}
