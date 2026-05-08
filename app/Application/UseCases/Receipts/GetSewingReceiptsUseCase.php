<?php

namespace App\Application\UseCases\Receipts;

use App\Application\Services\ReceiptEnricherService;
use App\Repositories\ConsecutivoReciboPedidoRepository;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * UseCase: Obtener recibos de costura avanzados con filtrado
 *
 * Responsabilidades:
 * - Orquestar obtencion y enriquecimiento de recibos
 * - Aplicar filtros
 * - Formatear respuesta
 */
class GetSewingReceiptsUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $recibosRepository,
        private ReceiptEnricherService $enricher
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(Request $request): array
    {
        $filtros = $this->procesarFiltros($request);
        $perPage = 25;
        $currentPage = max(1, (int) $request->input('page', 1));
        $hasTotalDiasFilter = isset($filtros['total_dias']) && is_array($filtros['total_dias']) && count($filtros['total_dias']) >= 1;

        // `total_dias` depende de `dias_calculados` (derivado tras enrich),
        // por eso se filtra en memoria antes de paginar para evitar paginas inconsistentes.
        if ($hasTotalDiasFilter) {
            $filtrosSinDias = $filtros;
            unset($filtrosSinDias['total_dias']);

            $recibosSinPaginar = $this->recibosRepository->getConFiltros('COSTURA', $filtrosSinDias, 0);
            $recibosEnriquecidos = $this->enricher->enriquecer($recibosSinPaginar->toArray());

            [$minDias, $maxDias] = $this->normalizarRangoDias($filtros['total_dias']);
            $recibosFiltrados = array_values(array_filter($recibosEnriquecidos, function (array $recibo) use ($minDias, $maxDias) {
                $dias = (int) ($recibo['dias_calculados'] ?? 0);
                return $dias >= $minDias && $dias <= $maxDias;
            }));

            $totalFiltrado = count($recibosFiltrados);
            $offset = ($currentPage - 1) * $perPage;
            $itemsPagina = array_slice($recibosFiltrados, $offset, $perPage);

            $paginator = new LengthAwarePaginator(
                $itemsPagina,
                $totalFiltrado,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return [
                'recibos' => $paginator,
                'total' => $totalFiltrado,
                'total_cantidad' => $this->calcularCantidadTotal($itemsPagina),
                'filtros_aplicados' => $filtros,
            ];
        }

        // Obtener recibos del repositorio (con filtros aplicados y paginacion)
        $recibosCostura = $this->recibosRepository->getConFiltros('COSTURA', $filtros, $perPage);

        // Verificar si es paginacion o coleccion
        $esPaginado = $recibosCostura instanceof LengthAwarePaginator;

        if ($esPaginado) {
            // Enriquecer solo los items de la pagina actual
            $recibosItems = $recibosCostura->getCollection()->toArray();
            $recibosConInfo = $this->enricher->enriquecer($recibosItems);
            $recibosCostura->setCollection(collect($recibosConInfo));

            $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

            return [
                'recibos' => $recibosCostura,
                'total' => $recibosCostura->total(),
                'total_cantidad' => $totalCantidad,
                'filtros_aplicados' => $filtros,
            ];
        }

        // Comportamiento original sin paginacion
        $recibosConInfo = $this->enricher->enriquecer($recibosCostura->toArray());
        $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

        return [
            'recibos' => $recibosConInfo,
            'total' => count($recibosConInfo),
            'total_cantidad' => $totalCantidad,
            'filtros_aplicados' => $filtros,
        ];
    }

    /**
     * Procesar filtros del request para convertir strings separados por coma en arrays
     */
    private function procesarFiltros(Request $request): array
    {
        $filtros = [];
        $camposFiltro = ['estado', 'area', 'total_dias', 'numero_recibo', 'cliente', 'descripcion', 'cantidad', 'novedades', 'fecha_creacion', 'fecha_estimada', 'encargado'];

        foreach ($camposFiltro as $campo) {
            if ($request->has($campo) && !empty($request->input($campo))) {
                $valor = $request->input($campo);

                if ($campo === 'total_dias') {
                    $filtros[$campo] = $this->parseTotalDiasFilter($valor);
                } elseif (is_string($valor)) {
                    $filtros[$campo] = array_map('trim', explode(',', $valor));
                } else {
                    $filtros[$campo] = $valor;
                }
            }
        }

        // Agregar termino de busqueda
        if ($request->has('search') && !empty($request->input('search'))) {
            $filtros['search'] = $request->input('search');
        }

        return $filtros;
    }

    /**
     * Acepta total_dias como:
     * - JSON string: "[5,14]"
     * - array: [5,14]
     * - string CSV legacy: "5,14"
     *
     * @param mixed $valor
     * @return array<int, int>
     */
    private function parseTotalDiasFilter(mixed $valor): array
    {
        if (is_array($valor)) {
            $numeros = array_values(array_filter(array_map(static fn ($v) => is_numeric($v) ? (int) $v : null, $valor), static fn ($v) => $v !== null));
            return $this->normalizarTotalDiasNumeros($numeros);
        }

        if (!is_string($valor) || trim($valor) === '') {
            return [];
        }

        $decoded = json_decode($valor, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $numeros = array_values(array_filter(array_map(static fn ($v) => is_numeric($v) ? (int) $v : null, $decoded), static fn ($v) => $v !== null));
            return $this->normalizarTotalDiasNumeros($numeros);
        }

        // Fallback robusto para strings corruptos:
        // ej. "15 días ... Rest: -" => [15, 15]
        preg_match_all('/-?\d+/', $valor, $matches);
        $numeros = array_map('intval', $matches[0] ?? []);
        return $this->normalizarTotalDiasNumeros($numeros);
    }

    /**
     * @param array<int, int> $numeros
     * @return array<int, int>
     */
    private function normalizarTotalDiasNumeros(array $numeros): array
    {
        if (count($numeros) === 0) {
            return [];
        }

        if (count($numeros) === 1) {
            return [$numeros[0], $numeros[0]];
        }

        return [$numeros[0], $numeros[1]];
    }

    /**
     * @param array<int, mixed> $rango
     * @return array{0:int,1:int}
     */
    private function normalizarRangoDias(array $rango): array
    {
        $min = isset($rango[0]) ? (int) $rango[0] : 0;
        $max = isset($rango[1]) ? (int) $rango[1] : $min;

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    /**
     * Calcular cantidad total de todos los recibos
     */
    private function calcularCantidadTotal(array $recibos): int
    {
        return array_reduce($recibos, function ($carry, $recibo) {
            return $carry + ($recibo['cantidad_total'] ?? 0);
        }, 0);
    }
}
