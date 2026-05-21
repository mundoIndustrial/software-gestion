<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Application\Operario\UseCases\VerPedidoOperarioUseCase;
use App\Application\Operario\UseCases\ObtenerDatosRecibosOperarioUseCase;
use App\Application\Operario\UseCases\GetPedidoDataOperarioUseCase;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PrendaBodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

/**
 * Controller: OperarioController
 * 
 * Gestiona vistas y acciones para operarios (cortador/costurero)
 */
class OperarioController extends Controller
{
    public function __construct(
        private ObtenerPedidosOperarioService $obtenerPedidosService,
        private ObtenerPrendasRecibosService $obtenerPrendasRecibosService,
        private OperarioRepository $operarioRepository,
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private VerPedidoOperarioUseCase $verPedidoOperarioUseCase,
        private ObtenerDatosRecibosOperarioUseCase $obtenerDatosRecibosOperarioUseCase,
        private GetPedidoDataOperarioUseCase $getPedidoDataOperarioUseCase,
    ) {
        $this->middleware('auth')->except(['getPedidoData']);
        $this->middleware('operario-access')->except(['getPedidoData']);
    }

    /**
     * Debug: Ver datos del usuario y procesos
     */
    public function debug()
    {
        $usuario = auth()->user();
        $area = $usuario->roles()->first()?->name === 'cortador' ? 'Corte' : 'Costura';

        // Obtener TODOS los procesos sin filtros
        $todosProcesos = \App\Models\ProcesoPrenda::all();

        // Procesos filtrados por Area (sin filtrar por estado)
        $procesesPorArea = \App\Models\ProcesoPrenda::where('proceso', $area)
            ->get();

        return response()->json([
            'usuario_actual' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->roles()->first()?->name,
                'area_buscada' => $area
            ],
            'total_procesos_en_bd' => $todosProcesos->count(),
            'todos_procesos' => $todosProcesos->map(function ($p) {
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'proceso' => $p->proceso,
                    'encargado' => $p->encargado,
                    'estado_proceso' => $p->estado_proceso
                ];
            }),
            'procesos_filtrados_por_area' => $procesesPorArea->map(function ($p) {
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'proceso' => $p->proceso,
                    'encargado' => $p->encargado,
                    'encargado_trim' => trim($p->encargado),
                    'encargado_lower' => strtolower(trim($p->encargado)),
                    'estado_proceso' => $p->estado_proceso
                ];
            }),
            'comparaciones' => $procesesPorArea->map(function ($p) use ($usuario) {
                $encargado_normalizado = strtolower(trim($p->encargado));
                $usuario_normalizado = strtolower(trim($usuario->name));
                return [
                    'numero_pedido' => $p->numero_pedido,
                    'encargado_bd' => $p->encargado,
                    'usuario_name' => $usuario->name,
                    'encargado_normalizado' => $encargado_normalizado,
                    'usuario_normalizado' => $usuario_normalizado,
                    'coinciden' => $encargado_normalizado === $usuario_normalizado
                ];
            })
        ]);
    }

    /**
     * Listar pedidos del operario
     */
    public function misPedidos(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return view('operario.mis-pedidos', [
            'operario' => $datosOperario,
            'usuario' => $usuario,
        ]);
    }

    /**
     * Ver detalle de un pedido
     */
    /**
     * Ver detalle de un pedido
     */
    public function verPedido($numeroPedido)
    {
        $result = $this->verPedidoOperarioUseCase->execute((int) $numeroPedido, request());

        if ((int) ($result['status'] ?? 200) === 302) {
            return redirect()->route((string) $result['redirect_route'])
                ->with('error', (string) $result['redirect_error']);
        }

        return view((string) $result['view'], (array) $result['data']);
    }
    public function obtenerPedidosJson(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return response()->json($datosOperario->toArray());
    }
    /**
     * API: Obtener datos del pedido para el modal movil de operarios
     * Endpoint: /api/operario/pedido/{numeroPedido}
     */
    public function obtenerDatosRecibosOperario($numeroPedido)
    {
        try {
            $result = $this->obtenerDatosRecibosOperarioUseCase->execute((int) $numeroPedido, request());
            return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioController] ERROR en obtenerDatosRecibosOperario', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'not found',
                'message' => 'Pedido no encontrado'
            ], 404);
        }
    }

    /**
     * API: Obtener datos completos del pedido (igual que /pedidos-public/{id}/recibos-datos)
     * Usa el mismo endpoint y logica que el modulo de recibos publicos
     */
    public function getPedidoData($numeroPedido)
    {
        $result = $this->getPedidoDataOperarioUseCase->execute((int) $numeroPedido, request());

        // FILTRAR POR PRENDA_ID si se proporciona
        $prendaIdParam = request()->query('prenda_id');
        if ($prendaIdParam !== null && isset($result['payload']['data']['prendas'])) {
            $prendaIdParam = (int) $prendaIdParam;

            // Filtrar solo la prenda especificada
            $prendasFiltradas = array_filter(
                $result['payload']['data']['prendas'],
                fn($prenda) => (int) ($prenda['id'] ?? 0) === $prendaIdParam
            );

            // Si encontramos la prenda, dejarla como unica
            if (!empty($prendasFiltradas)) {
                $result['payload']['data']['prendas'] = array_values($prendasFiltradas);
            }
        }

        return response()->json($result['payload'] ?? [], (int) ($result['status'] ?? 200));
    }

    /**
     * API: Obtener datos de una prenda de bodega para el modal de asignacion.
     * Endpoint: /operario/api/prenda-bodega/{prendaBodegaId}
     */
    public function obtenerDatosPrendaBodega($prendaBodegaId): JsonResponse
    {
        try {
            $prenda = PrendaBodega::with([
                'tallas' => function ($query) {
                    $query->orderBy('genero')->orderBy('talla')->orderBy('color');
                },
            ])->findOrFail((int) $prendaBodegaId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => (int) $prenda->id,
                    'numero_recibo' => (int) $prenda->numero_recibo,
                    'nombre' => (string) $prenda->nombre,
                    'descripcion' => (string) $prenda->descripcion,
                    'tallas' => $prenda->tallas->map(function ($talla) {
                        return [
                            'talla' => (string) $talla->talla,
                            'genero' => $talla->genero ? (string) $talla->genero : null,
                            'color' => $talla->color ? (string) $talla->color : null,
                            'cantidad' => (int) $talla->cantidad,
                        ];
                    })->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('[OperarioController] Error obteniendo datos de prenda de bodega', [
                'prenda_bodega_id' => $prendaBodegaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la prenda de bodega',
            ], 404);
        }
    }

    public function debugPrendasRecibos()
    {
        try {
            $usuario = Auth::user();

            // Obtener prendas con recibos usando el servicio
            $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);

            // Obtener informacion de la BD sin filtros
            $todosPedidos = \App\Models\PedidoProduccion::where('area', 'costura')
                ->select('id', 'numero_pedido', 'estado', 'area')
                ->get();

            $receptivos = \App\Models\ConsecutivoReciboPedido::where('activo', 1)
                ->whereIn('tipo_recibo', ['REFLECTIVO', 'COSTURA'])
                ->with(['pedido:id,numero_pedido,estado', 'prenda:id,nombre_prenda'])
                ->get();

            $detallesProcesos = \App\Models\PedidosProcesosPrendaDetalle::select('id', 'prenda_pedido_id', 'estado', 'tipo_recibo')
                ->whereIn('estado', ['APROBADO', 'PENDIENTE'])
                ->get();

            return response()->json([
                'success' => true,
                'usuario' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'roles' => $usuario->roles()->pluck('name')->toArray()
                ],
                'prendas_con_recibos_filtradas' => [
                    'total' => $prendasConRecibos->count(),
                    'datos' => $prendasConRecibos->map(function ($p) {
                        return [
                            'numero_pedido' => $p['numero_pedido'],
                            'nombre_prenda' => $p['nombre_prenda'],
                            'total_recibos' => $p['total_recibos'],
                            'tipos_recibos' => array_map(fn($r) => $r['tipo_recibo'], $p['recibos'])
                        ];
                    })->toArray()
                ],
                'todos_pedidos_costura' => [
                    'total' => $todosPedidos->count(),
                    'datos' => $todosPedidos->map(function ($p) {
                        return [
                            'numero_pedido' => $p->numero_pedido,
                            'estado' => $p->estado,
                        ];
                    })->toArray()
                ],
                'recibos_si_filtros' => [
                    'total' => $receptivos->count(),
                    'datos' => $receptivos->map(function ($r) {
                        return [
                            'tipo_recibo' => $r->tipo_recibo,
                            'pedido_numero' => $r->pedido?->numero_pedido,
                            'pedido_estado' => $r->pedido?->estado,
                            'prenda_nombre' => $r->prenda?->nombre_prenda,
                        ];
                    })->toArray()
                ],
                'detalles_procesos' => [
                    'total' => $detallesProcesos->count(),
                    'aprobados' => $detallesProcesos->where('estado', 'APROBADO')->count(),
                    'pendientes' => $detallesProcesos->where('estado', 'PENDIENTE')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

