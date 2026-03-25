<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Http\Controllers\Controller;
use App\Application\Operario\Services\ObtenerPedidosOperarioService;
use App\Application\Operario\Services\ObtenerPrendasRecibosService;
use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Application\Operario\UseCases\CompletarReciboOperarioUseCase;
use App\Application\Operario\UseCases\DeshacerReciboOperarioUseCase;
use App\Domain\Operario\Repositories\OperarioRepository;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        private GetOperarioDashboardUseCase $getOperarioDashboardUseCase,
        private CompletarReciboOperarioUseCase $completarReciboOperarioUseCase,
        private DeshacerReciboOperarioUseCase $deshacerReciboOperarioUseCase,
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

        // Procesos filtrados por área (sin filtrar por estado)
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
     * Dashboard del operario
     * Muestra las prendas con sus recibos de costura
     */
    public function dashboard(Request $request)
    {
        $dashboardData = $this->getOperarioDashboardUseCase->execute($request);

        return view('operario.dashboard', [
            'operario' => $dashboardData->operario,
            'prendasConRecibos' => $dashboardData->prendasConRecibos,
            'usuario' => $dashboardData->usuario,
            'tab' => $dashboardData->tab,
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
    public function verPedido($numeroPedido)
    {
        \Log::info('[OperarioController] 📄 INICIO verPedido', [
            'numero_pedido' => $numeroPedido
        ]);

        $usuario = Auth::user();
        
        // Búsqueda directa en BD - sin filtro de usuario
        $pedidoDB = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with('prendas')
            ->first();

        \Log::info('[OperarioController] Búsqueda directa en BD', [
            'numero_pedido' => $numeroPedido,
            'encontrado_en_bd' => !!$pedidoDB,
            'pedido_id' => $pedidoDB->id ?? null
        ]);

        if (!$pedidoDB) {
            \Log::warning('[OperarioController]  Pedido no encontrado en BD');
            return redirect()->route('operario.dashboard')
                ->with('error', 'Pedido no encontrado');
        }

        // Obtener fotos relacionadas del pedido
        $fotos = $this->obtenerFotosPedido($numeroPedido);

        // Obtener parámetros de la URL
        $prendaIdRequest = request('prenda_id');
        $tipoReciboRequest = request('tipo_recibo', 'COSTURA');
        $tipoReciboUpper = strtoupper(trim((string) $tipoReciboRequest));
        $consecutivoParcialParam = request('consecutivo_parcial');

        // Obtener número de recibo COSTURA para operarios
        $numeroReciboCostura = null;

        // Si es un recibo parcial, el consecutivo a mostrar es el parcial
        if ($tipoReciboUpper === 'PARCIAL' && $consecutivoParcialParam !== null && $consecutivoParcialParam !== '') {
            $numeroReciboCostura = (string) $consecutivoParcialParam;
        }
        
        // Si viene prenda_id específicamente, obtener el recibo de esa prenda
        if ($prendaIdRequest && $numeroReciboCostura === null) {
            $reciboEspecifico = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('prenda_id', $prendaIdRequest)
                ->where('tipo_recibo', $tipoReciboRequest)
                ->where('activo', 1)
                ->first();
            
            if ($reciboEspecifico) {
                $numeroReciboCostura = $reciboEspecifico->consecutivo_actual;
            }
        }
        
        // Fallback: si no encuentra con prenda_id específica, obtener el primero
        if (!$numeroReciboCostura) {
            $reciboCostura = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', $tipoReciboRequest)
                ->where('activo', 1)
                ->first();
            
            if ($reciboCostura) {
                $numeroReciboCostura = $reciboCostura->consecutivo_actual;
            }
        }

        \Log::info('[OperarioController]  Renderizando ver-pedido', [
            'numero_pedido' => $numeroPedido,
            'prenda_id_request' => $prendaIdRequest,
            'tipo_recibo_request' => $tipoReciboRequest,
            'total_fotos' => count($fotos),
            'numero_recibo_costura' => $numeroReciboCostura
        ]);

        return view('operario.ver-pedido', [
            'operario' => null, // No necesitamos datos del servicio
            'pedido' => [
                'numero_pedido' => $pedidoDB->numero_pedido,
                'numero_recibo_costura' => $numeroReciboCostura,
                'prenda_id' => $prendaIdRequest,
                'tipo_recibo' => $tipoReciboRequest,
                'cliente' => $pedidoDB->cliente,
                'asesor' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'asesora' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'forma_de_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'forma_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'estado' => $pedidoDB->estado ?? 'Pendiente',
                'area' => 'Operarios',
                'fecha_creacion' => $pedidoDB->created_at ? $pedidoDB->created_at->format('d/m/Y') : date('d/m/Y'),
                'fecha_estimada' => $pedidoDB->fecha_estimada ? $pedidoDB->fecha_estimada->format('d/m/Y') : null,
                'descripcion' => $pedidoDB->descripcion ?? 'N/A',
                'descripcion_prendas' => $pedidoDB->descripcion ?? 'N/A',
                'cantidad' => $pedidoDB->total_prendas ?? 0,
                'novedades' => $pedidoDB->novedades ?? 'Sin novedades',
            ],
            'usuario' => $usuario,
            'fotos' => $fotos,
        ]);
    }

    /**
     * Obtener fotos relacionadas al pedido
     */
    private function obtenerFotosPedido($numeroPedido)
    {
        // Usar caché por 10 minutos para las fotos
        $cacheKey = "fotos_pedido_{$numeroPedido}";
        
        return \Cache::remember($cacheKey, 600, function() use ($numeroPedido) {
            $fotos = [];

            try {
                // Obtener solo las columnas necesarias de la cotización
                $pedido = \App\Models\PedidoProduccion::select('id', 'cotizacion_id')
                    ->where('numero_pedido', $numeroPedido)
                    ->first();
                
                if (!$pedido || !$pedido->cotizacion_id) {
                    return [];
                }

                // Obtener IDs de prendas de una sola query
                $prendasCotIds = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($prendasCotIds)) {
                    return [];
                }

                // ===== 1. FOTOS DE PRENDAS =====
                $fotosPrendas = \App\Models\PrendaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();
                
                foreach($fotosPrendas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                // ===== 2. FOTOS DE TELAS =====
                $fotosTelas = \App\Models\PrendaTelaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();
                
                foreach($fotosTelas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                // ===== 3. FOTOS DE LOGOS =====
                $logoCotIds = \App\Models\LogoCotizacion::select('id')
                    ->where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($logoCotIds)) {
                    $fotosLogos = \App\Models\LogoFotoCot::select('ruta_webp', 'ruta_original')
                        ->whereIn('logo_cotizacion_id', $logoCotIds)
                        ->orderBy('orden')
                        ->get();
                    
                    foreach($fotosLogos as $foto) {
                        $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                        if($ruta) $fotos[] = $ruta;
                    }
                }
                
            } catch (\Exception $e) {
                \Log::error('Error en obtenerFotosPedido: ' . $e->getMessage());
                return [];
            }

            return $fotos;
        });
    }

    /**
     * API: Obtener pedidos en JSON
     */
    public function obtenerPedidosJson(Request $request)
    {
        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        return response()->json($datosOperario->toArray());
    }

    public function listarNotificacionesRecibos(Request $request): JsonResponse
    {
        try {
            $usuario = Auth::user();

            $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', 'COSTURA')));
            $since = trim((string) $request->query('since', ''));
            $limit = (int) $request->query('limit', 50);
            if ($limit <= 0) {
                $limit = 50;
            }
            $limit = min($limit, 200);

            $query = ConsecutivoReciboPedido::query()
                ->where('activo', 1)
                ->where('tipo_recibo', $tipoRecibo)
                ->whereNotIn('id', function ($sub) use ($usuario, $tipoRecibo) {
                    $sub->select('consecutivo_recibo_id')
                        ->from('recibos_usuario_vistos')
                        ->where('user_id', (int) $usuario->id)
                        ->where('tipo_recibo', $tipoRecibo);
                })
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->with(['pedido:id,numero_pedido,cliente']);

            // Para administrador-costura: no cargar historial completo al entrar.
            // Solo retornar notificaciones recientes (últimos 5 min) a menos que el cliente envíe un `since` explícito.
            if ($usuario->hasRole('administrador-costura')) {
                if ($since !== '') {
                    try {
                        $sinceDt = \Carbon\Carbon::parse($since);
                        $query->where('updated_at', '>=', $sinceDt);
                    } catch (\Exception $e) {
                        $query->where('updated_at', '>=', now()->subMinutes(5));
                    }
                } else {
                    $query->where('updated_at', '>=', now()->subMinutes(5));
                }
            }

            // Notificaciones SOLO cuando el recibo esté ASIGNADO al usuario (encargado) en el proceso correspondiente
            $encargadoNormalizado = strtolower(trim((string) ($usuario->name ?? '')));
            if ($usuario->hasRole('cortador')) {
                $query->where('area', 'Corte');

                if ($encargadoNormalizado !== '') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw("LOWER(TRIM(pp.proceso)) = 'corte'")
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                } else {
                    // Si el usuario no tiene nombre, no se puede asignar por encargado
                    $query->whereRaw('1 = 0');
                }
            } elseif ($usuario->hasRole('costurero')) {
                $query->where('area', 'Costura');

                if ($encargadoNormalizado !== '') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw("LOWER(TRIM(pp.proceso)) = 'costura'")
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $recibos = $query->get(['id', 'pedido_produccion_id', 'tipo_recibo', 'consecutivo_actual', 'created_at', 'updated_at']);

            $items = $recibos->map(function (ConsecutivoReciboPedido $recibo) {
                return [
                    'id' => (int) $recibo->id,
                    'numero_recibo' => (int) ($recibo->consecutivo_actual ?? 0),
                    'cliente' => (string) ($recibo->pedido->cliente ?? '-'),
                    'fecha' => $recibo->updated_at ? $recibo->updated_at->format('d/m/Y H:i') : ($recibo->created_at ? $recibo->created_at->format('d/m/Y H:i') : ''),
                    'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'total' => $items->count(),
                'notificaciones' => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error listarNotificacionesRecibos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al listar notificaciones',
                'total' => 0,
                'notificaciones' => [],
            ], 500);
        }
    }

    public function marcarNotificacionReciboLeida(Request $request, $id): JsonResponse
    {
        try {
            $usuario = Auth::user();

            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));
            $reciboId = (int) $id;

            $recibo = ConsecutivoReciboPedido::query()
                ->where('id', $reciboId)
                ->where('tipo_recibo', $tipoRecibo)
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                ], 404);
            }

            DB::table('recibos_usuario_vistos')->insertOrIgnore([
                'consecutivo_recibo_id' => $reciboId,
                'user_id' => (int) $usuario->id,
                'tipo_recibo' => $tipoRecibo,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'recibo_id' => $reciboId,
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error marcarNotificacionReciboLeida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como leída',
            ], 500);
        }
    }

    public function marcarTodasNotificacionesRecibosLeidas(Request $request): JsonResponse
    {
        try {
            $usuario = Auth::user();
            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));

            $query = ConsecutivoReciboPedido::query()
                ->where('activo', 1)
                ->where('tipo_recibo', $tipoRecibo)
                ->whereNotIn('id', function ($sub) use ($usuario, $tipoRecibo) {
                    $sub->select('consecutivo_recibo_id')
                        ->from('recibos_usuario_vistos')
                        ->where('user_id', (int) $usuario->id)
                        ->where('tipo_recibo', $tipoRecibo);
                });

            $encargadoNormalizado = strtolower(trim((string) ($usuario->name ?? '')));
            if ($usuario->hasRole('cortador')) {
                $query->where('area', 'Corte');

                if ($encargadoNormalizado !== '') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw("LOWER(TRIM(pp.proceso)) = 'corte'")
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif ($usuario->hasRole('costurero')) {
                $query->where('area', 'Costura');

                if ($encargadoNormalizado !== '') {
                    $query->whereExists(function ($sub) use ($encargadoNormalizado) {
                        $sub->select(DB::raw(1))
                            ->from('procesos_prenda as pp')
                            ->join('pedidos_produccion as ped', 'ped.numero_pedido', '=', 'pp.numero_pedido')
                            ->whereRaw("LOWER(TRIM(pp.proceso)) = 'costura'")
                            ->whereRaw('LOWER(TRIM(pp.encargado)) = ?', [$encargadoNormalizado])
                            ->whereColumn('ped.id', 'consecutivos_recibos_pedidos.pedido_produccion_id')
                            ->whereColumn('pp.prenda_pedido_id', 'consecutivos_recibos_pedidos.prenda_id')
                            ->whereNull('pp.deleted_at');
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $ids = $query->pluck('id')->map(fn($v) => (int) $v)->all();

            if (!empty($ids)) {
                $now = now();
                $rows = array_map(function ($reciboId) use ($usuario, $tipoRecibo, $now) {
                    return [
                        'consecutivo_recibo_id' => (int) $reciboId,
                        'user_id' => (int) $usuario->id,
                        'tipo_recibo' => $tipoRecibo,
                        'created_at' => $now,
                    ];
                }, $ids);

                DB::table('recibos_usuario_vistos')->insertOrIgnore($rows);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas',
                'total' => count($ids),
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error marcarTodasNotificacionesRecibosLeidas', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar todas como leídas',
            ], 500);
        }
    }

    /**
     * API: Obtener datos del pedido para el modal móvil de operarios
     * Endpoint: /api/operario/pedido/{numeroPedido}
     */
    public function obtenerDatosRecibosOperario($numeroPedido)
    {
        try {
            $tipoRecibo = request('tipo_recibo', 'COSTURA');
            $parcialId = request('parcial_id');
            $consecutivoParcial = request('consecutivo_parcial');

            \Log::info('[OperarioController] INICIO obtenerDatosRecibosOperario', [
                'numero_pedido' => $numeroPedido,
                'tipo_recibo' => $tipoRecibo,
                'parcial_id' => $parcialId,
                'consecutivo_parcial' => $consecutivoParcial,
                'query_params_completos' => request()->query(),
                'url_actual' => request()->fullUrl()
            ]);

            $usuario = Auth::user();
            \Log::info('[OperarioController] Usuario autenticado', [
                'usuario_id' => $usuario->id ?? null,
                'usuario_name' => $usuario->name ?? null
            ]);

            // Obtener el pedido
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            \Log::info('[OperarioController] Búsqueda de pedido', [
                'numero_pedido' => $numeroPedido,
                'encontrado' => !!$pedido,
                'pedido_id' => $pedido->id ?? null,
                'tipo_recibo' => $tipoRecibo,
                'parcial_id' => $parcialId
            ]);

            if (!$pedido) {
                \Log::warning('[OperarioController] Pedido no encontrado', [
                    'numero_pedido' => $numeroPedido
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Si es una solicitud de parcial, obtener datos del parcial
            if ($tipoRecibo === 'PARCIAL' && $parcialId) {
                \Log::info('[OperarioController] Obteniendo datos del parcial', [
                    'parcial_id' => $parcialId,
                    'parcial_id_type' => gettype($parcialId),
                    'consecutivo_parcial' => $consecutivoParcial
                ]);

                try {
                    $parcialId = (int) $parcialId;
                    $parcial = \App\Models\ReciboPorPartes::with(['tallas', 'pedido', 'prenda'])
                        ->findOrFail($parcialId);

                    \Log::info('[OperarioController] Parcial encontrado', [
                        'parcial_id' => $parcial->id,
                        'pedido_id' => $parcial->pedido_produccion_id,
                        'prenda_id' => $parcial->prenda_pedido_id,
                        'tallas_count' => $parcial->tallas->count()
                    ]);

                    // Usar ObtenerPedidoUseCase para obtener todos los datos
                    $datosPedido = $this->obtenerPedidoUseCase->ejecutar($pedido->id, false);
                    $responseData = $datosPedido->toArray();

                    // Filtrar prendas: solo la del parcial
                    if (isset($responseData['prendas']) && $parcial->prenda_pedido_id) {
                        $responseData['prendas'] = collect($responseData['prendas'])
                            ->filter(function($prenda) use ($parcial) {
                                return isset($prenda['prenda_id']) && $prenda['prenda_id'] == $parcial->prenda_pedido_id;
                            })
                            ->map(function($prenda) use ($parcial) {
                                // Reemplazar recibos con los del parcial
                                $prenda['recibos'] = [[
                                    'id' => $parcial->id,
                                    'consecutivo_actual' => (float) $parcial->consecutivo_parcial,
                                    'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                                    'consecutivo_original' => (float) $parcial->consecutivo_original,
                                    'tipo_recibo' => $parcial->tipo_recibo,
                                    'area' => $parcial->area,
                                    'encargado' => $parcial->encargado,
                                    'tallas' => $parcial->tallas->map(function($talla) {
                                        return [
                                            'talla' => $talla->talla,
                                            'cantidad' => (int) $talla->cantidad,
                                            'color' => $talla->color_nombre ?? 'N/A',
                                        ];
                                    })->toArray()
                                ]];
                                return $prenda;
                            })
                            ->values()
                            ->toArray();
                    }

                    \Log::info('[OperarioController] Respuesta de parcial enviada', [
                        'keys' => array_keys($responseData),
                        'tiene_prendas' => isset($responseData['prendas']),
                        'total_prendas' => count($responseData['prendas'] ?? [])
                    ]);

                    // Retornar en el formato esperado por el componente
                    return response()->json([
                        'success' => true,
                        'data' => $responseData
                    ]);
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    \Log::error('[OperarioController] Parcial no encontrado', [
                        'parcial_id' => $parcialId,
                        'error' => $e->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'error' => 'not found',
                        'message' => 'Parcial no encontrado'
                    ], 404);
                }
            }

            \Log::info('[OperarioController] Llamando ObtenerPedidoUseCase');
            
            // Usar ObtenerPedidoUseCase para obtener todos los datos
            $datosPedido = $this->obtenerPedidoUseCase->ejecutar($pedido->id, false);

            \Log::info('[OperarioController] Datos obtenidos del UseCase');

            // Convertir a array
            $responseData = $datosPedido->toArray();

            \Log::info('[OperarioController] Respuesta enviada', [
                'keys' => array_keys($responseData),
                'tiene_prendas' => isset($responseData['prendas']),
                'total_prendas' => count($responseData['prendas'] ?? [])
            ]);

            // Retornar en el formato esperado por el componente
            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
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
     * Buscar pedido
     */
    public function buscarPedido(Request $request)
    {
        $request->validate([
            'busqueda' => 'required|string|min:2',
        ]);

        $usuario = Auth::user();
        $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);

        $busqueda = strtolower($request->input('busqueda'));

        $resultados = collect($datosOperario->pedidos)
            ->filter(function ($pedido) use ($busqueda) {
                return str_contains(strtolower($pedido['numero_pedido']), $busqueda) ||
                       str_contains(strtolower($pedido['cliente']), $busqueda) ||
                       str_contains(strtolower($pedido['descripcion']), $busqueda);
            })
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
            'total' => count($resultados),
        ]);
    }

    /**
     * Obtener novedades existentes de un pedido
     */
    public function obtenerNovedades($numeroPedido)
    {
        try {
            // Obtener novedades de procesos_prenda
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)->first();
            $novedades = $proceso?->novedades ?? '';
            
            return response()->json([
                'success' => true,
                'novedades' => $novedades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reportar pendiente - Cambiar estado del proceso a Pendiente y guardar novedad
     * OPTIMIZADO: Guarda novedad en procesos_prenda, pedidos_produccion y tabla_original_bodega
     */
    public function reportarPendiente(Request $request)
    {
        $request->validate([
            'numero_pedido' => 'required|numeric',
            'novedad' => 'required|string',
        ]);

        $usuario = Auth::user();
        $numeroPedido = $request->input('numero_pedido');
        $novedad = $request->input('novedad');

        try {
            // Obtener el área del usuario
            $area = $usuario->hasRole('cortador') ? 'Corte' : 'Costura';

            // Buscar el proceso del usuario en este pedido
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('proceso', $area)
                ->where('encargado', $usuario->name)
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado para este pedido'
                ], 404);
            }

            // Formato de novedad con timestamp y usuario
            $novedadFormato = "[{$usuario->name} - " . now()->format('d-m-Y H:i:s') . "] {$area}: {$novedad}";

            // Cambiar estado a Pendiente y guardar novedad en proceso
            $proceso->update([
                'estado_proceso' => 'Pendiente',
                'observaciones' => $novedad,
                'novedades' => $novedadFormato
            ]);

            // ===== GUARDAR EN pedidos_produccion =====
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if ($pedido) {
                // Concatenar con las novedades existentes
                $novedadesActuales = $pedido->novedades ?? '';
                $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadFormato;
                $pedido->update(['novedades' => $novedadesActualizadas]);
            }

            // ===== GUARDAR EN tabla_original_bodega =====
            $bodega = \DB::table('tabla_original_bodega')
                ->where('pedido', $numeroPedido)
                ->first();
            if ($bodega) {
                // Concatenar con las novedades existentes
                $novedadesActuales = $bodega->novedades ?? '';
                $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadFormato;
                \DB::table('tabla_original_bodega')
                    ->where('pedido', $numeroPedido)
                    ->update(['novedades' => $novedadesActualizadas]);
            }

            // ===== LIMPIAR CACHÉ =====
            \Cache::forget("pedido_data_{$numeroPedido}");
            \Cache::forget("fotos_pedido_{$numeroPedido}");
            // Limpiar caché de registros
            \Cache::forget("registros_index");
            \Cache::forget("registros_search_{$numeroPedido}");
            \Cache::forget("registro_pedido_{$numeroPedido}");

            return response()->json([
                'success' => true,
                'message' => 'Novedad reportada correctamente. El estado ha sido cambiado a Pendiente.',
                'estado_nuevo' => 'Pendiente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reportar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener datos completos del pedido (igual que /pedidos-public/{id}/recibos-datos)
     * Usa el mismo endpoint y lógica que el módulo de recibos públicos
     */
    public function getPedidoData($numeroPedido)
    {
        try {
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'not found',
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $pedidoId = $pedido->id;
            \Log::info("[OperarioController.getPedidoData] Obteniendo detalles", [
                'numero_pedido' => $numeroPedido,
                'pedido_id' => $pedidoId
            ]);

            // VALIDACIÓN BODEGUERO: No puede ver recibos si pedido está en pendiente_cartera o RECHAZADO_CARTERA
            $esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');
            if ($esBodyguero) {
                $estadoPedido = strtolower($pedido->estado ?? '');
                if ($estadoPedido === 'pendiente_cartera' || $estadoPedido === 'rechazado_cartera') {
                    \Log::warning('[OperarioController.getPedidoData] 🔐 Bodeguero bloqueado - Pedido en estado: ' . $pedido->estado, [
                        'numero_pedido' => $numeroPedido,
                        'usuario_id' => auth()->id(),
                        'estado' => $pedido->estado
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes ver recibos de pedidos en estado ' . $pedido->estado
                    ], 403);
                }
            }

            // FILTRO BODEGUERO: Si es bodeguero, verificar que tenga recibo COSTURA-BODEGA
            $esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');
            if ($esBodyguero) {
                // Verificar si este pedido tiene un recibo COSTURA-BODEGA activo
                $tieneCosturaBodega = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('tipo_recibo', 'COSTURA-BODEGA')
                    ->where('activo', 1)
                    ->exists();
                
                \Log::info('[OperarioController.getPedidoData] 🔐 VERIFICANDO RECIBO COSTURA-BODEGA', [
                    'numero_pedido' => $numeroPedido,
                    'pedido_id' => $pedidoId,
                    'usuario_id' => auth()->id(),
                    'tiene_costura_bodega' => $tieneCosturaBodega
                ]);
                
                if (!$tieneCosturaBodega) {
                    \Log::warning('[OperarioController.getPedidoData] 🔐 Bodeguero intenta ver pedido sin recibo COSTURA-BODEGA', [
                        'numero_pedido' => $numeroPedido,
                        'pedido_id' => $pedidoId,
                        'usuario_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene recibo de COSTURA-BODEGA disponible'
                    ], 403);
                }
            }

            $response = $this->obtenerPedidoUseCase->ejecutar($pedidoId, false);
            $responseData = $response->toArray();

            // NOTA: El parámetro tipo_recibo es SOLO informativo para el frontend
            // NO se debe usar para filtrar procesos, solo para indicar cuál mostrar primero
            // Todos los procesos deben ser devueltos para poder mostrar fotos de todos ellos
            $tipoReciboFiltro = request('tipo_recibo', '');
            $prendaIdFiltro = request('prenda_id', null);
            
            \Log::info('[OperarioController.getPedidoData]  Tipo recibo solicitado (informativo, NO filtra procesos)', [
                'numero_pedido' => $numeroPedido,
                'tipo_recibo' => $tipoReciboFiltro,
                'prenda_id_filtro' => $prendaIdFiltro
            ]);

            // Si se solicita una prenda específica, filtrar solo esa prenda
            if ($prendaIdFiltro && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                $prendaIdFiltroInt = (int) $prendaIdFiltro;
                $prendasFiltradas = array_filter($responseData['prendas'], function($prenda) use ($prendaIdFiltroInt) {
                    $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
                    return (int)$prendaId === $prendaIdFiltroInt;
                });
                
                $responseData['prendas'] = array_values($prendasFiltradas); // Reindexar
                
                \Log::info('[OperarioController.getPedidoData] Filtrando por prenda específica', [
                    'numero_pedido' => $numeroPedido,
                    'prenda_id_solicitada' => $prendaIdFiltroInt,
                    'prendas_encontradas' => count($prendasFiltradas)
                ]);
            }

            // ================================
            // SOPORTE RECIBO PARCIAL (Distribución por módulos)
            // ================================
            // Cuando se abre /operario/pedido/{numeroPedido}?tipo_recibo=PARCIAL&parcial_id=...&consecutivo_parcial=...
            // la vista consume este endpoint y debe devolver solo tallas/variantes del parcial.
            $tipoReciboUpper = strtoupper(trim((string) request('tipo_recibo', '')));
            $parcialIdParam = request('parcial_id');
            $consecutivoParcialParam = request('consecutivo_parcial');

            if ($tipoReciboUpper === 'PARCIAL' && ($parcialIdParam || $consecutivoParcialParam)) {
                try {
                    $parcialQuery = \App\Models\ReciboPorPartes::query()
                        ->where('pedido_produccion_id', $pedidoId);

                    if ($parcialIdParam) {
                        $parcialQuery->where('id', (int) $parcialIdParam);
                    }
                    if ($consecutivoParcialParam !== null && $consecutivoParcialParam !== '') {
                        $parcialQuery->where('consecutivo_parcial', (string) $consecutivoParcialParam);
                    }
                    if ($prendaIdFiltro) {
                        $parcialQuery->where('prenda_pedido_id', (int) $prendaIdFiltro);
                    }

                    $parcial = $parcialQuery->with('tallas')->first();

                    if ($parcial && isset($responseData['prendas']) && is_array($responseData['prendas']) && count($responseData['prendas']) > 0) {
                        $tallasParcial = $parcial->tallas ?? collect();
                        $tallasMap = [];
                        foreach ($tallasParcial as $t) {
                            $key = strtoupper(trim((string) $t->talla));
                            if ($key === '') continue;
                            $tallasMap[$key] = (int) $t->cantidad;
                        }

                        foreach ($responseData['prendas'] as &$prenda) {
                            $prendaIdResp = (int) ($prenda['id'] ?? $prenda['prenda_pedido_id'] ?? 0);
                            if ($prendaIdFiltro && $prendaIdResp !== (int) $prendaIdFiltro) {
                                continue;
                            }

                            if (!isset($prenda['variantes']) || !is_array($prenda['variantes'])) {
                                continue;
                            }

                            // Mantener solo 1 variante por talla (la primera que coincida)
                            $variantesNuevas = [];
                            $tallasUsadas = [];
                            foreach ($prenda['variantes'] as $var) {
                                $tallaVar = strtoupper(trim((string) ($var['talla'] ?? '')));
                                if ($tallaVar === '' || !array_key_exists($tallaVar, $tallasMap)) {
                                    continue;
                                }
                                if (isset($tallasUsadas[$tallaVar])) {
                                    continue;
                                }
                                $tallasUsadas[$tallaVar] = true;
                                $var['cantidad'] = (int) $tallasMap[$tallaVar];
                                $variantesNuevas[] = $var;
                            }

                            // Si por alguna razón no había variantes del pedido, igual devolver vacío
                            $prenda['variantes'] = $variantesNuevas;
                        }
                        unset($prenda);
                    }
                } catch (\Exception $e) {
                    \Log::warning('[OperarioController.getPedidoData] Error aplicando filtro de parcial', [
                        'numero_pedido' => $numeroPedido,
                        'pedido_id' => $pedidoId,
                        'parcial_id' => $parcialIdParam,
                        'consecutivo_parcial' => $consecutivoParcialParam,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // FILTRO BODEGUERO: Si es bodeguero, filtrar procesos para mostrar SOLO 'costura-bodega'
            if ($esBodyguero && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[OperarioController.getPedidoData] 🔐 FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA', [
                    'numero_pedido' => $numeroPedido,
                    'usuario_id' => auth()->id()
                ]);
                
                foreach ($responseData['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        // Filtrar: solo mantener procesos 'costura-bodega'
                        $procesosFiltrados = array_filter($prenda['procesos'], function($proceso) {
                            // Intentar obtener el nombre del proceso desde varias claves posibles
                            $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? '';
                            $tipoLower = strtolower(trim($tipoProceso));
                            
                            \Log::debug('[OperarioController.getPedidoData] Verificando proceso para bodeguero', [
                                'tipo_proceso' => $tipoProceso,
                                'tipo_lower' => $tipoLower,
                                'es_costura_bodega' => $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega'
                            ]);
                            
                            return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
                        });
                        
                        $prenda['procesos'] = array_values($procesosFiltrados); // Reindexar array
                    }
                }
            }

            $this->enriquecerPrendasConConsecutivos($responseData, $pedidoId);
            $this->agregarRecibosParcialesAProcesos($responseData, $pedidoId);
            $this->agregarAnchoMetrajeGeneral($responseData, $pedido);

            // Si se solicita un tipo_recibo que NO es COSTURA, la vista debe mostrar tallas del ANEXO
            // (pedidos_parciales_tallas). Para evitar que el frontend muestre tallas de la prenda por error,
            // limpiamos las tallas a nivel prenda en esta respuesta.
            $tipoReciboFiltroUpper = strtoupper((string) request('tipo_recibo', ''));
            $tiposCostura = ['COSTURA', 'COSTURA-BODEGA', 'REFLECTIVO'];
            if ($tipoReciboFiltroUpper && !in_array($tipoReciboFiltroUpper, $tiposCostura, true)) {
                if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                    foreach ($responseData['prendas'] as &$prenda) {
                        $prenda['tallas'] = [];
                        $prenda['talla_colores'] = [];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[OperarioController.getPedidoData] Error', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enriquecer prendas con consecutivos y ancho/metraje
     */
    private function enriquecerPrendasConConsecutivos(&$responseData, int $pedidoId): void
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return;
        }

        \Log::info('[OperarioController] Enriqueciendo prendas', [
            'pedido_id' => $pedidoId,
            'total_prendas' => count($responseData['prendas'])
        ]);

        foreach ($responseData['prendas'] as &$prenda) {
            $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
            if (!$prendaId) {
                $prenda['ancho_metraje'] = null;
                $prenda['recibos'] = null;
                continue;
            }

            $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->first();

            $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->get();

            if ($anchoGeneral || $metrajesPorColor->isNotEmpty()) {
                $ancho_metraje_data = [
                    'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null,
                    'metraje' => $anchoGeneral ? $anchoGeneral->metraje : null,
                    'metrajes_por_color' => []
                ];
                
                foreach ($metrajesPorColor as $metraje) {
                    $ancho_metraje_data['metrajes_por_color'][] = [
                        'color' => $metraje->color,
                        'metraje' => $metraje->metraje
                    ];
                }
                
                $prenda['ancho_metraje'] = $ancho_metraje_data;
            } else {
                $prenda['ancho_metraje'] = null;
            }

            $prenda['recibos'] = $this->obtenerConsecutivosPrenda($pedidoId, $prendaId);
        }
    }

    private function agregarRecibosParcialesAProcesos(array &$responseData, int $pedidoId): void
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return;
        }

        foreach ($responseData['prendas'] as &$prenda) {
            $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
            if (!$prendaId) {
                continue;
            }

            try {
                $recibosParciales = \DB::table('pedidos_parciales')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('prenda_pedido_id', $prendaId)
                    ->orderBy('tipo_recibo', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($recibosParciales->isEmpty()) {
                    continue;
                }

                $anexosPorTipo = [];
                $procesosAdicionales = [];

                foreach ($recibosParciales as $reciboParcial) {
                    $tipoRecibo = (string) ($reciboParcial->tipo_recibo ?? '');
                    if ($tipoRecibo === '') {
                        continue;
                    }

                    if (!isset($anexosPorTipo[$tipoRecibo])) {
                        $anexosPorTipo[$tipoRecibo] = 0;
                    }
                    $anexosPorTipo[$tipoRecibo]++;

                    $numeroReciboAnexo = $reciboParcial->consecutivo_actual ?? $reciboParcial->numero_recibo ?? null;

                    $tallas = \DB::table('pedidos_parciales_tallas')
                        ->where('pedido_parcial_id', $reciboParcial->id)
                        ->get();

                    $tallasList = [];
                    $tallasTransformadas = [
                        'dama' => [],
                        'caballero' => [],
                        'unisex' => [],
                    ];
                    $tallaColores = [];

                    foreach ($tallas as $talla) {
                        $tallasList[] = [
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'genero' => $talla->genero ?? 'General',
                            'color_nombre' => $talla->color_nombre ?? null,
                        ];

                        $tallaColores[] = [
                            'genero' => $talla->genero ?? null,
                            'talla' => $talla->talla,
                            'cantidad' => (int) $talla->cantidad,
                            'color_nombre' => $talla->color_nombre ?? null,
                        ];

                        $genero = strtolower($talla->genero ?? 'caballero');
                        if ($genero === 'dama') {
                            $genero = 'dama';
                        } elseif ($genero === 'caballero') {
                            $genero = 'caballero';
                        } else {
                            $genero = 'unisex';
                        }

                        $tallasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                    }

                    $imagenesPrenda = [];
                    if (isset($prenda['imagenes']) && is_array($prenda['imagenes'])) {
                        $imagenesPrenda = $prenda['imagenes'];
                    }

                    $procesosAdicionales[] = [
                        'tipo_proceso' => $tipoRecibo,
                        'nombre_proceso' => $tipoRecibo . ' ANEXO ' . $anexosPorTipo[$tipoRecibo],
                        'estado' => $reciboParcial->estado ?? 'PENDIENTE',
                        'numero_recibo' => $numeroReciboAnexo,
                        'es_parcial' => true,
                        'numero_anexo' => $anexosPorTipo[$tipoRecibo],
                        'pedido_parcial_id' => $reciboParcial->id,
                        // IMPORTANT: Para el frontend del recibo, el campo 'tallas' debe ser OBJETO (no lista)
                        // para que lo renderice correctamente. En anexos solo deben mostrarse tallas del anexo.
                        'tallas' => $tallasTransformadas,
                        'tallas_list' => $tallasList,
                        'tallas_transformadas' => $tallasTransformadas,
                        // Soporte para tallas con color (el frontend transforma esto si existe)
                        'talla_colores' => $tallaColores,
                        'created_at' => $reciboParcial->created_at,
                        'imagenes' => $imagenesPrenda,
                    ];
                }

                if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
                    $prenda['procesos'] = [];
                }

                $prenda['procesos'] = array_merge($prenda['procesos'], $procesosAdicionales);
            } catch (\Exception $e) {
                \Log::error('[OperarioController.getPedidoData] Error cargando recibos parciales', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Agregar ancho/metraje general
     */
    private function agregarAnchoMetrajeGeneral(&$responseData, $pedido): void
    {
        $responseData['ancho_metraje'] = [
            'ancho' => $pedido->ancho ?? null,
            'metraje' => $pedido->metraje ?? null,
            'fecha_actualizacion' => $pedido->updated_at ?? null
        ];
    }

    /**
     * Obtener consecutivos de una prenda específica
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array|null
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            \Log::info('[OperarioController] Buscando consecutivos para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            // Obtener consecutivos para este pedido (incluyendo generales y específicos de prenda)
            $consecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('activo', 1)
                ->where(function($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)  // Específicos de esta prenda
                          ->orWhereNull('prenda_id');       // Generales del pedido
                })
                ->get();

            if ($consecutivos->isEmpty()) {
                return null;
            }

            // Estructurar los consecutivos por tipo
            $recibos = [
                'COSTURA' => null,
                'ESTAMPADO' => null,
                'BORDADO' => null,
                'DTF' => null,
                'SUBLIMADO' => null,
                'REFLECTIVO' => null,
                'COSTURA-BODEGA' => null  // Nuevo: Consecutivo para costura-bodega
            ];

            foreach ($consecutivos as $consecutivo) {
                $tipo = $consecutivo->tipo_recibo;
                if (array_key_exists($tipo, $recibos)) {
                    $recibos[$tipo] = $consecutivo->consecutivo_actual;
                }
            }

            return $recibos;

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error obteniendo consecutivos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }


    /**
     * Marcar proceso como completado
     */
    public function completarProceso(Request $request, $numeroPedido)
    {
        try {
            $usuario = Auth::user();
            $area = $usuario->roles()->first()?->name === 'cortador' ? 'Corte' : 'Costura';

            // Buscar el proceso del pedido (sin importar si está completado)
            $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('proceso', $area)
                ->first();

            if (!$proceso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no encontrado'
                ], 404);
            }

            // Si ya está completado, retornar éxito
            if ($proceso->estado_proceso === 'Completado') {
                return response()->json([
                    'success' => true,
                    'message' => 'Proceso ya fue marcado como completado',
                    'already_completed' => true
                ]);
            }

            // Actualizar el proceso a completado
            $proceso->update([
                'estado_proceso' => 'Completado',
                'fecha_fin' => now(),
            ]);

            // Limpiar cache
            \Illuminate\Support\Facades\Cache::forget("pedido_data_{$numeroPedido}");
            \Illuminate\Support\Facades\Cache::forget("fotos_pedido_{$numeroPedido}");

            \Log::info("Proceso marcado como completado", [
                'numero_pedido' => $numeroPedido,
                'area' => $area,
                'usuario' => $usuario->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso marcado como completado'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error al completar proceso: " . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar el proceso'
            ], 500);
        }
    }

    public function completarRecibo(Request $request, $idRecibo)
    {
        $result = $this->completarReciboOperarioUseCase->execute((int) $idRecibo);

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
        ], $result->statusCode);
    }

    public function deshacerRecibo(Request $request, $idRecibo)
    {
        $result = $this->deshacerReciboOperarioUseCase->execute((int) $idRecibo);

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
        ], $result->statusCode);
    }

    /**
     * Obtener datos completos del pedido en formato JSON
     * Para la visualización móvil order-detail-modal-mobile
     */
    public function obtenerPedidoJson($numeroPedido)
    {
        try {
            $usuario = Auth::user();
            
            // Obtener los datos del operario incluyendo el pedido específico
            $datosOperario = $this->obtenerPedidosService->obtenerPedidosDelOperario($usuario);
            
            // Buscar el pedido específico
            $pedido = collect($datosOperario->pedidos)
                ->firstWhere('numero_pedido', $numeroPedido);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            // Obtener las fotos del pedido
            $fotos = $this->obtenerFotosPedido($numeroPedido);
            
            // Construir respuesta con todos los datos necesarios
            return response()->json([
                'success' => true,
                'numero_pedido' => $pedido['numero_pedido'] ?? $numeroPedido,
                'fecha_creacion' => $pedido['fecha_creacion'] ?? $pedido['created_at'] ?? now()->toDateString(),
                'asesora' => $pedido['asesora_nombre'] ?? $pedido['asesora'] ?? 'N/A',
                'cliente' => $pedido['cliente_nombre'] ?? $pedido['cliente'] ?? 'N/A',
                'forma_pago' => $pedido['forma_pago'] ?? $pedido['forma_de_pago'] ?? 'N/A',
                'encargado' => $pedido['asesora_nombre'] ?? $pedido['asesora'] ?? 'N/A',
                'cantidad' => $pedido['cantidad_total'] ?? $pedido['cantidad'] ?? 0,
                'cantidad_total' => $pedido['cantidad_total'] ?? $pedido['cantidad'] ?? 0,
                'total_entregado' => $pedido['total_entregado'] ?? 0,
                'descripcion_prendas' => $pedido['descripcion_prendas'] ?? 'N/A',
                'prendas' => $pedido['prendas'] ?? [],
                'fotos' => $fotos
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error al obtener pedido JSON: " . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DEBUG: Obtener información detallada sobre prendas con recibos
     * Endpoint: /operario/debug/prendas-recibos
     */
    public function debugPrendasRecibos()
    {
        try {
            $usuario = Auth::user();
            
            // Obtener prendas con recibos usando el servicio
            $prendasConRecibos = $this->obtenerPrendasRecibosService->obtenerPrendasConRecibos($usuario);
            
            // Obtener información de la BD sin filtros
            $todosPedidos = \App\Models\PedidoProduccion::where('area', 'costura')
                ->select('id', 'numero_pedido', 'estado', 'area')
                ->get();
            
            $receptivos = \App\Models\ConsecutivoReciboPedido::where('activo', 1)
                ->whereIn('tipo_recibo', ['REFLECTIVO', 'COSTURA', 'COSTURA-BODEGA'])
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
                    'datos' => $prendasConRecibos->map(function($p) {
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
                    'datos' => $todosPedidos->map(function($p) {
                        return [
                            'numero_pedido' => $p->numero_pedido,
                            'estado' => $p->estado,
                        ];
                    })->toArray()
                ],
                'recibos_si_filtros' => [
                    'total' => $receptivos->count(),
                    'datos' => $receptivos->map(function($r) {
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

    /**
     * API: Crear novedad de prenda/recibo
     * POST /operario/api/novedades/crear
     */
    public function crearNovedad(Request $request)
    {
        try {
            $request->validate([
                'numero_pedido' => 'required|numeric',
                'prenda_id' => 'required|numeric',
                'numero_recibo' => 'required|string',
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $usuario = Auth::user();

            // Buscar la prenda
            $prenda = \App\Models\PrendaPedido::find($request->prenda_id);
            if (!$prenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada'
                ], 404);
            }

            // Crear la novedad
            $novedad = \DB::table('prendas_pedido_novedades_recibo')->insert([
                'prenda_pedido_id' => $request->prenda_id,
                'numero_recibo' => $request->numero_recibo,
                'novedad_texto' => $request->novedad_texto,
                'tipo_novedad' => $request->tipo_novedad,
                'estado_novedad' => 'activa',
                'creado_por' => $usuario->id,
                'creado_en' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Actualizar estado del pedido a Pendiente
            \DB::table('pedidos_produccion')
                ->where('numero_pedido', $request->numero_pedido)
                ->update(['estado' => 'Pendiente', 'updated_at' => now()]);

            \Log::info('[OperarioController] Novedad creada', [
                'prenda_id' => $request->prenda_id,
                'usuario_id' => $usuario->id,
                'tipo_novedad' => $request->tipo_novedad,
                'numero_pedido' => $request->numero_pedido
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Novedad registrada correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error creando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener novedades de una prenda
     * GET /operario/api/novedades/{numeroPedido}/{prendaId}
     */
    public function obtenerNovedadesPrenda($numeroPedido, $prendaId)
    {
        try {
            $usuario = Auth::user();

            // Obtener novedades de la prenda
            $novedades = \DB::table('prendas_pedido_novedades_recibo')
                ->where('prenda_pedido_id', $prendaId)
                ->orderBy('creado_en', 'desc')
                ->get()
                ->map(function($n) use ($usuario) {
                    $n->creado_en = \Carbon\Carbon::parse($n->creado_en)->format('d/m/Y H:i');
                    
                    // Obtener nombre y rol del usuario que creó
                    $creador = \App\Models\User::find($n->creado_por);
                    $n->creado_por_nombre = $creador?->name ?? 'Usuario Desconocido';
                    $n->usuario_nombre = $n->creado_por_nombre;
                    
                    // Obtener el rol del usuario
                    if ($creador) {
                        $roles = $creador->getRoleNames()->toArray();
                        $n->creado_por_rol = !empty($roles) ? strtoupper($roles[0]) : 'USUARIO';
                    } else {
                        $n->creado_por_rol = 'USUARIO';
                    }

                    $n->usuario_rol = $n->creado_por_rol;
                    $n->es_mia = $usuario ? ((int)$n->creado_por === (int)$usuario->id) : false;
                    
                    return $n;
                });

            return response()->json([
                'success' => true,
                'novedades' => $novedades->values()
            ]);

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error obteniendo novedades: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener novedades'
            ], 500);
        }
    }

    /**
     * API: Eliminar novedad
     * DELETE /operario/api/novedades/{id}
     */
    public function eliminarNovedad($id)
    {
        try {
            $usuario = Auth::user();

            // Obtener la novedad
            $novedad = \DB::table('prendas_pedido_novedades_recibo')->find($id);
            if (!$novedad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novedad no encontrada'
                ], 404);
            }

            // Verificar permisos - solo el que la creó o admin
            if ($novedad->creado_por !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta novedad'
                ], 403);
            }

            // Eliminar
            \DB::table('prendas_pedido_novedades_recibo')->delete($id);

            \Log::info('[OperarioController] Novedad eliminada', [
                'novedad_id' => $id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Novedad eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error eliminando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar novedad'
            ], 500);
        }
    }

    /**
     * API: Actualizar novedad
     * PUT /operario/api/novedades/{id}
     */
    public function actualizarNovedad(Request $request, $id)
    {
        try {
            $request->validate([
                'novedad_texto' => 'required|string|min:5',
                'tipo_novedad' => 'required|in:observacion,problema,cambio,correccion,aprobacion,rechazo'
            ]);

            $usuario = Auth::user();

            // Obtener la novedad
            $novedad = \DB::table('prendas_pedido_novedades_recibo')->find($id);
            if (!$novedad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Novedad no encontrada'
                ], 404);
            }

            // Verificar permisos - solo el que la creó o admin
            if ($novedad->creado_por !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta novedad'
                ], 403);
            }

            // Actualizar
            \DB::table('prendas_pedido_novedades_recibo')
                ->where('id', $id)
                ->update([
                    'novedad_texto' => $request->novedad_texto,
                    'tipo_novedad' => $request->tipo_novedad,
                    'editado' => 1,
                    'editado_por' => $usuario->id,
                    'editado_en' => now(),
                    'updated_at' => now()
                ]);

            \Log::info('[OperarioController] Novedad actualizada', [
                'novedad_id' => $id,
                'usuario_id' => $usuario->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Novedad actualizada correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error actualizando novedad: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener distribución de recibos por partes
     * GET /operario/api/recibos/{idRecibo}/distribucion
     */
    public function obtenerDistribucionRecibo(Request $request, $idRecibo)
    {
        try {
            $usuario = Auth::user();

            // Solo vista-costura puede acceder a esta ruta
            if (!$usuario->hasRole('vista-costura')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta información'
                ], 403);
            }

            \Log::info('[DistribucionRecibo] Iniciando búsqueda', ['recibo_id' => $idRecibo, 'usuario' => $usuario->id]);

            // Obtener el recibo
            $recibo = \App\Models\ConsecutivoReciboPedido::find($idRecibo);
            if (!$recibo) {
                \Log::warning('[DistribucionRecibo] Recibo no encontrado', ['recibo_id' => $idRecibo]);
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }

            \Log::info('[DistribucionRecibo] Recibo encontrado', [
                'recibo_id' => $recibo->id,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'tipo_recibo' => $recibo->tipo_recibo,
                'consecutivo_actual' => $recibo->consecutivo_actual
            ]);

            // Obtener todos los parciales de este recibo CON sus tallas
            $parciales = \App\Models\ReciboPorPartes::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('prenda_pedido_id', $recibo->prenda_id)
                ->where('tipo_recibo', $recibo->tipo_recibo)
                ->where('consecutivo_original', $recibo->consecutivo_actual)
                ->with('tallas') // Eager load de tallas
                ->get();

            \Log::info('[DistribucionRecibo] Parciales encontrados', ['total' => $parciales->count()]);

            // Obtener el número de pedido
            $pedidoProduccion = \App\Models\PedidoProduccion::find($recibo->pedido_produccion_id);
            $numeroPedido = $pedidoProduccion ? $pedidoProduccion->numero_pedido : null;

            // Si no hay parciales, retornar estructura vacía
            if ($parciales->isEmpty()) {
                \Log::info('[DistribucionRecibo] Sin parciales para este recibo');
                return response()->json([
                    'success' => true,
                    'parciales' => [],
                    'mensaje' => 'No hay parciales creados para este recibo',
                    'total_parciales' => 0
                ]);
            }

            // Obtener información de procesos para cada parcial
            $parciales_info = $parciales->map(function ($parcial) use ($recibo, $numeroPedido) {
                try {
                    // Buscar el proceso relacionado en procesos_prenda
                    $proceso = null;
                    if ($numeroPedido) {
                        $proceso = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                            ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                            ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                            ->first();
                    }

                    // Usar encargado de procesos_prenda si está disponible, sino de recibo_por_partes
                    $encargado = $proceso->encargado ?? $parcial->encargado ?? 'SIN ASIGNAR';
                    // Usar área (proceso) de procesos_prenda si está disponible, sino de recibo_por_partes
                    $area = $proceso->proceso ?? $parcial->area ?? 'SIN ASIGNAR';

                    return [
                        'id' => $parcial->id,
                        'area' => $area,
                        'encargado' => $encargado,
                        'tipo_recibo' => $parcial->tipo_recibo,
                        'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                        'consecutivo_original' => (float) $parcial->consecutivo_original,
                        'proceso_estado' => $proceso->estado_proceso ?? 'Pendiente',
                        'fecha_asignacion' => $proceso->fecha_de_asignacion_encargado ?? null,
                        'observaciones' => $proceso->observaciones ?? '',
                        'pedido_produccion_id' => $parcial->pedido_produccion_id,
                        'prenda_pedido_id' => $parcial->prenda_pedido_id,
                        'numero_pedido' => $numeroPedido,
                        'tallas' => $parcial->tallas->map(function ($talla) {
                            return [
                                'id' => $talla->id,
                                'talla' => $talla->talla,
                                'cantidad' => $talla->cantidad,
                                'color_nombre' => $talla->color_nombre,
                            ];
                        })->toArray(),
                    ];
                } catch (\Exception $e) {
                    \Log::error('[DistribucionRecibo] Error mapeando parcial', [
                        'parcial_id' => $parcial->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            })->sortBy('area')->values();

            \Log::info('[DistribucionRecibo] Parciales procesados exitosamente', ['total' => $parciales_info->count()]);

            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo' => $recibo->consecutivo_actual,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'area_actual' => $recibo->area,
                    'numero_pedido' => $numeroPedido,
                ],
                'parciales' => $parciales_info,
                'total_parciales' => $parciales_info->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('[OperarioController] Error obteniendo distribución: ' . $e->getMessage(), [
                'recibo_id' => $idRecibo,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribución: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deshacer un parcial específico (eliminarlo de todas las tablas)
     * DELETE /operario/api/parciales/{id}/deshacer
     */
    public function deshacerParcial(Request $request, $id)
    {
        try {
            // Validar autenticación
            $usuario = Auth::user();
            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            // Encontrar el parcial
            $parcial = \App\Models\ReciboPorPartes::with(['pedido', 'prenda'])->findOrFail($id);

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener identificadores para borrar procesos_prenda
                $numeroPedido = $parcial->pedido?->numero_pedido;
                $prendaPedidoId = $parcial->prenda_pedido_id;
                $numeroReciboParcial = $parcial->consecutivo_parcial;

                \Log::info('[DeshacerParcial] Iniciando eliminación', [
                    'parcial_id' => $id,
                    'numero_pedido' => $numeroPedido,
                    'prenda_pedido_id' => $prendaPedidoId,
                    'numero_recibo_parcial' => $numeroReciboParcial
                ]);

                // 1. Eliminar tallas asociadas
                $tallasEliminadas = \App\Models\ReciboPorPartesTalla::where('recibo_por_partes_id', $id)->delete();
                \Log::info('[DeshacerParcial] Tallas eliminadas', ['count' => $tallasEliminadas]);

                // 2. Eliminar procesos_prenda asociados
                $procesosEliminados = \App\Models\ProcesoPrenda::where('numero_pedido', $numeroPedido)
                    ->where('prenda_pedido_id', $prendaPedidoId)
                    ->where('numero_recibo_parcial', $numeroReciboParcial)
                    ->delete();
                \Log::info('[DeshacerParcial] Procesos eliminados', ['count' => $procesosEliminados]);

                // 3. Eliminar el parcial
                $parcialEliminado = $parcial->delete();
                \Log::info('[DeshacerParcial] Parcial eliminado', ['deleted' => $parcialEliminado]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Parcial eliminado correctamente',
                    'deleted' => [
                        'tallas' => $tallasEliminadas,
                        'procesos' => $procesosEliminados,
                        'parcial' => $parcialEliminado
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('[DeshacerParcial] Error durante eliminación', [
                    'parcial_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parcial no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar parcial: ' . $e->getMessage()
            ], 500);
        }
    }

}
