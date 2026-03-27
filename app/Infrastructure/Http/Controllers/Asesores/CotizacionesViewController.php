<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ContarCotizacionesPorEstadoUseCase;
use App\Application\Asesores\UseCases\ObtenerDatosCotizacionModalUseCase;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionesViewController - Controller para vistas de cotizaciones
 */
final class CotizacionesViewController extends Controller
{
    public function __construct(
        private readonly ListarCotizacionesHandler $listarHandler,
        private readonly ContarCotizacionesPorEstadoUseCase $contarCotizacionesPorEstadoUseCase,
        private readonly ObtenerDatosCotizacionModalUseCase $obtenerDatosCotizacionModalUseCase
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * GET /asesores/cotizaciones
     */
    public function index(Request $request)
    {
        try {
            $searchTerm = $request->query('search', '');

            $query = ListarCotizacionesQuery::crear(
                usuarioId: Auth::id(),
                pagina: $request->integer('pagina', 1),
                porPagina: 500
            );

            $cotizaciones = collect($this->listarHandler->handle($query))
                ->map(function ($cotizacionDto) {
                    $createdAt = $cotizacionDto->createdAt ?? $cotizacionDto->fechaInicio;

                    return (object) [
                        'id' => $cotizacionDto->id,
                        'numero_cotizacion' => $cotizacionDto->numeroCotizacion,
                        'tipo' => $cotizacionDto->tipo,
                        'tipo_cotizacion_id' => $cotizacionDto->tipoCotizacionId,
                        'estado' => $cotizacionDto->estado,
                        'es_borrador' => $cotizacionDto->esBorrador,
                        'cliente' => $cotizacionDto->cliente ?? 'Sin cliente',
                        'created_at' => Carbon::instance($createdAt),
                        'fecha_inicio' => $cotizacionDto->fechaInicio,
                        'fecha_envio' => $cotizacionDto->fechaEnvio,
                        'prendas' => $cotizacionDto->prendas,
                        'logoCotizacion' => $cotizacionDto->logo,
                        'tiene_logo' => !empty($cotizacionDto->logo),
                    ];
                });

            if (!empty($searchTerm)) {
                $searchLower = strtolower($searchTerm);
                $cotizaciones = $cotizaciones->filter(function ($cot) use ($searchLower) {
                    return stripos($cot->cliente, $searchLower) !== false
                        || stripos($cot->numero_cotizacion, $searchLower) !== false;
                });
            }

            \Log::info('CotizacionesViewController: Cotizaciones obtenidas', [
                'total' => $cotizaciones->count(),
                'usuario_id' => Auth::id(),
                'search_term' => $searchTerm,
                'sample' => $cotizaciones->first() ? (array) $cotizaciones->first() : null,
                'es_borrador_values' => $cotizaciones->pluck('es_borrador')->unique()->toArray(),
            ]);

            $cotizacionesEnviadas = $cotizaciones->filter(fn($c) => $c->es_borrador !== true && $c->es_borrador !== 1);

            $cotizacionesTodas = $this->paginate($cotizacionesEnviadas, 15);
            $cotizacionesPrenda = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'PL'), 15);
            $cotizacionesLogo = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'L'), 15);
            $cotizacionesPrendaBordado = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'PL'), 15);

            $borradoresCollection = $cotizaciones->filter(fn($c) => $c->es_borrador === true || $c->es_borrador === 1);
            $borradoresTodas = $this->paginate($borradoresCollection, 15);
            $borradorespPrenda = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'PL'), 15);
            $borradoresLogo = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'L'), 15);
            $borradorespPrendaBordado = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'PL'), 15);

            return view('asesores.cotizaciones.index', compact(
                'cotizacionesTodas',
                'cotizacionesPrenda',
                'cotizacionesLogo',
                'cotizacionesPrendaBordado',
                'borradoresTodas',
                'borradorespPrenda',
                'borradoresLogo',
                'borradorespPrendaBordado'
            ));
        } catch (\Exception $e) {
            \Log::error('CotizacionesViewController@index: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('asesores.cotizaciones.index', [
                'cotizacionesTodas' => collect([]),
                'cotizacionesPrenda' => collect([]),
                'cotizacionesLogo' => collect([]),
                'cotizacionesPrendaBordado' => collect([]),
                'borradoresTodas' => collect([]),
                'borradorespPrenda' => collect([]),
                'borradoresLogo' => collect([]),
                'borradorespPrendaBordado' => collect([]),
            ]);
        }
    }

    /**
     * GET /cotizaciones/{id}/datos
     */
    public function getDatosForModal(int|string $cotizacion): JsonResponse
    {
        try {
            $datos = $this->obtenerDatosCotizacionModalUseCase->ejecutar((int) $cotizacion);

            if ($datos === null) {
                \Log::warning('CotizacionesViewController@getDatosForModal: Cotizacion no encontrada', [
                    'cotizacion_id' => $cotizacion,
                ]);

                return $this->failure('Cotizacion no encontrada', 404);
            }

            return $this->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (\Exception $e) {
            \Log::error('CotizacionesViewController@getDatosForModal: Error', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacion,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failure('Error al obtener los datos', 500);
        }
    }

    /**
     * GET /pendientes-count
     */
    public function cotizacionesPendientesAprobadorCount(): JsonResponse
    {
        try {
            $count = $this->contarCotizacionesPorEstadoUseCase->ejecutar('APROBADA_CONTADOR');

            return $this->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            \Log::error('CotizacionesViewController@cotizacionesPendientesAprobadorCount: Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al obtener el contador', 500, [
                'count' => 0,
            ]);
        }
    }

    private function paginate($items, $perPage = 15)
    {
        $page = request()->get('page', 1);
        $items = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\Paginator(
            $items,
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'query' => request()->query(),
            ]
        );
    }
}
