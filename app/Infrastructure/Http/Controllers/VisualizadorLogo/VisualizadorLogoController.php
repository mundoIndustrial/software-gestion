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

    public function disenosLogo()
    {
        return view('visualizador-logo.disenos-logo');
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

    public function disenosLogoData(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = DB::table('disenos_logo_pedido as dlp')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.id', '=', 'dlp.proceso_prenda_detalle_id')
            ->join('prendas_pedido as pr', 'pr.id', '=', 'ppd.prenda_pedido_id')
            ->join('pedidos_produccion as ped', 'ped.id', '=', 'pr.pedido_produccion_id')
            ->select([
                'dlp.id',
                'dlp.url',
                'dlp.observacio_diseño',
                'dlp.created_at',
                'ppd.id as proceso_prenda_detalle_id',
                'pr.id as prenda_pedido_id',
                'ped.id as pedido_id',
                'ped.cliente as cliente',
            ])
            ->orderByDesc('dlp.created_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('ped.cliente', 'like', "%{$search}%")
                    ->orWhere('dlp.observacio_diseño', 'like', "%{$search}%")
                    ->orWhere('dlp.url', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'items' => $items,
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

