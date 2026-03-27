<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ContarCotizacionesPorEstadoUseCase;
use App\Application\Asesores\UseCases\ObtenerDatosCotizacionModalUseCase;
use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionesViewController - Controller para vistas de cotizaciones
 * 
 * Responsabilidad: Retornar vistas HTML con datos obtenidos de Handlers DDD
 */
final class CotizacionesViewController extends Controller
{
    public function __construct(
        private readonly ListarCotizacionesHandler $listarHandler,
        private readonly ContarCotizacionesPorEstadoUseCase $contarCotizacionesPorEstadoUseCase,
        private readonly ObtenerDatosCotizacionModalUseCase $obtenerDatosCotizacionModalUseCase
    ) {
    }

    /**
     * Mostrar vista de cotizaciones del usuario
     * GET /asesores/cotizaciones
     * GET /asesores/cotizaciones?search=MINCIVIL
     */
    public function index(\Illuminate\Http\Request $request)
    {
        try {
            // Obtener parámetro de búsqueda
            $searchTerm = $request->query('search', '');
            
            $query = ListarCotizacionesQuery::crear(
                usuarioId: Auth::id(),
                pagina: $request->integer('pagina', 1),
                porPagina: 500
            );

            $cotizaciones = collect($this->listarHandler->handle($query))
                ->map(function ($cotizacionDto) {
                    $createdAt = $cotizacionDto->createdAt ?? $cotizacionDto->fechaInicio;

                    return (object)[
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

            // Aplicar filtro de búsqueda si existe
            if (!empty($searchTerm)) {
                $searchLower = strtolower($searchTerm);
                $cotizaciones = $cotizaciones->filter(function($cot) use ($searchLower) {
                    return 
                        stripos($cot->cliente, $searchLower) !== false ||
                        stripos($cot->numero_cotizacion, $searchLower) !== false;
                });
            }

            \Log::info('CotizacionesViewController: Cotizaciones obtenidas', [
                'total' => $cotizaciones->count(),
                'usuario_id' => Auth::id(),
                'search_term' => $searchTerm,
                'sample' => $cotizaciones->first() ? (array)$cotizaciones->first() : null,
                'es_borrador_values' => $cotizaciones->pluck('es_borrador')->unique()->toArray(),
            ]);

            // Separar cotizaciones enviadas (no borradores) de borradores
            $cotizacionesEnviadas = $cotizaciones->filter(fn($c) => $c->es_borrador !== true && $c->es_borrador !== 1);
            
            // Mostrar solo cotizaciones enviadas en el tab de Cotizaciones
            // y solo las que tienen es_borrador = 1 en el tab de Borradores
            $cotizacionesTodas = $this->paginate($cotizacionesEnviadas, 15);
            $cotizacionesPrenda = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'PL'), 15);
            $cotizacionesLogo = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'L'), 15);
            $cotizacionesPrendaBordado = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'PL'), 15);

            // Separar borradores por tipo (solo las que tienen es_borrador = 1)
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
     * Obtener datos de una cotización para el modal de comparación
     * GET /cotizaciones/{id}/datos
     * 
     * Trae información de todas las tablas relacionadas:
     * - prendas_cot (prendas)
     * - prenda_fotos_cot (fotos)
     * - prenda_telas_cot (telas)
     * - prenda_tallas_cot (tallas)
     * - prenda_variantes_cot (variantes)
     * 
     * @param int $cotizacion
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatosForModal($cotizacion)
    {
        try {
            $datos = $this->obtenerDatosCotizacionModalUseCase->ejecutar((int) $cotizacion);

            if ($datos === null) {
                \Log::warning('CotizacionesViewController@getDatosForModal: Cotización no encontrada', [
                    'cotizacion_id' => $cotizacion,
                ]);

                return response()->json(['error' => 'Cotización no encontrada'], 404);
            }

            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('CotizacionesViewController@getDatosForModal: Error', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacion,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Error al obtener los datos'], 500);
        }
    }

    /**
     * Obtener contador de cotizaciones pendientes para aprobador
     * GET /pendientes-count
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function cotizacionesPendientesAprobadorCount()
    {
        try {
            $count = $this->contarCotizacionesPorEstadoUseCase->ejecutar('APROBADA_CONTADOR');

            return response()->json([
                'success' => true,
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            \Log::error('CotizacionesViewController@cotizacionesPendientesAprobadorCount: Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'count' => 0,
                'error' => 'Error al obtener el contador',
            ], 500);
        }
    }

    /**
     * Paginar una colección manualmente
     */
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

