<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use Illuminate\Http\Request;
use App\Application\PedidosLogo\Services\DisenoLogoBroadcastService;
use App\Application\PedidosLogo\UseCases\ReemplazarDisenoLogoPedidoUseCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

final class VisualizadorLogoController extends Controller
{
    private ListOrdersUseCase $listOrdersUseCase;
    private ReemplazarDisenoLogoPedidoUseCase $reemplazarDisenoLogoPedidoUseCase;
    private DisenoLogoBroadcastService $disenoLogoBroadcastService;

    public function __construct(
        ListOrdersUseCase $listOrdersUseCase,
        ReemplazarDisenoLogoPedidoUseCase $reemplazarDisenoLogoPedidoUseCase,
        DisenoLogoBroadcastService $disenoLogoBroadcastService,
    ) {
        $this->listOrdersUseCase = $listOrdersUseCase;
        $this->reemplazarDisenoLogoPedidoUseCase = $reemplazarDisenoLogoPedidoUseCase;
        $this->disenoLogoBroadcastService = $disenoLogoBroadcastService;
    }
    public function dashboard()
    {
        return view('visualizador-logo.dashboard');
    }

    public function getCotizaciones(Request $request)
    {
        Log::info('[VisualizadorLogo] getCotizaciones inicio');

        $query = Cotizacion::with([
            'asesor',
            'cliente',
            'logoCotizacion',
            'logoCotizacion.fotos',
        ])
            ->select('cotizaciones.*')
            ->selectRaw('(SELECT nombre FROM clientes WHERE clientes.id = cotizaciones.cliente_id) as cliente_nombre')
            ->whereNotNull('numero_cotizacion')
            ->where('es_borrador', false);

        $query->where(function ($q) {
            $q->where('tipo_cotizacion_id', 2)
                ->orWhere(function ($subQ) {
                    $subQ->where('tipo_cotizacion_id', 1)
                        ->whereHas('logoCotizacion');
                });
        });

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_cotizacion', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($subQ) use ($search) {
                        $subQ->where('nombre', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_envio', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_envio', '<=', $request->fecha_hasta);
        }

