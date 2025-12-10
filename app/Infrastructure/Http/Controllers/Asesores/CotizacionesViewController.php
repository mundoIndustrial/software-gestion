<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionesViewController - Controller para vistas de cotizaciones
 * 
 * Responsabilidad: Retornar vistas HTML con datos obtenidos de Handlers DDD
 */
final class CotizacionesViewController extends Controller
{
    public function __construct(
        private readonly ListarCotizacionesHandler $listarHandler
    ) {
    }

    /**
     * Mostrar vista de cotizaciones del usuario
     * GET /asesores/cotizaciones
     */
    public function index()
    {
        try {
            // Crear query para listar cotizaciones
            $query = ListarCotizacionesQuery::crear(
                usuarioId: Auth::id(),
                soloEnviadas: false,
                soloBorradores: false,
                pagina: 1,
                porPagina: 100,
            );

            // Ejecutar handler
            $cotizacionesDTO = $this->listarHandler->handle($query);

            // Convertir DTOs a colección de objetos para la vista
            $cotizaciones = collect(array_map(function($dto) {
                $data = $dto->toArray();
                // Convertir fechas string a DateTime
                if (isset($data['fecha_inicio']) && is_string($data['fecha_inicio'])) {
                    $data['created_at'] = \Carbon\Carbon::parse($data['fecha_inicio']);
                }
                if (isset($data['fecha_envio']) && is_string($data['fecha_envio'])) {
                    $data['fecha_envio'] = \Carbon\Carbon::parse($data['fecha_envio']);
                }
                return (object)$data;
            }, $cotizacionesDTO));

            \Log::info('CotizacionesViewController: Cotizaciones obtenidas', [
                'total' => $cotizaciones->count(),
                'usuario_id' => Auth::id(),
                'sample' => $cotizaciones->first() ? (array)$cotizaciones->first() : null,
                'es_borrador_values' => $cotizaciones->pluck('es_borrador')->unique()->toArray(),
            ]);

            // Mostrar todas las cotizaciones en el tab de Cotizaciones
            // y solo las que tienen estado BORRADOR en el tab de Borradores
            $cotizacionesTodas = $this->paginate($cotizaciones, 15);
            $cotizacionesPrenda = $this->paginate($cotizaciones->filter(fn($c) => ($c->tipo === 'P' || $c->tipo === null)), 15);
            $cotizacionesLogo = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'L'), 15);
            $cotizacionesPrendaBordado = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'PL'), 15);

            // Separar borradores por tipo (solo las que tienen es_borrador = 1)
            $borradoresCollection = $cotizaciones->filter(fn($c) => $c->es_borrador === true || $c->es_borrador === 1);
            $borradoresTodas = $this->paginate($borradoresCollection, 15);
            $borradorespPrenda = $this->paginate($borradoresCollection->filter(fn($c) => ($c->tipo === 'P' || $c->tipo === null)), 15);
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
