<?php

namespace App\Application\Insumos\Services;

use App\Infrastructure\Insumos\ReadModels\RecibosCosturaReadRepository;
use App\Infrastructure\Insumos\ReadModels\RecibosMaterialesMapBuilder;
use App\Infrastructure\Insumos\ReadModels\RecibosViewTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RecibosQueryService
{
    public function __construct(
        private readonly RecibosCosturaReadRepository $repository,
        private readonly RecibosMaterialesMapBuilder $materialesMapBuilder,
        private readonly RecibosViewTransformer $transformer
    ) {
    }

    public function obtenerRecibosConPaginacion(
        $request,
        callable $calcularDiasCallback,
        string $tipoRecibo = 'COSTURA',
        string $routeName = 'insumos.materiales.index'
    ): LengthAwarePaginator
    {
        $timeStart = microtime(true);
        $traceId = (string) $request->header('X-Insumos-Trace-Id', '');
        \Log::info('═══ INICIO RecibosQueryService ═══', [
            'timestamp' => now()->toIso8601String(),
            'url' => $request->fullUrl(),
            'page' => $request->get('page', 1),
            'trace_id' => $traceId,
        ]);

        try {
            $timeQueryStart = microtime(true);

            $search = $request->get('search', '');
            $filterColumns = (array) $request->get('filter_columns', []);
            $filterValuesArray = (array) $request->get('filter_values', []);
            $filterValues = (array) $request->get('filter_values', []);

            if (empty($filterColumns) && empty($filterValuesArray)) {
                $singleColumn = $request->get('filter_column');
                $singleValue = $request->get('filter_value');
                if ($singleColumn !== null && $singleValue !== null && $singleValue !== '') {
                    $filterColumns = [(string) $singleColumn];
                    $filterValuesArray = [(string) $singleValue];
                    $filterValues = [(string) $singleValue];
                }
            }

            \Log::info('[RecibosQueryService] Parametros normalizados', [
                'trace_id' => $traceId,
                'search' => $search,
                'filter_columns' => $filterColumns,
                'filter_values_array' => $filterValuesArray,
                'has_filters' => !empty($filterColumns) || !empty($filterValuesArray) || !empty($search),
            ]);

            $hasFilters = !empty($filterColumns) || !empty($filterValuesArray) || !empty($search);
            if ($hasFilters) {
                $query = $this->repository->buildBaseQueryForFiltering($tipoRecibo);
            } else {
                $query = $this->repository->buildBaseQuery($tipoRecibo);
            }

            $query = $this->repository->applyFilters(
                $query,
                $filterColumns,
                $filterValuesArray,
                $filterValues,
                $search,
                $tipoRecibo
            );

            $page = (int) $request->get('page', 1);
            $perPage = 10;

            // 🔧 ORDENAMIENTO: Primero por número de recibo (descendente), luego por fecha
            $paginador = $query
                ->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')
                ->orderByRaw('COALESCE(consecutivos_recibos_pedidos.ultima_actividad, consecutivos_recibos_pedidos.created_at) DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            $timeQueryEnd = microtime(true);
            $durationQuery = round(($timeQueryEnd - $timeQueryStart) * 1000, 2);

            /** @var Collection<int, object> $recibosPagina */
            $recibosPagina = $paginador->getCollection();
            \Log::info('✓ Query BD completada', [
                'trace_id' => $traceId,
                'duration_ms' => $durationQuery,
                'total_recibos' => $paginador->total(),
                'items_pagina' => $recibosPagina->count(),
                'sample_consecutivos' => $recibosPagina->pluck('consecutivo_actual')->filter()->take(10)->values()->all(),
            ]);

            // Logs detallados de ordenamiento
            $detallesOrdenamiento = $recibosPagina->map(function ($recibo) {
                $pedidoId = $recibo->pedido_produccion_id ?? 'N/A';
                $consecutivo = $recibo->consecutivo_actual ?? 'N/A';
                $fechaCreacion = $recibo->created_at ?? 'N/A';

                // Obtener la fecha más reciente de prendas
                $ultimaModificacionPrenda = \DB::table('prendas_pedido')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->max('updated_at') ?? 'sin prendas';

                return [
                    'consecutivo' => $consecutivo,
                    'pedido_id' => $pedidoId,
                    'fecha_creacion_recibo' => $fechaCreacion,
                    'ultima_mod_prenda' => $ultimaModificacionPrenda,
                ];
            })->values()->all();

            \Log::info('📊 DETALLES DE ORDENAMIENTO (primeros 10 recibos)', [
                'trace_id' => $traceId,
                'page' => $page,
                'detalles' => array_slice($detallesOrdenamiento, 0, 10),
            ]);

            // Buscar específicamente el 204
            $recibo204 = $recibosPagina->firstWhere('consecutivo_actual', '204');
            if ($recibo204) {
                $pedido204 = \DB::table('pedidos_produccion')->find($recibo204->pedido_produccion_id);
                $prendas204 = \DB::table('prendas_pedido')
                    ->where('pedido_produccion_id', $recibo204->pedido_produccion_id)
                    ->get(['id', 'nombre_prenda', 'created_at', 'updated_at']);

                \Log::info('🔍 DETALLES DEL RECIBO #204', [
                    'trace_id' => $traceId,
                    'pedido_id' => $recibo204->pedido_produccion_id,
                    'fecha_recibo' => $recibo204->created_at,
                    'pedido_created_at' => $pedido204->created_at ?? null,
                    'pedido_updated_at' => $pedido204->updated_at ?? null,
                    'prendas_count' => count($prendas204),
                    'prendas_details' => $prendas204->toArray(),
                ]);
            } else {
                \Log::info('⚠️ RECIBO #204 NO ENCONTRADO EN PÁGINA ACTUAL', [
                    'trace_id' => $traceId,
                    'page' => $page,
                ]);
            }

            $timeMapsStart = microtime(true);

            try {
                $parcialCreatedAtMap = $this->repository->obtenerMapaParciales($recibosPagina);
            } catch (\Exception $e) {
                \Log::warning('⚠ Error obteniendo parciales: ' . $e->getMessage());
                $parcialCreatedAtMap = [];
            }

            try {
                $materialesMap = $this->materialesMapBuilder->build($recibosPagina);
            } catch (\Exception $e) {
                \Log::warning('⚠ Error obteniendo materiales: ' . $e->getMessage());
                $materialesMap = [];
            }

            $timeMapsEnd = microtime(true);
            $durationMaps = round(($timeMapsEnd - $timeMapsStart) * 1000, 2);

            \Log::info('✓ Maps obtenidos', [
                'trace_id' => $traceId,
                'duration_ms' => $durationMaps,
                'parciales_count' => count($parcialCreatedAtMap),
                'materiales_count' => count($materialesMap),
            ]);

            $timeTransformStart = microtime(true);

            $recibosTransformados = $this->transformer->transform(
                $recibosPagina,
                $parcialCreatedAtMap,
                $calcularDiasCallback,
                $materialesMap
            );

            $timeTransformEnd = microtime(true);
            $durationTransform = round(($timeTransformEnd - $timeTransformStart) * 1000, 2);

            \Log::info('✓ Transformación completada', [
                'trace_id' => $traceId,
                'duration_ms' => $durationTransform,
            ]);

            $paginadorUrl = route($routeName);
            $paginador->setCollection($recibosTransformados->values());
            $paginador->setPath($paginadorUrl);
            $paginador->appends($request->query());

            $timeEnd = microtime(true);
            $durationTotal = round(($timeEnd - $timeStart) * 1000, 2);

            \Log::info('═══ FIN RecibosQueryService ═══', [
                'trace_id' => $traceId,
                'duration_total_ms' => $durationTotal,
                'breakdown' => [
                    'query_ms' => $durationQuery,
                    'maps_ms' => $durationMaps,
                    'transform_ms' => $durationTransform,
                    'other_ms' => round($durationTotal - $durationQuery - $durationMaps - $durationTransform, 2),
                ],
                'total_recibos' => $paginador->total(),
            ]);

            return $paginador;
        } catch (\Exception $e) {
            \Log::error('ERROR CRITICO en RecibosQueryService', [
                'trace_id' => $request->header('X-Insumos-Trace-Id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }
}
