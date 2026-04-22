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
                $search
            );

            $page = (int) $request->get('page', 1);
            $perPage = 10;

            $paginador = $query
                ->orderByRaw('COALESCE(consecutivos_recibos_pedidos.updated_at, consecutivos_recibos_pedidos.created_at) DESC')
                ->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')
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
