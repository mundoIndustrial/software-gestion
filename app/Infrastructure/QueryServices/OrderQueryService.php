<?php

namespace App\Infrastructure\QueryServices;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use App\Models\PedidoProduccion;
use App\Domain\Pedidos\PedidoConstants;
use Illuminate\Support\Facades\DB;

/**
 * Query Service para operaciones complejas de lectura sobre órdenes
 * Se encarga de:
 * - Construir queries complejas
 * - Aplicar filtros avanzados
 * - Paginación
 * - Transformación de datos para presentación
 */
class OrderQueryService
{
    /**
     * Obtener todas las opciones disponibles para filtros
     */
    public function getFilterOptions(): array
    {
        return [
            'estados' => PedidoConstants::ESTADOS,
            'dias_entrega' => PedidoConstants::DIAS_ENTREGA,
            'areas' => $this->getDistinctAreas(),
            'clientes' => $this->getDistinctClientes(),
            'asesores' => $this->getDistinctAsesores(),
            'formas_pago' => $this->getDistinctFormasPago(),
            'encargados' => $this->getDistinctEncargados(),
        ];
    }

    /**
     * Obtener opciones de columna específica
     */
    public function getColumnFilterOptions(string $column, string $search = '', int $page = 1, int $limit = 25): array
    {
        $options = match ($column) {
            'estado' => PedidoConstants::ESTADOS,
            'area' => $this->getDistinctAreas(),
            'dia_entrega' => PedidoConstants::DIAS_ENTREGA,
            'pedido' => $this->searchPedidoNumbers($search),
            'cliente' => $this->searchClientes($search),
            'asesor' => $this->getDistinctAsesores(),
            'forma_pago' => $this->getDistinctFormasPago(),
            'encargado' => $this->getDistinctEncargados(),
            default => []
        };

        // Filtrar por búsqueda
        if (!empty($search)) {
            $options = array_filter($options, fn($item) => 
                stripos(is_array($item) ? $item['display'] : $item, $search) !== false
            );
            $options = array_values($options);
        }

        // Aplicar paginación
        $total = count($options);
        $offset = ($page - 1) * $limit;
        $paginated = array_slice($options, $offset, $limit);

        return [
            'options' => $paginated,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Filtrar órdenes por criterios
     */
    public function filterOrders(array $filters, int $page = 1, int $perPage = 25): array
    {
        $query = PedidoProduccion::query();

        // Aplicar seguridad por defecto
        $query->whereNotNull('numero_pedido')
              ->where('numero_pedido', '>', 0);

        // Aplicar filtros específicos
        $this->applyFilterCriteria($query, $filters);

        $total = $query->count();
        
        $ordenes = $query
            ->with(['prendas.tallas', 'asesora', 'procesos' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $this->formatOrdersForDisplay($ordenes->items()),
            'pagination' => [
                'current_page' => $ordenes->currentPage(),
                'total' => $ordenes->total(),
                'per_page' => $ordenes->perPage(),
                'last_page' => $ordenes->lastPage(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ]
        ];
    }

    /**
     * Buscar órdenes por término
     */
    public function searchOrders(string $search, int $page = 1, int $limit = 25): array
    {
        if (strlen($search) < 1) {
            return ['ordenes' => [], 'pagination' => []];
        }

        $query = PedidoProduccion::where('numero_pedido', 'LIKE', '%' . $search . '%')
            ->orWhere('cliente', 'LIKE', '%' . $search . '%');

        $total = $query->count();
        
        $ordenes = $query->select([
            'id', 'numero_pedido', 'cliente', 'estado', 'area',
            'dia_de_entrega', 'created_at',
            'fecha_estimada_de_entrega', 'novedades', 'forma_de_pago',
            'asesor_id', 'created_at', 'updated_at'
        ])->with(['prendas.tallas', 'asesora'])
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'ordenes' => $this->formatOrdersForDisplay($ordenes->items()),
            'pagination' => [
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'per_page' => $ordenes->perPage(),
                'total' => $ordenes->total(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ]
        ];
    }

    // =========== Métodos privados de ayuda ===========

    private function getDistinctAreas(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('area')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getDistinctClientes(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('cliente')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getDistinctAsesores(): array
    {
        return PedidoProduccion::with('asesora')
            ->get()
            ->pluck('asesora.name')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getDistinctFormasPago(): array
    {
        return PedidoProduccion::distinct()
            ->pluck('forma_de_pago')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function getDistinctEncargados(): array
    {
        return PedidoProduccion::with(['procesos' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->get()
            ->map(fn($orden) => $orden->procesos->first()?->encargado)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    private function searchPedidoNumbers(string $search): array
    {
        $query = PedidoProduccion::distinct();
        if (!empty($search)) {
            $query->where('numero_pedido', 'LIKE', "%{$search}%");
        }
        return $query->pluck('numero_pedido')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function searchClientes(string $search): array
    {
        $query = PedidoProduccion::distinct();
        if (!empty($search)) {
            $query->where('cliente', 'LIKE', "%{$search}%");
        }
        return $query->pluck('cliente')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    private function applyFilterCriteria(Builder $query, array $filters): void
    {
        // Aplicar filtros según tipo
        if (isset($filters['estado']) && !empty($filters['estado'])) {
            $query->whereIn('estado', $filters['estado']);
        }

        if (isset($filters['area']) && !empty($filters['area'])) {
            $query->whereIn('area', $filters['area']);
        }

        if (isset($filters['cliente']) && !empty($filters['cliente'])) {
            $query->where(function($q) use ($filters) {
                foreach ($filters['cliente'] as $value) {
                    $q->orWhere('cliente', 'LIKE', '%' . $value . '%');
                }
            });
        }

        // Agregar más filtros según sea necesario
    }

    private function formatOrdersForDisplay(array $ordenes): array
    {
        return array_map(function($orden) {
            return [
                'id' => $orden->id,
                'numero_pedido' => $orden->numero_pedido,
                'cliente' => $orden->cliente,
                'estado' => $orden->estado,
                'area' => $orden->area,
                'dia_de_entrega' => $orden->dia_de_entrega,
                'fecha_creacion' => $orden->created_at?->format('d/m/Y'),
                'fecha_estimada' => $orden->fecha_estimada_de_entrega?->format('d/m/Y'),
                'asesor' => $orden->asesora?->name ?? '-',
                'forma_de_pago' => $orden->forma_de_pago,
                'novedades' => $orden->novedades,
            ];
        }, $ordenes);
    }
}
