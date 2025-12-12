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
            // Obtener cotizaciones directamente del modelo con relaciones
            $cotizacionesModelo = \App\Models\Cotizacion::where('asesor_id', Auth::id())
                ->with([
                    'cliente',
                    'prendas.fotos',
                    'prendas.tallas',
                    'prendas.variantes',
                    'prendas.telas',
                    'logoCotizacion.fotos',
                    'reflectivo.fotos'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Convertir modelos a objetos para la vista
            $cotizaciones = $cotizacionesModelo->map(function($cot) {
                $obj = (object)[
                    'id' => $cot->id,
                    'numero_cotizacion' => $cot->numero_cotizacion,
                    'tipo' => $cot->tipo_cotizacion_id ? ($cot->tipoCotizacion->codigo ?? 'P') : 'P',
                    'estado' => $cot->estado,
                    'es_borrador' => $cot->es_borrador,
                    'cliente' => $cot->cliente ? $cot->cliente->nombre : 'Sin cliente',
                    'created_at' => $cot->created_at,
                    'fecha_inicio' => $cot->fecha_inicio,
                    'fecha_envio' => $cot->fecha_envio,
                    'prendas' => $cot->prendas,
                    'logoCotizacion' => $cot->logoCotizacion,
                ];
                return $obj;
            });

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
            $cotizacionesReflectivo = $this->paginate($cotizaciones->filter(fn($c) => $c->tipo === 'RF'), 15);

            // Separar borradores por tipo (solo las que tienen es_borrador = 1)
            $borradoresCollection = $cotizaciones->filter(fn($c) => $c->es_borrador === true || $c->es_borrador === 1);
            $borradoresTodas = $this->paginate($borradoresCollection, 15);
            $borradorespPrenda = $this->paginate($borradoresCollection->filter(fn($c) => ($c->tipo === 'P' || $c->tipo === null)), 15);
            $borradoresLogo = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'L'), 15);
            $borradorespPrendaBordado = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'PL'), 15);
            $borradoresReflectivo = $this->paginate($borradoresCollection->filter(fn($c) => $c->tipo === 'RF'), 15);

            return view('asesores.cotizaciones.index', compact(
                'cotizacionesTodas',
                'cotizacionesPrenda',
                'cotizacionesLogo',
                'cotizacionesPrendaBordado',
                'cotizacionesReflectivo',
                'borradoresTodas',
                'borradorespPrenda',
                'borradoresLogo',
                'borradorespPrendaBordado',
                'borradoresReflectivo'
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
                'cotizacionesReflectivo' => collect([]),
                'borradoresTodas' => collect([]),
                'borradorespPrenda' => collect([]),
                'borradoresLogo' => collect([]),
                'borradorespPrendaBordado' => collect([]),
                'borradoresReflectivo' => collect([]),
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
            // Obtener la cotización con TODAS sus relaciones anidadas
            $cotizacionModelo = \App\Models\Cotizacion::with([
                'cliente',
                'asesor',
                'prendas' => function($query) {
                    $query->with([
                        'fotos',
                        'telas',
                        'telaFotos',
                        'tallas',
                        'variantes'
                    ]);
                }
            ])->findOrFail($cotizacion);

            // Preparar datos de la cotización
            $datos = [
                'cotizacion' => [
                    'id' => $cotizacionModelo->id,
                    'numero_cotizacion' => $cotizacionModelo->numero_cotizacion,
                    'asesora_nombre' => $cotizacionModelo->asesor ? $cotizacionModelo->asesor->name : 'N/A',
                    'empresa' => $cotizacionModelo->empresa_solicitante ?? 'N/A',
                    'nombre_cliente' => $cotizacionModelo->cliente ? $cotizacionModelo->cliente->nombre : 'N/A',
                    'created_at' => $cotizacionModelo->created_at,
                    'estado' => $cotizacionModelo->estado,
                ],
                'prendas_cotizaciones' => $cotizacionModelo->prendas->map(function($prenda) {
                    return [
                        'id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                        'cantidad' => $prenda->cantidad ?? 0,
                        'descripcion' => $prenda->descripcion ?? null,
                        'detalles_proceso' => $prenda->descripcion ?? null,
                        // Fotos de la prenda
                        'fotos' => $prenda->fotos ? $prenda->fotos->map(function($foto) {
                            return [
                                'id' => $foto->id,
                                'url' => $foto->url,
                                'nombre' => $foto->nombre,
                            ];
                        })->toArray() : [],
                        // Telas asociadas
                        'telas' => $prenda->telas ? $prenda->telas->map(function($tela) {
                            return [
                                'id' => $tela->id,
                                'color' => $tela->color ?? null,
                                'nombre_tela' => $tela->nombre_tela ?? null,
                                'referencia' => $tela->referencia ?? null,
                                'url_imagen' => $tela->url_imagen ?? null,
                            ];
                        })->toArray() : [],
                        // Fotos de telas
                        'tela_fotos' => $prenda->telaFotos ? $prenda->telaFotos->map(function($foto) {
                            return [
                                'id' => $foto->id,
                                'url' => $foto->url ?? null,
                                'nombre' => $foto->nombre ?? null,
                            ];
                        })->toArray() : [],
                        // Tallas
                        'tallas' => $prenda->tallas ? $prenda->tallas->map(function($talla) {
                            return [
                                'id' => $talla->id,
                                'talla' => $talla->talla,
                                'cantidad' => $talla->cantidad,
                            ];
                        })->toArray() : [],
                        // Variantes
                        'variantes' => $prenda->variantes ? $prenda->variantes->map(function($variante) {
                            return [
                                'id' => $variante->id,
                                'tipo_prenda' => $variante->tipo_prenda ?? null,
                                'es_jean_pantalon' => $variante->es_jean_pantalon ?? null,
                                'tipo_jean_pantalon' => $variante->tipo_jean_pantalon ?? null,
                                'genero_id' => $variante->genero_id ?? null,
                                'color' => $variante->color ?? null,
                                'tiene_bolsillos' => $variante->tiene_bolsillos ?? null,
                                'aplica_manga' => $variante->aplica_manga ?? null,
                                'tipo_manga' => $variante->tipo_manga ?? null,
                                'aplica_broche' => $variante->aplica_broche ?? null,
                                'tipo_broche_id' => $variante->tipo_broche_id ?? null,
                                'tiene_reflectivo' => $variante->tiene_reflectivo ?? null,
                                'descripcion_adicional' => $variante->descripcion_adicional ?? null,
                            ];
                        })->toArray() : [],
                    ];
                })->toArray(),
            ];

            return response()->json($datos);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('CotizacionesViewController@getDatosForModal: Cotización no encontrada', [
                'cotizacion_id' => $cotizacion,
            ]);
            return response()->json(['error' => 'Cotización no encontrada'], 404);
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
            // Contar cotizaciones en estado ENVIADO A APROBADOR
            $count = \App\Models\Cotizacion::where('estado', 'ENVIADO A APROBADOR')
                ->count();

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
