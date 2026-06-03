<?php

namespace App\Infrastructure\Http\Controllers\VisualizadorLogo;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Application\SupervisorPedidos\UseCases\ListOrdersUseCase;
use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

final class VisualizadorLogoController extends Controller
{
    private ListOrdersUseCase $listOrdersUseCase;

    public function __construct(ListOrdersUseCase $listOrdersUseCase)
    {
        $this->listOrdersUseCase = $listOrdersUseCase;
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
            $diseno->update(['revisada' => 1]);
            
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
            'proceso.prenda'
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

            // Now let's try to find the consecutivo_recibo_pedido
            $numeroRecibo = '-';

            if ($proceso && $prendaPedido && $pedido) {
                // Map tipo_proceso_id to tipo_recibo
                $tipoRecibo = match($proceso->tipo_proceso_id) {
                    1 => 'REFLECTIVO',
                    2 => 'BORDADO',
                    3 => 'ESTAMPADO',
                    4 => 'DTF',
                    5 => 'SUBLIMADO',
                    default => null
                };
                
                // First, try using the mapped tipo_recibo
                $crp = null;
                if ($tipoRecibo) {
                    $crpQuery = \DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
                        ->where('prenda_id', $prendaPedido->id)
                        ->where('activo', 1)
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoRecibo]);
                    
                    $crp = $crpQuery->first();
                }

                // If no luck, try any active CRP for this pedido AND prenda_pedido.id
                if (!$crp) {
                    $crpQuery = \DB::table('consecutivos_recibos_pedidos')
                        ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
                        ->where('prenda_id', $prendaPedido->id)
                        ->where('activo', 1);
                    
                    $crp = $crpQuery->first();
                }

                // If we found a CRP, use its consecutivo_actual
                if ($crp && $crp->consecutivo_actual) {
                    $numeroRecibo = $crp->consecutivo_actual;
                }
                // Fallback to ppd's numero_recibo
                else if ($proceso->numero_recibo) {
                    $numeroRecibo = $proceso->numero_recibo;
                }
            }

            return [
                'id' => $diseno->id,
                'url' => $diseno->url,
                'observacio_diseño' => $diseno->observacio_diseño,
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
                    || str_contains(strtolower($item['observacio_diseño'] ?? ''), strtolower($search))
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
                'observacio_diseño' => $first['observacio_diseño'],
                'todos_revisados' => $todosRevisados,
                'logos' => $group->map(function ($item) {
                    return [
                        'id' => $item['id'],
                        'url' => $item['url'],
                        'revisada' => $item['revisada'],
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
}

