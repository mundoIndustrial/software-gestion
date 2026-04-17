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

            $page = (int) $request->get('page', 1);
            $perPage = 10;

            // Orden tipo "correo" por actividad real de la prenda.
            // Prioriza cambios en prenda/tallas/colores/variantes; si no hay, usa timestamps del recibo.
            $paginador = $query
                ->orderByRaw('COALESCE(actividad_prenda_en, consecutivos_recibos_pedidos.updated_at, consecutivos_recibos_pedidos.created_at) DESC')
                ->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            /** @var Collection<int, object> $recibosPagina */
            $recibosPagina = $paginador->getCollection();
            \Log::info(' Recibos obtenidos de BD (paginado)', [
                'pagina' => $paginador->currentPage(),
                'pagina_total_items' => $recibosPagina->count(),
                'total_general' => $paginador->total(),
            ]);

            try {
                $parcialCreatedAtMap = $this->repository->obtenerMapaParciales($recibosPagina);
                \Log::info(' Mapa de parciales obtenido');
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo parciales: ' . $e->getMessage());
                $parcialCreatedAtMap = [];
            }

            try {
                $materialesMap = $this->materialesMapBuilder->build($recibosPagina);
                \Log::info(' Mapa de materiales obtenido');
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo materiales: ' . $e->getMessage());
                $materialesMap = [];
            }

            $recibosTransformados = $this->transformer->transform(
                $recibosPagina,
                $parcialCreatedAtMap,
                $calcularDiasCallback,
                $materialesMap
            );
            \Log::info(' Recibos transformados');

            $paginadorUrl = route('insumos.materiales.index');
            \Log::info(' Configurando paginador', [
                'total' => $paginador->total(),
                'plan' => $page,
                'pagina_url' => $paginadorUrl,
            ]);

            $paginador->setCollection($recibosTransformados->values());
            $paginador->setPath($paginadorUrl);
            $paginador->appends($request->query());
            \Log::info(" RecibosQueryService completado: Total = {$paginador->total()} recibos");

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
