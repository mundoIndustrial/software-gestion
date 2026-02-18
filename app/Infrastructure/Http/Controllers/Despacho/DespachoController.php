<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PedidoObservacionesDespacho;
use App\Models\DesparChoParcialesModel;
use App\Models\Role;
use App\Models\ReciboPrenda;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Application\Bodega\Services\BodegaPedidoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DespachoController extends Controller
{
    public function __construct(
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private GuardarDespachoUseCase $guardarDespacho,
        private PedidoProduccionRepository $pedidoRepository,
        private BodegaPedidoService $bodegaPedidoService,
    ) {}

    /**
     * Listar pedidos disponibles para despacho
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        
        $query = PedidoProduccion::query()
            ->whereIn('estado', ['En Ejecución', 'Entregado', 'Pendiente', 'PENDIENTE_SUPERVISOR'])
            ->orderByDesc('created_at');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
        }
        
        $pedidos = $query->paginate(20)->withQueryString();
        
        return view('despacho.index', compact('pedidos', 'search'));
    }

    /**
     * Mostrar detalle de despacho para un pedido
     */
    public function show(PedidoProduccion $pedido)
    {
        $pedido->load(['cliente', 'prendas.prenda', 'epps.epp']);
        
        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
        
        $prendas = $filas->where('tipo', 'prenda');
        $epps = $filas->where('tipo', 'epp');
        
        return view('despacho.show', compact('pedido', 'prendas', 'epps'));
    }

    /**
     * Guardar control de entregas (despacho)
     */
    public function guardarDespacho(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'despachos' => 'required|array',
                'despachos.*.tipo' => 'required|string|in:prenda,epp',
                'despachos.*.id' => 'required|integer',
                'despachos.*.talla_id' => 'nullable|integer',
                'despachos.*.genero' => 'nullable|string',
                'despachos.*.pendiente_inicial' => 'required|integer|min:0',
                'despachos.*.parcial_1' => 'nullable|integer|min:0',
                'despachos.*.pendiente_1' => 'nullable|integer|min:0',
                'despachos.*.parcial_2' => 'nullable|integer|min:0',
                'despachos.*.pendiente_2' => 'nullable|integer|min:0',
                'despachos.*.parcial_3' => 'nullable|integer|min:0',
                'despachos.*.pendiente_3' => 'nullable|integer|min:0',
                'cliente_empresa' => 'nullable|string',
                'fecha_hora' => 'nullable|string',
            ]);

            $control = new ControlEntregasDTO(
                pedidoId: $pedido->id,
                numeroPedido: $pedido->numero_pedido,
                despachos: $validated['despachos'],
                clienteEmpresa: $validated['cliente_empresa'] ?? '',
                fechaHora: $validated['fecha_hora'] ?? now()->toDateTimeString(),
            );

            $resultado = $this->guardarDespacho->ejecutar($control);

            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('Error al guardar despacho', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vista de impresión del control de entregas
     */
    public function printDespacho(PedidoProduccion $pedido)
    {
        $pedido->load(['cliente', 'prendas.prenda', 'epps.epp']);
        
        $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
        $prendas = $filas->where('tipo', 'prenda');
        $epps = $filas->where('tipo', 'epp');
        
        return view('despacho.print', compact('pedido', 'prendas', 'epps'));
    }

    /**
     * Obtener despachos guardados para un pedido
     */
    public function obtenerDespachos(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $despachos = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->get()
                ->map(function ($despacho) {
                    return [
                        'id' => $despacho->id,
                        'tipo_item' => $despacho->tipo_item,
                        'item_id' => $despacho->item_id,
                        'talla_id' => $despacho->talla_id,
                        'genero' => $despacho->genero,
                        'pendiente_inicial' => $despacho->pendiente_inicial,
                        'parcial_1' => $despacho->parcial_1,
                        'pendiente_1' => $despacho->pendiente_1,
                        'parcial_2' => $despacho->parcial_2,
                        'pendiente_2' => $despacho->pendiente_2,
                        'parcial_3' => $despacho->parcial_3,
                        'pendiente_3' => $despacho->pendiente_3,
                        'observaciones' => $despacho->observaciones,
                        'entregado' => $despacho->entregado,
                        'fecha_entrega' => $despacho->fecha_entrega?->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'despachos' => $despachos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener despachos', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener despachos',
            ], 500);
        }
    }

    /**
     * Obtener datos de factura para un pedido
     */
    public function obtenerFacturaDatos(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $pedido->load(['cliente', 'prendas.prenda', 'epps.epp']);
            
            return response()->json([
                'success' => true,
                'pedido' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente?->nombre,
                    'cliente_empresa' => $pedido->cliente_empresa,
                    'fecha_creacion' => $pedido->created_at?->toISOString(),
                    'estado' => $pedido->estado,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener datos de factura', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos',
            ], 500);
        }
    }

    /**
     * Marcar ítem como entregado
     */
    public function marcarEntregado(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo_item' => 'required|string|in:prenda,epp',
                'item_id' => 'required|integer',
                'talla_id' => 'nullable|integer',
            ]);

            $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('tipo_item', $validated['tipo_item'])
                ->where('item_id', $validated['item_id'])
                ->when($validated['talla_id'], function ($q) use ($validated) {
                    $q->where('talla_id', $validated['talla_id']);
                })
                ->first();

            if (!$despacho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ítem no encontrado',
                ], 404);
            }

            $despacho->update([
                'entregado' => true,
                'fecha_entrega' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ítem marcado como entregado',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al marcar como entregado', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar',
            ], 500);
        }
    }

    /**
     * Obtener estado de entregas
     */
    public function obtenerEstadoEntregas(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $entregas = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('entregado', true)
                ->whereNotNull('fecha_entrega')
                ->get()
                ->map(function ($entrega) {
                    return [
                        'tipo_item' => $entrega->tipo_item,
                        'item_id' => $entrega->item_id,
                        'talla_id' => $entrega->talla_id,
                        'entregado' => true,
                        'fecha_entrega' => $entrega->fecha_entrega,
                    ];
                });

            return response()->json([
                'success' => true,
                'entregas' => $entregas,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entregas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado',
            ], 500);
        }
    }

    /**
     * Deshacer marcado como entregado
     */
    public function deshacerEntregado(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo_item' => 'required|string|in:prenda,epp',
                'item_id' => 'required|integer',
                'talla_id' => 'nullable|integer',
            ]);

            $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('tipo_item', $validated['tipo_item'])
                ->where('item_id', $validated['item_id'])
                ->when($validated['talla_id'], function ($q) use ($validated) {
                    $q->where('talla_id', $validated['talla_id']);
                })
                ->first();

            if (!$despacho) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ítem no encontrado',
                ], 404);
            }

            $despacho->update([
                'entregado' => false,
                'fecha_entrega' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Marcado deshecho',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al deshacer entregado', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar',
            ], 500);
        }
    }

    // ===== MÉTODOS UNIFICADOS PARA PENDIENTES (COSTURA + EPP) =====

    /**
     * Vista unificada de pendientes de costura y EPP para despacho
     */
    public function pendientesUnificados(Request $request)
    {
        $search = $request->query('search', '');
        $tipo = $request->query('tipo', 'todos'); // 'costura', 'epp', 'todos'
        
        return view('despacho.pendientes-unificados', [
            'search' => $search,
            'tipo' => $tipo
        ]);
    }

    /**
     * API para obtener pendientes de costura
     */
    public function obtenerPendientesCostura(Request $request)
    {
        try {
            $search = $request->query('search', '');
            
            // Pedidos pendientes de costura (usar la misma lógica que bodega)
            $query = PedidoProduccion::query()
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '')
                ->where('estado', 'PENDIENTE_INSUMOS');
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero_pedido', 'like', "%{$search}%")
                      ->orWhere('cliente', 'like', "%{$search}%");
                });
            }
            
            $pedidos = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $pedidos->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
                        'tipo' => 'costura'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes de costura: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== OBSERVACIONES ====================

    public function resumenObservaciones(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_ids' => 'required|array',
            'pedido_ids.*' => 'integer',
        ]);

        $pedidoIds = $validated['pedido_ids'];
        $usuario = auth()->user();
        $usuarioId = $usuario?->id;

        // Contar observaciones no leídas (estado = 0) por pedido
        // Excluir las creadas por el mismo usuario
        $resumen = PedidoObservacionesDespacho::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->where('estado', 0)
            ->where(function ($q) use ($usuarioId) {
                $q->whereNull('usuario_id')
                  ->orWhere('usuario_id', '!=', $usuarioId);
            })
            ->selectRaw('pedido_produccion_id, COUNT(*) as unread')
            ->groupBy('pedido_produccion_id')
            ->pluck('unread', 'pedido_produccion_id')
            ->toArray();

        // Construir respuesta con todos los pedidos
        $resultado = [];
        foreach ($pedidoIds as $pedidoId) {
            $resultado[$pedidoId] = [
                'unread' => (int) ($resumen[$pedidoId] ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    public function marcarLeidas(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $usuario = auth()->user();
        $usuarioId = $usuario?->id;

        // Marcar como leídas (estado = 1) las observaciones no leídas de otros usuarios
        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('estado', 0)
            ->where(function ($q) use ($usuarioId) {
                $q->whereNull('usuario_id')
                  ->orWhere('usuario_id', '!=', $usuarioId);
            })
            ->update(['estado' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Observaciones marcadas como leídas',
        ]);
    }

    public function obtenerObservaciones(PedidoProduccion $pedido): JsonResponse
    {
        $rows = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $observaciones = $rows->map(function ($row) {
            return [
                'id' => (string) $row->uuid,
                'contenido' => $row->contenido,
                'usuario_id' => $row->usuario_id,
                'usuario_nombre' => $row->usuario_nombre,
                'usuario_rol' => $row->usuario_rol,
                'ip_address' => $row->ip_address,
                'estado' => (int) $row->estado,
                'created_at' => optional($row->created_at)->toISOString(),
                'updated_at' => optional($row->updated_at)->toISOString(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => $observaciones,
        ]);
    }

    public function guardarObservacion(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $usuario = auth()->user();

        $uuid = Str::uuid();
        $row = PedidoObservacionesDespacho::create([
            'pedido_produccion_id' => $pedido->id,
            'uuid' => $uuid,
            'contenido' => $validated['contenido'],
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name,
            'usuario_rol' => $usuario?->getCurrentRole()?->name ?? null,
            'ip_address' => $request->ip(),
            'estado' => 0,
        ]);

        $observacion = [
            'id' => (string) $row->uuid,
            'contenido' => $row->contenido,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => (int) $row->estado,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Observación guardada exitosamente',
            'data' => $observacion,
        ]);
    }

    public function actualizarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {
        $validated = $request->validate([
            'contenido' => 'required|string|max:5000',
        ]);

        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        }

        $usuario = auth()->user();
        $ownerId = $row->usuario_id;
        if ((string) $ownerId !== (string) ($usuario?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta observación',
            ], 403);
        }

        $row->contenido = $validated['contenido'];
        $row->save();

        $payload = [
            'id' => (string) $row->uuid,
            'contenido' => $row->contenido,
            'usuario_id' => $row->usuario_id,
            'usuario_nombre' => $row->usuario_nombre,
            'usuario_rol' => $row->usuario_rol,
            'ip_address' => $row->ip_address,
            'estado' => (int) $row->estado,
            'created_at' => optional($row->created_at)->toISOString(),
            'updated_at' => optional($row->updated_at)->toISOString(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Observación actualizada correctamente',
            'data' => $payload,
        ]);
    }

    public function eliminarObservacion(Request $request, PedidoProduccion $pedido, string $observacionId): JsonResponse
    {

        $row = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->where('uuid', $observacionId)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Observación no encontrada',
            ], 404);
        }

        $usuario = auth()->user();
        $ownerId = $row->usuario_id;
        if ((string) $ownerId !== (string) ($usuario?->id)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta observación',
            ], 403);
        }

        $row->delete();

        return response()->json([
            'success' => true,
            'message' => 'Observación eliminada correctamente',
        ]);
    }

    /**
     * API para obtener pendientes de EPP
     */
    public function obtenerPendientesEpp(Request $request)
    {
        try {
            $search = $request->query('search', '');
            
            // Pedidos pendientes de EPP (usar la misma lógica que bodega)
            $query = PedidoProduccion::query()
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '')
                ->where('estado', 'No iniciado');
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero_pedido', 'like', "%{$search}%")
                      ->orWhere('cliente', 'like', "%{$search}%");
                });
            }
            
            $pedidos = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $pedidos->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'fecha_creacion' => $pedido->created_at->format('d/m/Y'),
                        'tipo' => 'epp'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes de EPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API para obtener todos los pendientes unificados
     */
    public function obtenerPendientesUnificados(Request $request)
    {
        try {
            $search = $request->query('search', '');
            $tipo = $request->query('tipo', 'todos');
            
            $pendientes = collect();
            
            // Obtener pendientes de costura
            if ($tipo === 'todos' || $tipo === 'costura') {
                $costuraResponse = $this->obtenerPendientesCostura($request);
                if ($costuraResponse->getData()->success) {
                    $pendientes = $pendientes->merge($costuraResponse->getData()->data);
                }
            }
            
            // Obtener pendientes de EPP
            if ($tipo === 'todos' || $tipo === 'epp') {
                $eppResponse = $this->obtenerPendientesEpp($request);
                if ($eppResponse->getData()->success) {
                    $pendientes = $pendientes->merge($eppResponse->getData()->data);
                }
            }
            
            // Ordenar por fecha de creación
            $pendientes = $pendientes->sortByDesc('fecha_creacion')->values();
            
            return response()->json([
                'success' => true,
                'data' => $pendientes,
                'total' => $pendientes->count(),
                'costura_count' => $pendientes->where('tipo', 'costura')->count(),
                'epp_count' => $pendientes->where('tipo', 'epp')->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes unificados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar HTML para la factura
     */
    private function generarHTMLFactura($datos): string
    {
        return view('despacho.partials.factura-content', compact('datos'))->render();
    }

    /**
     * Mostrar detalles de pedido pendiente (vista igual a bodega)
     */
    public function showPendienteUnificado($id)
    {
        try {
            // Usar el mismo servicio que bodega para obtener detalles completos
            $datos = $this->bodegaPedidoService->obtenerDetallePedido($id, true); // <- true para despacho
            
            // DEBUG: Ver qué datos vienen del servicio
            \Log::info('[DESPACHO] Datos del servicio', [
                'pedido_id' => $id,
                'pedido' => $datos['pedido'] ?? 'null',
                'items_count' => isset($datos['items']) ? count($datos['items']) : 0,
                'primer_item' => isset($datos['items'][0]) ? [
                    'numero_pedido' => $datos['items'][0]['numero_pedido'] ?? 'N/A',
                    'tallas_count' => isset($datos['items'][0]['tallas']) ? count($datos['items'][0]['tallas']) : 0,
                    'tallas' => $datos['items'][0]['tallas'] ?? 'null',
                    'descripcion' => $datos['items'][0]['descripcion'] ?? 'null'
                ] : 'null'
            ]);
            
            // Verificar que sea un pedido pendiente
            if (!in_array($datos['pedido']['estado'] ?? '', ['PENDIENTE_INSUMOS', 'No iniciado'])) {
                return redirect()->route('despacho.pendientes')
                    ->with('error', 'Este pedido no es un pendiente válido');
            }
            
            return view('despacho.show-pendiente-bodega', $datos);
        } catch (\Exception $e) {
            \Log::error('[DESPACHO] Error al mostrar detalles del pedido pendiente', [
                'pedido_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('despacho.pendientes')
                ->with('error', 'Error al cargar el pedido: ' . $e->getMessage());
        }
    }
}