        $cotizaciones = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'cotizaciones' => $cotizaciones,
        ]);
    }

    public function verCotizacion(int $id)
    {
        $cotizacion = Cotizacion::with([
            'cliente',
            'asesor',
            'logoCotizacion',
            'logoCotizacion.fotos',
            'tipoCotizacion',
            'prendas.variantes.genero',
            'logoCotizacion.tecnicasPrendas.prenda.variantes.genero',
            'logoCotizacion.tecnicasPrendas.tipoLogo',
        ])->findOrFail($id);

        $tipoPermitido = $cotizacion->tipo_cotizacion_id == 2
            || ($cotizacion->tipo_cotizacion_id == 1 && $cotizacion->logoCotizacion);

        if (!$tipoPermitido) {
            abort(403, 'No tienes permiso para ver esta cotizacion.');
        }

        if (!$cotizacion->logoCotizacion) {
            abort(404, 'Esta cotizacion no tiene informacion de logo.');
        }

        return view('visualizador-logo.detalle', compact('cotizacion'));
    }

    public function pedidosLogo()
    {
        return view('visualizador-logo.pedidos-logo');
    }

    public function logosConfirmados()
    {
        return view('visualizador-logo.logos-confirmados');
    }
    
    public function marcarComoRevisado(int $disenoId)
    {
        try {
            $diseno = \App\Models\DisenoLogoPedido::findOrFail($disenoId);
            $estadoAnterior = (string) $diseno->estado;
            $diseno->update(['revisada' => 1]);
            $diseno->refresh();
            $this->disenoLogoBroadcastService->emit('revisado', $diseno, $estadoAnterior);
            
            return response()->json([
                'success' => true,
                'message' => 'Diseño marcado como revisado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('[VisualizadorLogo] Error al marcar como revisado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como revisado'
            ], 500);
        }
    }

    public function reemplazarImagen(Request $request, int $disenoId)
    {
        try {
            $result = $this->reemplazarDisenoLogoPedidoUseCase->execute($request, $disenoId);

            if (!($result['ok'] ?? false)) {
                $status = (int) ($result['status'] ?? 500);
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Error al reemplazar la imagen.',
                    'errors' => $result['errors'] ?? null,
                ], $status);
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen reemplazada exitosamente.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('[VisualizadorLogo] Error al reemplazar imagen: ' . $e->getMessage());
            Log::error('[VisualizadorLogo] Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al reemplazar la imagen: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    public static function getConteoLogosNoRevisados()
    {
        $confirmados = \App\Models\DisenoLogoPedido::where('estado', 'logo_confirmado')
            ->where('revisada', 0)
            ->count();
        
        $devueltos = \App\Models\DisenoLogoPedido::where('estado', 'devuelto_a_diseño')
            ->where('revisada', 0)
            ->count();
        
        return [
            'confirmados' => $confirmados,
            'devueltos' => $devueltos,
            'total' => $confirmados + $devueltos,
        ];
    }

    public function pedidosVisualizacion()
    {
        return view('visualizador-logo.pedidos-visualizacion');
    }

    public function pedidosVisualizacionData(Request $request)
    {
        try {
            $params = $request->query();
            $params['user_id'] = $request->user()?->id;
            $params['is_visualizador'] = true;

            $response = $this->listOrdersUseCase->execute(new ListOrdersRequest($params));
            
            // Obtener los datos de la respuesta
            $ordenes = $response->getOrdenes();
            $estados = $response->getEstados();
            $pedidosSeleccionados = $response->getPedidosSeleccionados();

            // Convertir el paginator a array incluyendo información de paginación
            $ordenesArray = [
                'data' => $ordenes->items(),
                'current_page' => $ordenes->currentPage(),
                'last_page' => $ordenes->lastPage(),
                'per_page' => $ordenes->perPage(),
                'total' => $ordenes->total(),
                'from' => $ordenes->firstItem(),
                'to' => $ordenes->lastItem(),
            ];

            return response()->json([
                'success' => true,
                'ordenes' => $ordenesArray,
                'estados' => $estados,
                'pedidosSeleccionados' => $pedidosSeleccionados,
            ]);
        } catch (\Exception $e) {
            Log::error('[VisualizadorLogo] Error en pedidosVisualizacionData: ' . $e->getMessage());
            Log::error('[VisualizadorLogo] Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pedidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pedidoVisualizacionDatos(Request $request, $pedidoId)
    {
        try {
            $pedido = PedidoProduccion::with([
                'asesora:id,name',
                'cliente:id,nombre',
                'prendas',
            ])->findOrFail($pedidoId);

            return response()->json([
                'success' => true,
                'orden' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente?->nombre ?? $pedido->cliente,
                    'estado' => $pedido->estado,
                    'forma_de_pago' => $pedido->forma_de_pago,
                    'fecha_pedido' => $pedido->created_at,
                    'asesora' => $pedido->asesora?->name,
                    'prendas_count' => $pedido->prendas->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[VisualizadorLogo] Error en pedidoVisualizacionDatos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el pedido',
            ], 500);
        }
    }

    public function logosConfirmadosHistorialNovedades(Request $request)
    {
        $disenos = \App\Models\DisenoLogoPedido::with([
            'proceso.prenda.pedidoProduccion.cliente',
            'proceso.prenda.pedidoProduccion.asesora',
            'novedades.usuario',
        ])
            ->whereIn('estado', ['logo_confirmado', 'devuelto_a_diseño'])
            ->get();

        $gruposLogos = [];
        foreach ($disenos as $diseno) {
            $prendaPedido = $diseno->proceso?->prenda;
            $groupKey = ($prendaPedido?->pedido_produccion_id ?? '0') . '-' . ($prendaPedido?->id ?? '0');

            if (!isset($gruposLogos[$groupKey])) {
                $gruposLogos[$groupKey] = [];
            }

            $gruposLogos[$groupKey][] = [
                'id' => $diseno->id,
                'url' => $diseno->url,
                'revisada' => (bool) $diseno->revisada,
                'novedades' => $diseno->novedades,
            ];
        }

        $disenoGroupMap = [];
        foreach ($disenos as $diseno) {
            $prendaPedido = $diseno->proceso?->prenda;
            $groupKey = ($prendaPedido?->pedido_produccion_id ?? '0') . '-' . ($prendaPedido?->id ?? '0');
            $disenoGroupMap[$diseno->id] = $groupKey;
        }

        $novedades = \App\Models\DisenoLogoPedidoNovedad::with(['diseno.proceso.prenda.pedidoProduccion.cliente', 'diseno.proceso.prenda.pedidoProduccion.asesora'])
            ->whereHas('diseno', function ($q) {
                $q->whereIn('estado', ['logo_confirmado', 'devuelto_a_diseño']);
            })
            ->orderByDesc('created_at')
            ->get();

        $items = $novedades->map(function ($novedad) use ($gruposLogos, $disenoGroupMap) {
            $diseno = $novedad->diseno;
            $groupKey = $diseno ? ($disenoGroupMap[$diseno->id] ?? null) : null;
            
            // Obtener cliente directamente del pedido producción
            $cliente = '-';
            if ($diseno && $diseno->proceso && $diseno->proceso->prenda && $diseno->proceso->prenda->pedidoProduccion) {
                $cliente = $diseno->proceso->prenda->pedidoProduccion->cliente ?? '-';
            }

            // Obtener nombre de la asesora (usuario) desde pedidoProduccion->asesora
            $asesora = '-';
            if ($diseno && $diseno->proceso && $diseno->proceso->prenda && $diseno->proceso->prenda->pedidoProduccion && $diseno->proceso->prenda->pedidoProduccion->asesora) {
                $asesora = $diseno->proceso->prenda->pedidoProduccion->asesora->name ?? '-';
            }

            return [
                'id' => $novedad->id,
                'numero_recibo' => $diseno ? $this->resolveNumeroRecibo($diseno) : '-',
                'cliente' => $cliente,
                'asesora' => $asesora,
                'fecha' => $novedad->created_at,
                'observacion' => $novedad->novedad,
                'tipo_novedad' => $novedad->tipo_novedad,
                'logos' => $groupKey && isset($gruposLogos[$groupKey])
                    ? array_values($gruposLogos[$groupKey])
                    : [],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    private function resolveNumeroRecibo(\App\Models\DisenoLogoPedido $diseno): string
    {
        $proceso = $diseno->proceso;
        $prendaPedido = $proceso?->prenda;

        if (!$proceso || !$prendaPedido) {
            return '-';
        }

        $tipoRecibo = match ($proceso->tipo_proceso_id) {
            1 => 'REFLECTIVO',
            2 => 'BORDADO',
            3 => 'ESTAMPADO',
            4 => 'DTF',
            5 => 'SUBLIMADO',
            default => null,
        };

        $crp = null;
        if ($tipoRecibo) {
            $crp = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
                ->where('prenda_id', $prendaPedido->id)
                ->where('activo', 1)
                ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoRecibo])
                ->first();
        }

        if (!$crp) {
            $crp = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
                ->where('prenda_id', $prendaPedido->id)
                ->where('activo', 1)
                ->first();
        }

        if ($crp && $crp->consecutivo_actual) {
            return (string) $crp->consecutivo_actual;
        }

        if ($proceso->numero_recibo) {
            return (string) $proceso->numero_recibo;
        }

        return '-';
    }

    public function logosConfirmadosData(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        // Get the active tab from request
        $tab = $request->get('tab', 'confirmados');

        // First, get all designs with their relationships based on tab
        $query = \App\Models\DisenoLogoPedido::with([
            'proceso.prenda.pedidoProduccion',
            'proceso.prenda',
            'novedades.usuario'
        ]);

        if ($tab === 'devueltos') {
            $query->where('estado', 'devuelto_a_diseño');
        } else {
            $query->where('estado', 'logo_confirmado');
        }

        $disenos = $query->orderByDesc('created_at')->get();

        // Let's map them and try to find numero_recibo
        $items = $disenos->map(function ($diseno) {
            $proceso = $diseno->proceso;
            $prendaPedido = $proceso?->prenda;
            $pedido = $prendaPedido?->pedidoProduccion;

            $numeroRecibo = $this->resolveNumeroRecibo($diseno);

            // Get last novedad for display
            $ultimaNovedad = $diseno->novedades->first();
            
            return [
                'id' => $diseno->id,
                'url' => $diseno->url,
                'novedades' => $diseno->novedades,
                'ultima_novedad' => $ultimaNovedad?->novedad,
                'created_at' => $diseno->created_at,
                'proceso_prenda_detalle_id' => $diseno->proceso_prenda_detalle_id,
                'prenda_pedido_id' => $prendaPedido?->id,
                'pedido_id' => $pedido?->id,
                'cliente' => $pedido?->cliente ?? '-',
                'nombre_prenda' => $prendaPedido?->nombre_prenda ?? '-',
                'numero_recibo' => $numeroRecibo,
                'revisada' => (bool)$diseno->revisada,
            ];
        });

        // Now apply search filter
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(strtolower($item['cliente']), strtolower($search))
                    || str_contains(strtolower($item['ultima_novedad'] ?? ''), strtolower($search))
                    || str_contains(strtolower($item['numero_recibo']), strtolower($search))
                    || str_contains(strtolower($item['nombre_prenda']), strtolower($search));
            });
        }

        // Group by pedido_id + prenda_id
        $grouped = $items->groupBy(function ($item) {
            return $item['pedido_id'] . '-' . $item['prenda_pedido_id'];
        })->values()->map(function ($group) {
            $first = $group->first();
            // Check if all logos in group are revisados
            $todosRevisados = $group->every(fn($item) => $item['revisada']);
            return [
                'group_key' => $first['pedido_id'] . '-' . $first['prenda_pedido_id'],
                'pedido_id' => $first['pedido_id'],
                'prenda_pedido_id' => $first['prenda_pedido_id'],
                'cliente' => $first['cliente'],
                'nombre_prenda' => $first['nombre_prenda'],
                'numero_recibo' => $first['numero_recibo'],
                'created_at' => $first['created_at'],
                'ultima_novedad' => $first['ultima_novedad'],
                'todos_revisados' => $todosRevisados,
                'logos' => $group->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'url' => $item['url'],
                        'revisada' => $item['revisada'],
                        'novedades' => $item['novedades'],
                    ];
                })->values()->toArray(),
            ];
        });

        // Paginate manually since group
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPageItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $grouped->forPage($currentPage, $perPage),
            $grouped->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return response()->json([
            'success' => true,
            'items' => $perPageItems,
            'conteo_no_revisados' => self::getConteoLogosNoRevisados(),
            'tab' => $tab,
        ]);
    }

    public function getEstadisticas()
    {
        $baseQuery = Cotizacion::whereNotNull('numero_cotizacion')
            ->where('es_borrador', false)
            ->where(function ($q) {
                $q->where('tipo_cotizacion_id', 2)
                    ->orWhere(function ($subQ) {
                        $subQ->where('tipo_cotizacion_id', 1)
                            ->whereHas('logoCotizacion');
                    });
            });

        $estadisticas = [
            'total' => (clone $baseQuery)->count(),
            'pendientes' => (clone $baseQuery)->where('estado', 'pendiente')->count(),
            'aprobadas' => (clone $baseQuery)->where('estado', 'aprobado')->count(),
            'rechazadas' => (clone $baseQuery)->where('estado', 'rechazado')->count(),
            'este_mes' => (clone $baseQuery)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'estadisticas' => $estadisticas,
        ]);
    }

    public function historialLogos(Request $request)
    {
        // Obtener clientes con logos confirmados agrupados
        $clientesConLogos = \App\Models\DisenoLogoPedido::where('estado', 'logo_confirmado')
            ->with([
                'proceso.prenda.pedidoProduccion.cliente'
            ])
            ->get()
            ->groupBy(function($diseno) {
                return $diseno->proceso?->prenda?->pedidoProduccion?->cliente_id;
            })
            ->map(function($disenosGrupo, $clienteId) {
                $primeraInstancia = $disenosGrupo->first();
                $cliente = $primeraInstancia->proceso?->prenda?->pedidoProduccion?->cliente;
                
                return [
                    'cliente_id' => $clienteId,
                    'cliente_nombre' => $cliente ?? 'Cliente desconocido',
                    'cantidad_logos' => $disenosGrupo->count(),
                    'logos' => $disenosGrupo,
                ];
            })
            ->sortByDesc('cantidad_logos')
            ->values();

        // Si viene con all=true, retornar todos sin paginación (para búsqueda desde navbar)
        if ($request->get('all') === 'true') {
            return view('visualizador-logo.historial-logos', [
                'clientesConLogos' => $clientesConLogos,
                'search' => '',
                'allClientes' => true,
            ]);
        }

        // Aplicar filtro de búsqueda si existe
        $search = $request->get('search', '');
        if ($search) {
            $searchLower = strtolower($search);
            $clientesConLogos = $clientesConLogos->filter(function($cliente) use ($searchLower) {
                return str_contains(strtolower($cliente['cliente_nombre']), $searchLower);
            })->values();
        }

        // Paginar los clientes (9 por página)
        $perPage = 9;
        $page = $request->get('page', 1);
        $total = $clientesConLogos->count();
        $items = $clientesConLogos->slice(($page - 1) * $perPage, $perPage)->values();
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('visualizador-logo.historial-logos', [
            'clientesConLogos' => $paginator,
            'search' => $search,
        ]);
    }

    public function disenosCliente($clienteId, Request $request)
    {
        try {
            // Obtener todos los diseños confirmados del cliente
            $disenosCliente = \App\Models\DisenoLogoPedido::where('estado', 'logo_confirmado')
                ->with([
                    'proceso.prenda.pedidoProduccion.cliente',
                    'proceso.tipoProceso',
                    'novedades.usuario'
                ])
                ->get()
                ->filter(function($diseno) use ($clienteId) {
                    return $diseno->proceso?->prenda?->pedidoProduccion?->cliente_id == $clienteId;
                })
                ->sortByDesc('created_at')
                ->values();

            // Obtener nombre del cliente
            if ($disenosCliente->isEmpty()) {
                $cliente = 'Cliente desconocido';
            } else {
                $cliente = $disenosCliente->first()->proceso?->prenda?->pedidoProduccion?->cliente ?? 'Cliente desconocido';
            }

            // Mapear datos para la vista
            $disenosFormateados = $disenosCliente->map(function($diseno) {
                $proceso = $diseno->proceso;
                $prendaPedido = $proceso?->prenda;
                $pedido = $prendaPedido?->pedidoProduccion;

                // Resolver número de recibo y tipo
                $numeroRecibo = $this->resolveNumeroRecibo($diseno);
                $tipoRecibo = $this->resolveTipoRecibo($diseno);

                return [
                    'id' => $diseno->id,
                    'url' => $diseno->url,
                    'created_at' => $diseno->created_at,
                    'numero_recibo' => $numeroRecibo,
                    'tipo_recibo' => $tipoRecibo,
                    'nombre_prenda' => $prendaPedido?->nombre_prenda ?? '-',
                    'novedades' => $diseno->novedades,
                ];
            });

            // Paginar diseños (6 por página)
            $perPage = 6;
            $page = $request->get('page', 1);
            $total = $disenosFormateados->count();
            $items = $disenosFormateados->slice(($page - 1) * $perPage, $perPage)->values();
            
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'success' => true,
                'cliente_nombre' => $cliente,
                'cliente_id' => $clienteId,
                'diseños' => $paginator->items(),
                'paginacion' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[VisualizadorLogo] Error en disenosCliente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los diseños del cliente',
            ], 500);
        }
    }

    private function resolveTipoRecibo(\App\Models\DisenoLogoPedido $diseno): string
    {
        $proceso = $diseno->proceso;
        
        if (!$proceso) {
            return 'DESCONOCIDO';
        }

        return match ($proceso->tipo_proceso_id) {
            1 => 'REFLECTIVO',
            2 => 'BORDADO',
            3 => 'ESTAMPADO',
            4 => 'DTF',
            5 => 'SUBLIMADO',
            default => 'DESCONOCIDO',
        };
    }
}

