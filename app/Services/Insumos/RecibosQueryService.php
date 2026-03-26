<?php

namespace App\Services\Insumos;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class RecibosQueryService
{
    /**
     * Construye la query base para recibos de costura
     */
    public function buildBaseQuery()
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega'
            )
            ->where(function($q) {
                $q->where('pedidos_produccion.estado', 'PENDIENTE_INSUMOS')
                  ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                  ->orWhere(function($q2) {
                      $q2->where('pedidos_produccion.area', 'LIKE', '%Corte%')
                         ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                         ->orWhere('pedidos_produccion.area', 'LIKE', '%Creación%orden%')
                         ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                         ->orWhere('pedidos_produccion.area', 'LIKE', '%Creación de orden%')
                         ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
                  });
            });
    }

    /**
     * Aplica filtros a la query
     */
    public function applyFilters($query, array $filterColumns = [], array $filterValuesArray = [], array $filterValues = [], string $search = '')
    {
        // Múltiples filtros (nuevo sistema)
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            foreach ($filterColumns as $idx => $column) {
                if (isset($filterValuesArray[$idx])) {
                    $filterValue = $filterValuesArray[$idx];

                    if ($column === 'estado' && $filterValue === 'Pendiente Insumos') {
                        $filterValue = 'PENDIENTE_INSUMOS';
                    }

                    if ($column === 'numero_pedido') {
                        $column = 'pedidos_produccion.numero_pedido';
                    } elseif ($column === 'cliente') {
                        $column = 'pedidos_produccion.cliente';
                    } elseif ($column === 'estado') {
                        $column = 'pedidos_produccion.estado';
                    } elseif ($column === 'area') {
                        $column = 'pedidos_produccion.area';
                    } elseif ($column === 'created_at') {
                        $column = 'pedidos_produccion.created_at';
                    }

                    if (in_array($column, ['pedidos_produccion.numero_pedido', 'pedidos_produccion.cliente'])) {
                        $query->where($column, 'LIKE', "%{$filterValue}%");
                    } elseif ($column === 'pedidos_produccion.created_at') {
                        try {
                            $fecha = Carbon::createFromFormat('d/m/Y', $filterValue);
                            $query->whereDate($column, $fecha->format('Y-m-d'));
                        } catch (\Exception $e) {
                            \Log::warning("Error al convertir fecha: {$filterValue}");
                        }
                    } else {
                        $query->whereIn($column, [$filterValue]);
                    }
                }
            }
        } elseif (!empty($filterValues)) {
            // Fallback para filtro antiguo (singular)
            $filterValues = array_map(function($value) {
                return $value === 'Pendiente Insumos' ? 'PENDIENTE_INSUMOS' : $value;
            }, $filterValues);
            $query->whereIn('pedidos_produccion.estado', $filterValues);
        }

        // Búsqueda
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('consecutivos_recibos_pedidos.consecutivo_actual', 'LIKE', "%{$search}%")
                  ->orWhere('pedidos_produccion.numero_pedido', 'LIKE', "%{$search}%")
                  ->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Obtiene los IDs de parciales mencionados en notas
     */
    public function obtenerMapaParciales($recibos)
    {
        $parcialIds = $recibos
            ->map(function ($recibo) {
                $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    return (int) $matches[1];
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($parcialIds)) {
            return [];
        }

        return DB::table('pedidos_parciales')
            ->whereNull('deleted_at')
            ->whereIn('id', $parcialIds)
            ->pluck('created_at', 'id')
            ->map(function ($dt) {
                return $dt ? (string) $dt : null;
            })
            ->toArray();
    }

    /**
     * Transforma recibos crudos en objetos para la vista
     */
    public function transformarRecibos($recibos, $parcialCreatedAtMap, $calcularDiasCallback, $materialesMap = [])
    {
        return $recibos->map(function($recibo) use ($parcialCreatedAtMap, $calcularDiasCallback, $materialesMap) {
            $diasCalculados = 0;
            if ($recibo->created_at) {
                $fechaInicio = Carbon::parse($recibo->created_at);
                $diasCalculados = $calcularDiasCallback($fechaInicio);
            }

            $parcialId = null;
            $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
            if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                $parcialId = (int) $matches[1];
            }
            $esParcial = $parcialId !== null;

            $fechaInicioOrden = $recibo->created_at;
            if ($esParcial && $parcialId !== null && isset($parcialCreatedAtMap[$parcialId]) && $parcialCreatedAtMap[$parcialId]) {
                $fechaInicioOrden = $parcialCreatedAtMap[$parcialId];
            }

            // Obtener conteo de materiales para este pedido + prenda
            $numeroPedido = $recibo->numero_pedido;
            $prendaId = $recibo->prenda_id;
            $materialesKey = $numeroPedido . '_' . $prendaId;
            $cantidadMateriales = isset($materialesMap[$materialesKey]) ? $materialesMap[$materialesKey] : 0;

            return (object)[
                'id' => $recibo->id,
                'numero_pedido' => $recibo->consecutivo_actual,
                'numero_pedido_original' => $recibo->numero_pedido_original,
                'cliente' => $recibo->cliente,
                'estado' => $recibo->recibo_estado ?? $recibo->pedido_estado,
                'area' => $recibo->recibo_area ?? $recibo->pedido_area,
                'pedido_estado' => $recibo->pedido_estado,
                'created_at' => $fechaInicioOrden,
                'dia_de_entrega' => $recibo->dia_de_entrega,
                'fecha_estimada_de_entrega' => !empty($recibo->fecha_estimada_de_entrega) ? \Carbon\Carbon::parse($recibo->fecha_estimada_de_entrega)->format('d/m/Y') : null,
                'dias_calculados' => $diasCalculados,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'tipo_recibo' => $recibo->tipo_recibo,
                'marcar_plooter' => $recibo->marcar_plooter ?? false,
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $parcialId,
                'created_at' => $recibo->created_at,
                'updated_at' => $recibo->updated_at,
                'tiene_materiales' => $cantidadMateriales > 0,
                'cantidad_materiales' => $cantidadMateriales,
            ];
        });
    }

    /**
     * Obtiene el conteo de materiales registrados para cada número de pedido + prenda_id
     */
    public function obtenerMapaMateriales($recibos)
    {
        $materiales = $recibos
            ->map(function($recibo) {
                return [
                    'numero_pedido' => $recibo->numero_pedido,
                    'prenda_id' => $recibo->prenda_id,
                ];
            })
            ->unique(function($item) {
                return $item['numero_pedido'] . '_' . $item['prenda_id'];
            })
            ->values()
            ->all();

        if (empty($materiales)) {
            return [];
        }

        $result = [];
        foreach ($materiales as $material) {
            $count = DB::table('materiales_orden_insumos')
                ->where('numero_pedido', $material['numero_pedido'])
                ->where('prenda_id', $material['prenda_id'])
                ->count();
            
            $key = $material['numero_pedido'] . '_' . $material['prenda_id'];
            $result[$key] = $count;
        }

        return $result;
    }

    /**
     * Orquestación completa: Query -> Filtros -> Transformación -> Paginación
     * El controller NO debe hacer nada con queries
     */
    public function obtenerRecibosConPaginacion($request, callable $calcularDiasCallback)
    {
        \Log::info('RecibosQueryService: Iniciando obtención de recibos paginados');
        
        // 1. Construir query base
        $query = $this->buildBaseQuery();
        
        // 2. Obtener parámetros del request
        $search = $request->get('search', '');
        $filterColumns = (array) $request->get('filter_columns', []);
        $filterValuesArray = (array) $request->get('filter_values', []);
        $filterValues = (array) $request->get('filter_values', []);
        
        // 3. Aplicar filtros
        $query = $this->applyFilters($query, $filterColumns, $filterValuesArray, $filterValues, $search);
        
        // 4. Ejecutar query y obtener recibos
        $allRecibos = $query->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')->get();
        
        // 5. Obtener mapa de parciales
        try {
            $parcialCreatedAtMap = $this->obtenerMapaParciales($allRecibos);
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo parciales: ' . $e->getMessage());
            $parcialCreatedAtMap = [];
        }

        // 5.5 Obtener mapa de materiales
        try {
            $materialesMap = $this->obtenerMapaMateriales($allRecibos);
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo materiales: ' . $e->getMessage());
            $materialesMap = [];
        }
        
        // 6. Transformar recibos
        $recibosTransformados = $this->transformarRecibos($allRecibos, $parcialCreatedAtMap, $calcularDiasCallback, $materialesMap);
        
        // 7. Paginar manualmente
        $page = $request->get('page', 1);
        $perPage = 10;
        $total = $recibosTransformados->count();
        $items = $recibosTransformados->slice(($page - 1) * $perPage, $perPage)->values();
        
        // 8. Retornar paginador
        $paginador = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => route('insumos.materiales.index'),
                'query' => $request->query(),
            ]
        );
        
        $paginador->appends($request->query());
        \Log::info("RecibosQueryService completado: Total = {$total} recibos");
        
        return $paginador;
    }
}
