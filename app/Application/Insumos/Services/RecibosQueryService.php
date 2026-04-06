<?php

namespace App\Application\Insumos\Services;

use App\Infrastructure\Insumos\ReadModels\RecibosCosturaReadRepository;
use App\Infrastructure\Insumos\ReadModels\RecibosMaterialesMapBuilder;
use App\Infrastructure\Insumos\ReadModels\RecibosViewTransformer;
use Illuminate\Pagination\LengthAwarePaginator;

class RecibosQueryService
{
    public function __construct(
        private readonly RecibosCosturaReadRepository $repository,
        private readonly RecibosMaterialesMapBuilder $materialesMapBuilder,
        private readonly RecibosViewTransformer $transformer
    ) {
    }

    public function obtenerRecibosConPaginacion($request, callable $calcularDiasCallback): LengthAwarePaginator
    {
        \Log::info(' RecibosQueryService: Iniciando obtencion de recibos paginados', [
            'url' => $request->fullUrl(),
            'page' => $request->get('page', 1),
        ]);

        try {
            // Obtener parámetros de filtro
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

            // Si hay filtros o búsqueda, usar query sin los filtros por defecto
            $hasFilters = !empty($filterColumns) || !empty($filterValuesArray) || !empty($search);
            if ($hasFilters) {
                \Log::info(' Aplicando query con filtros personalizados (sin filtros por defecto)');
                $query = $this->repository->buildBaseQueryForFiltering();
            } else {
                \Log::info(' Usando query base con filtros por defecto');
                $query = $this->repository->buildBaseQuery();
            }
            
            \Log::info(' Base query construida exitosamente');

            $query = $this->repository->applyFilters(
                $query,
                $filterColumns,
                $filterValuesArray,
                $filterValues,
                $search
            );
            \Log::info(' Filtros aplicados');

            $allRecibos = $query->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')->get();
            \Log::info(' Recibos obtenidos de BD', ['total' => $allRecibos->count()]);

            try {
                $parcialCreatedAtMap = $this->repository->obtenerMapaParciales($allRecibos);
                \Log::info(' Mapa de parciales obtenido');
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo parciales: ' . $e->getMessage());
                $parcialCreatedAtMap = [];
            }

            try {
                $materialesMap = $this->materialesMapBuilder->build($allRecibos);
                \Log::info(' Mapa de materiales obtenido');
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo materiales: ' . $e->getMessage());
                $materialesMap = [];
            }

            $recibosTransformados = $this->transformer->transform(
                $allRecibos,
                $parcialCreatedAtMap,
                $calcularDiasCallback,
                $materialesMap
            );
            \Log::info(' Recibos transformados');

            $page = (int) $request->get('page', 1);
            $perPage = 10;
            $total = $recibosTransformados->count();
            $items = $recibosTransformados->slice(($page - 1) * $perPage, $perPage)->values();

            $paginadorUrl = route('insumos.materiales.index');
            \Log::info(' Configurando paginador', [
                'total' => $total,
                'plan' => $page,
                'pagina_url' => $paginadorUrl,
            ]);

            $paginador = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => $paginadorUrl,
                    'query' => $request->query(),
                ]
            );

            $paginador->appends($request->query());
            \Log::info(" RecibosQueryService completado: Total = {$total} recibos");

            return $paginador;
        } catch (\Exception $e) {
            \Log::error('ERROR CRITICO en RecibosQueryService', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            throw $e;
        }
    }
}
