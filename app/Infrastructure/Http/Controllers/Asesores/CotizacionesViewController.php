<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;
use App\Http\Controllers\Controller;
use App\Helpers\DescripcionPrendaHelper;
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

            // Separar cotizaciones enviadas (no borradores) de borradores
            $cotizacionesEnviadas = $cotizaciones->filter(fn($c) => $c->es_borrador !== true && $c->es_borrador !== 1);
            
            // Mostrar solo cotizaciones enviadas en el tab de Cotizaciones
            // y solo las que tienen es_borrador = 1 en el tab de Borradores
            $cotizacionesTodas = $this->paginate($cotizacionesEnviadas, 15);
            $cotizacionesPrenda = $this->paginate($cotizacionesEnviadas->filter(fn($c) => ($c->tipo === 'P' || $c->tipo === null)), 15);
            $cotizacionesLogo = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'L'), 15);
            $cotizacionesPrendaBordado = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'PL'), 15);
            $cotizacionesReflectivo = $this->paginate($cotizacionesEnviadas->filter(fn($c) => $c->tipo === 'RF'), 15);

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
                        'telas.color',
                        'telas.tela',
                        'telaFotos',
                        'tallas',
                        'variantes' => function($q) {
                            $q->with(['manga', 'broche']);
                        }
                    ]);
                }
            ])->findOrFail($cotizacion);

            \Log::info('=== COTIZACION CARGADA ===', [
                'cotizacion_id' => $cotizacion,
                'prendas_count' => $cotizacionModelo->prendas->count(),
            ]);
            
            foreach ($cotizacionModelo->prendas as $idx => $prenda) {
                \Log::info("Prenda {$idx}", [
                    'prenda_id' => $prenda->id,
                    'nombre' => $prenda->nombre_producto,
                    'telas_cargadas' => $prenda->telas->count(),
                    'variantes_cargadas' => $prenda->variantes->count(),
                ]);
                
                if ($prenda->telas->count() > 0) {
                    foreach ($prenda->telas as $tidx => $tela) {
                        \Log::info("  Tela {$tidx}", [
                            'tela_id' => $tela->id,
                            'tela_relation_loaded' => $tela->relationLoaded('tela'),
                            'color_relation_loaded' => $tela->relationLoaded('color'),
                        ]);
                    }
                }
            }

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
                'prendas_cotizaciones' => $cotizacionModelo->prendas->map(function($prenda, $index) {
                    // Generar descripción formateada usando el método del modelo
                    $descripcionFormateada = $prenda->generarDescripcionDetallada($index + 1);
                    
                    return [
                        'id' => $prenda->id,
                        'nombre_prenda' => $prenda->nombre_producto ?? 'Prenda sin nombre',
                        'cantidad' => $prenda->cantidad ?? 0,
                        'descripcion' => $prenda->descripcion ?? null,
                        'descripcion_formateada' => $descripcionFormateada,
                        'detalles_proceso' => $prenda->descripcion ?? null,
                        // Fotos de la prenda - URLs completas (asset)
                        'fotos' => $prenda->fotos ? $prenda->fotos->map(function($foto) {
                            return asset($foto->ruta_webp ?? $foto->url);
                        })->toArray() : [],
                        // Telas asociadas - URLs de imagen
                        'telas' => $prenda->telas ? $prenda->telas->map(function($tela) {
                            return [
                                'id' => $tela->id,
                                'color' => $tela->color ?? null,
                                'nombre_tela' => $tela->tela->nombre ?? null,
                                'referencia' => $tela->tela->referencia ?? null,
                                'url_imagen' => asset($tela->ruta_webp ?? $tela->url_imagen),
                            ];
                        })->toArray() : [],
                        // Fotos de telas - URLs completas (asset)
                        'tela_fotos' => $prenda->telaFotos ? $prenda->telaFotos->map(function($foto) {
                            return asset($foto->ruta_webp ?? $foto->url ?? null);
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
