<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Events\DespachoPedidoActualizado;
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
use App\Domain\Pedidos\Despacho\Services\DespachoEstadoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\ObservacionDespachoCreada;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DespachoController extends Controller
{
    public function __construct(
        private ObtenerFilasDespachoUseCase $obtenerFilas,
        private GuardarDespachoUseCase $guardarDespacho,
        private PedidoProduccionRepository $pedidoRepository,
        private BodegaPedidoService $bodegaPedidoService,
        private DespachoEstadoService $despachoEstadoService,
    ) {}

    /**
     * Listar pedidos disponibles para despacho
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        
        $query = PedidoProduccion::query()
            ->whereIn('estado', ['Pendiente', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'])
            ->whereNotNull('numero_pedido') // Excluir pedidos sin número de pedido
            ->where('numero_pedido', '!=', '') // Excluir números de pedido vacíos
            ->orderByDesc('created_at');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pedido', 'like', "%{$search}%")
                  ->orWhere('cliente', 'like', "%{$search}%");
            });
        }
        
        $pedidos = $query->paginate(20)->withQueryString();
        
        // Agregar estado de entrega a cada pedido
        $pedidos->getCollection()->transform(function ($pedido) {
            $pedido->estado_entrega = $this->despachoEstadoService->obtenerEstadoEntrega($pedido->id);
            return $pedido;
        });
        
        return view('despacho.index', compact('pedidos', 'search'));
    }

    /**
     * Mostrar detalle de despacho para un pedido
     */
    public function show(PedidoProduccion $pedido)
    {
        $pedido->load(['cliente', 'prendas.pedidoProduccion', 'epps.epp']);
        
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
        $pedido->load(['cliente', 'prendas.pedidoProduccion', 'epps.epp']);
        
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
            // Usar el mismo servicio que bodega para obtener datos completos
            $facturaService = new \App\Domain\Pedidos\Services\FacturaPedidoService();
            $datos = $facturaService->obtenerDatosFactura($pedido->id);
            
            return response()->json($datos);
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
        \Log::info('[DespachoController] marcarEntregado llamado', [
            'pedido_id' => $pedido->id,
            'request_data' => $request->all(),
        ]);
        
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
            
            \Log::info('[DespachoController] Búsqueda de despacho', [
                'pedido_id' => $pedido->id,
                'tipo_item' => $validated['tipo_item'],
                'item_id' => $validated['item_id'],
                'talla_id' => $validated['talla_id'],
                'despacho_encontrado' => $despacho ? 'SI' : 'NO',
                'despacho_id' => $despacho?->id,
            ]);

            // Si no existe, crearlo automáticamente
            if (!$despacho) {
                \Log::info('[DespachoController] Creando registro de despacho automáticamente', [
                    'pedido_id' => $pedido->id,
                    'tipo_item' => $validated['tipo_item'],
                    'item_id' => $validated['item_id'],
                    'talla_id' => $validated['talla_id'],
                ]);
                
                $despacho = DesparChoParcialesModel::create([
                    'pedido_id' => $pedido->id,
                    'tipo_item' => $validated['tipo_item'],
                    'item_id' => $validated['item_id'],
                    'talla_id' => $validated['talla_id'] ?? null,
                    'pendiente_inicial' => 1, // Valor por defecto
                    'entregado' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                \Log::info('[DespachoController] Registro de despacho creado', [
                    'despacho_id' => $despacho->id,
                ]);
            }

            // Marcar como entregado
            $despacho->update([
                'entregado' => true,
                'fecha_entrega' => now(),
            ]);

            // Verificar si todos los ítems del pedido están entregados
            $this->verificarYActualizarEstadoPedido($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Ítem marcado como entregado',
                'despacho_id' => $despacho->id,
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
        \Log::info('[DespachoController] deshacerEntregado llamado', [
            'pedido_id' => $pedido->id,
            'request_data' => $request->all(),
        ]);
        
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
            
            \Log::info('[DespachoController] Búsqueda para deshacer', [
                'pedido_id' => $pedido->id,
                'tipo_item' => $validated['tipo_item'],
                'item_id' => $validated['item_id'],
                'talla_id' => $validated['talla_id'],
                'despacho_encontrado' => $despacho ? 'SI' : 'NO',
                'despacho_id' => $despacho?->id,
                'entregado_actual' => $despacho?->entregado,
            ]);

            if (!$despacho) {
                \Log::warning('[DespachoController] Ítem no encontrado para deshacer', [
                    'pedido_id' => $pedido->id,
                    'tipo_item' => $validated['tipo_item'],
                    'item_id' => $validated['item_id'],
                    'talla_id' => $validated['talla_id'],
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Ítem no encontrado',
                ], 404);
            }

            // Actualizar a no entregado
            $despacho->update([
                'entregado' => false,
                'fecha_entrega' => null,
            ]);
            
            \Log::info('[DespachoController] Despacho actualizado a no entregado', [
                'despacho_id' => $despacho->id,
                'entregado_nuevo' => $despacho->entregado,
                'fecha_entrega_nueva' => $despacho->fecha_entrega,
            ]);

            // Verificar si el estado del pedido debe cambiar de "Entregado" a "Pendiente"
            $this->verificarYActualizarEstadoPedido($pedido);

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
     * Vista de pedidos entregados
     */
    public function entregados(Request $request)
    {
        $search = $request->query('search', '');
        
        return view('despacho.entregados', [
            'search' => $search
        ]);
    }

    /**
     * API para obtener pendientes de costura
     */
    public function obtenerPendientesCostura(Request $request)
    {
        try {
            $search = $request->query('search', '');
            
            // Pedidos pendientes de costura (solo los que tienen registros en bodega_detalles_talla)
            $query = PedidoProduccion::query()
                ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->whereNotNull('pedidos_produccion.numero_pedido')
                ->where('pedidos_produccion.numero_pedido', '!=', '')
                ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'Entregado'])
                ->where('bodega_detalles_talla.area', 'Costura')
                ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
                ->select('pedidos_produccion.*') // Evitar columnas duplicadas
                ->distinct(); // Evitar duplicados por múltiples registros en bodega_detalles_talla
            
            // Debug: Verificar la consulta SQL
            \Log::info('[DEBUG] Costura SQL Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // Debug: Verificar si hay registros en bodega_detalles_talla
            $bodegaCount = \DB::table('bodega_detalles_talla')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->count();
            
            \Log::info('[DEBUG] Bodega detalles costura count:', [
                'count' => $bodegaCount
            ]);
            
            // Debug: Verificar todos los valores únicos en bodega_detalles_talla
            $areas = \DB::table('bodega_detalles_talla')->distinct()->pluck('area');
            $estados = \DB::table('bodega_detalles_talla')->distinct()->pluck('estado_bodega');
            $totalRegistros = \DB::table('bodega_detalles_talla')->count();
            
            \Log::info('[DEBUG] Bodega detalles análisis:', [
                'total_registros' => $totalRegistros,
                'areas_disponibles' => $areas->toArray(),
                'estados_disponibles' => $estados->toArray()
            ]);
            
            // Debug: Verificar los pedidos relacionados con los registros de bodega
            $bodegaPedidosIds = \DB::table('bodega_detalles_talla')
                ->where('area', 'Costura')
                ->where('estado_bodega', 'Pendiente')
                ->pluck('pedido_produccion_id');
            
            \Log::info('[DEBUG] Pedidos IDs de bodega (Costura):', [
                'pedido_ids' => $bodegaPedidosIds->toArray()
            ]);
            
            // Debug: Verificar si esos pedidos existen en pedidos_produccion
            $pedidosRelacionados = \DB::table('pedidos_produccion')
                ->whereIn('id', $bodegaPedidosIds)
                ->get(['id', 'numero_pedido', 'estado', 'deleted_at']);
            
            \Log::info('[DEBUG] Pedidos relacionados en pedidos_produccion:', [
                'pedidos' => $pedidosRelacionados->toArray()
            ]);
            
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

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

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

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

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

        // Broadcast event
        broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

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
            
            // Pedidos pendientes de EPP (solo los que tienen registros en bodega_detalles_talla)
            $query = PedidoProduccion::query()
                ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->whereNotNull('pedidos_produccion.numero_pedido')
                ->where('pedidos_produccion.numero_pedido', '!=', '')
                ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'Entregado'])
                ->where('bodega_detalles_talla.area', 'EPP')
                ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
                ->select('pedidos_produccion.*') // Evitar columnas duplicadas
                ->distinct(); // Evitar duplicados por múltiples registros en bodega_detalles_talla
            
            // Debug: Verificar la consulta SQL
            \Log::info('[DEBUG] EPP SQL Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            
            // Debug: Verificar si hay registros en bodega_detalles_talla
            $bodegaCount = \DB::table('bodega_detalles_talla')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->count();
            
            \Log::info('[DEBUG] Bodega detalles EPP count:', [
                'count' => $bodegaCount
            ]);
            
            // Debug: Verificar los pedidos relacionados con los registros de bodega
            $bodegaPedidosIds = \DB::table('bodega_detalles_talla')
                ->where('area', 'EPP')
                ->where('estado_bodega', 'Pendiente')
                ->pluck('pedido_produccion_id');
            
            \Log::info('[DEBUG] Pedidos IDs de bodega (EPP):', [
                'pedido_ids' => $bodegaPedidosIds->toArray()
            ]);
            
            // Debug: Verificar si esos pedidos existen en pedidos_produccion
            $pedidosRelacionados = \DB::table('pedidos_produccion')
                ->whereIn('id', $bodegaPedidosIds)
                ->get(['id', 'numero_pedido', 'estado', 'deleted_at']);
            
            \Log::info('[DEBUG] Pedidos relacionados en pedidos_produccion (EPP):', [
                'pedidos' => $pedidosRelacionados->toArray()
            ]);
            
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
            \Log::info('[DEBUG] obtenerPendientesUnificados llamado - INICIO ABSOLUTO');
            
            $search = $request->query('search', '');
            $tipo = $request->query('tipo', 'todos');
            $filter = $request->query('filter', '');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 10);
            
            \Log::info('[DEBUG] obtenerPendientesUnificados iniciado', [
                'search' => $search,
                'tipo' => $tipo,
                'filter' => $filter,
                'page' => $page,
                'per_page' => $perPage
            ]);
            
            $pendientes = collect();
            $pedidosProcesados = [];
            
            // Obtener pendientes de costura
            if ($tipo === 'todos' || $tipo === 'costura') {
                \Log::info('[DEBUG] Obteniendo pendientes de costura');
                try {
                    $costuraResponse = $this->obtenerPendientesCostura($request);
                    $costuraData = $costuraResponse->getData();
                    \Log::info('[DEBUG] Respuesta costura:', [
                        'success' => $costuraData->success ?? 'NO_DATA',
                        'data_count' => $costuraData->data ? count($costuraData->data) : 0
                    ]);
                    
                    if ($costuraData->success) {
                        $costuraPedidos = collect($costuraData->data);
                        
                        // Aplicar filtros si existen
                        if ($filter) {
                            $costuraPedidos = $this->aplicarFiltros($costuraPedidos, $filter);
                            \Log::info('[DEBUG] Costura después de filtros:', [
                                'total_antes' => count($costuraData->data),
                                'total_despues' => $costuraPedidos->count()
                            ]);
                        }
                        
                        foreach ($costuraPedidos as $pedido) {
                            $pedidoId = $pedido->id;
                            if (!isset($pedidosProcesados[$pedidoId])) {
                                $pendientes->push($pedido);
                                $pedidosProcesados[$pedidoId] = true;
                            }
                        }
                        \Log::info('[DEBUG] Pendientes costura agregados, total: ' . $pendientes->count());
                    }
                } catch (\Exception $e) {
                    \Log::error('[ERROR] Error obteniendo pendientes de costura:', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                }
            }
            
            // Obtener pendientes de EPP (solo los que no están ya en costura)
            if ($tipo === 'todos' || $tipo === 'epp') {
                \Log::info('[DEBUG] Obteniendo pendientes de EPP');
                try {
                    $eppResponse = $this->obtenerPendientesEpp($request);
                    $eppData = $eppResponse->getData();
                    \Log::info('[DEBUG] Respuesta EPP:', [
                        'success' => $eppData->success ?? 'NO_DATA',
                        'data_count' => $eppData->data ? count($eppData->data) : 0
                    ]);
                    
                    if ($eppData->success) {
                        $eppPedidos = collect($eppData->data);
                        
                        // Aplicar filtros si existen
                        if ($filter) {
                            $eppPedidos = $this->aplicarFiltros($eppPedidos, $filter);
                            \Log::info('[DEBUG] EPP después de filtros:', [
                                'total_antes' => count($eppData->data),
                                'total_despues' => $eppPedidos->count()
                            ]);
                        }
                        
                        foreach ($eppPedidos as $pedido) {
                            $pedidoId = $pedido->id;
                            if (!isset($pedidosProcesados[$pedidoId])) {
                                $pendientes->push($pedido);
                                $pedidosProcesados[$pedidoId] = true;
                            }
                        }
                        \Log::info('[DEBUG] Pendientes EPP agregados, total: ' . $pendientes->count());
                    }
                } catch (\Exception $e) {
                    \Log::error('[ERROR] Error obteniendo pendientes de EPP:', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                }
            }
            
            // Aplicar filtros finales si existen
            if ($filter && $pendientes->count() > 0) {
                $pendientes = $this->aplicarFiltros($pendientes, $filter);
                \Log::info('[DEBUG] Pendientes finales después de filtros globales:', [
                    'total_antes' => $pendientes->count(),
                    'total_despues' => $pendientes->count()
                ]);
            }
            
            // Ordenar por fecha de creación
            $pendientes = $pendientes->sortByDesc('fecha_creacion')->values();
            
            // Aplicar paginación
            $total = $pendientes->count();
            $offset = ($page - 1) * $perPage;
            $paginated = $pendientes->slice($offset, $perPage);
            
            \Log::info('[DEBUG] Pendientes finales:', [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'paginated_count' => $paginated->count(),
                'costura_count' => $paginated->where('tipo', 'costura')->count(),
                'epp_count' => $paginated->where('tipo', 'epp')->count()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $paginated,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $total > 0 ? $offset + 1 : null,
                    'to' => min($offset + $perPage, $total),
                    'has_more' => $page < ceil($total / $perPage)
                ],
                'costura_count' => $paginated->where('tipo', 'costura')->count(),
                'epp_count' => $paginated->where('tipo', 'epp')->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('[ERROR] Error en obtenerPendientesUnificados:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes unificados: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Aplicar filtros a una colección de pedidos
     */
    private function aplicarFiltros($pedidos, $filterString)
    {
        try {
            \Log::info('[DEBUG] Aplicando filtros:', [
                'filter_string' => $filterString,
                'pedidos_count' => $pedidos->count()
            ]);
            
            if (empty($filterString)) {
                return $pedidos;
            }
            
            $filtros = explode(',', $filterString);
            $pedidosFiltrados = $pedidos;
            
            foreach ($filtros as $filtroItem) {
                $filtroItem = trim($filtroItem);
                
                \Log::info('[DEBUG] Procesando filtro:', [
                    'filtro_item' => $filtroItem,
                    'es_numerico' => is_numeric($filtroItem)
                ]);
                
                // Filtro por número de pedido
                if (is_numeric($filtroItem)) {
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($filtroItem) {
                        return $pedido->numero_pedido == $filtroItem;
                    });
                }
                
                // Filtro por cliente (texto)
                elseif (!is_numeric($filtroItem)) {
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($filtroItem) {
                        return stripos($pedido->cliente, $filtroItem) !== false;
                    });
                }
                
                // Filtro por estado
                $estadosMap = [
                    'Pendiente' => 'Pendiente',
                    'PENDIENTE_INSUMOS' => 'PENDIENTE_INSUMOS',
                    'No iniciado' => 'No iniciado',
                    'En Ejecución' => 'En Ejecución',
                    'Anulada' => 'Anulada',
                    'PENDIENTE_SUPERVISOR' => 'PENDIENTE_SUPERVISOR',
                    'DEVUELTO_A_ASESORA' => 'DEVUELTO_A_ASESORA'
                ];
                
                if (isset($estadosMap[$filtroItem])) {
                    $estadoBusqueda = $estadosMap[$filtroItem];
                    $pedidosFiltrados = $pedidosFiltrados->filter(function ($pedido) use ($estadoBusqueda) {
                        return $pedido->estado === $estadoBusqueda;
                    });
                }
            }
            
            \Log::info('[DEBUG] Resultado de filtros:', [
                'total_original' => $pedidos->count(),
                'total_filtrados' => $pedidosFiltrados->count()
            ]);
            
            return $pedidosFiltrados;
            
        } catch (\Exception $e) {
            \Log::error('[ERROR] Error aplicando filtros:', [
                'error' => $e->getMessage(),
                'filter_string' => $filterString,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $pedidos;
        }
    }

    /**
     * API para obtener todos los pedidos con estados solicitados (excluyendo completamente entregados en bodega)
     */
    public function obtenerTodosLosPedidos(Request $request)
    {
        // ...
    }

    /**
     * API para obtener pedidos entregados
     */
    public function obtenerEntregados(Request $request)
    {
        try {
            $search = $request->query('search', '');
            
            // Obtener pedidos con estado "Entregado"
            $query = PedidoProduccion::query()
                ->where('estado', 'Entregado')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '!=', '')
                ->orderByDesc('updated_at'); // Ordenar por fecha de actualización (cuando se marcaron como entregados)
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('numero_pedido', 'like', "%{$search}%")
                      ->orWhere('cliente', 'like', "%{$search}%");
                });
            }
            
            $pedidos = $query->get();
            
            // Agregar información adicional a cada pedido
            $pedidos->transform(function ($pedido) {
                $pedido->fecha_entrega = $pedido->updated_at ? $pedido->updated_at->format('d/m/Y H:i') : '—';
                $pedido->fecha_creacion = $pedido->created_at ? $pedido->created_at->format('d/m/Y') : '—';
                return $pedido;
            });
            
            return response()->json([
                'success' => true,
                'data' => $pedidos->map(function ($pedido) {
                    return [
                        'id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'fecha_entrega' => $pedido->fecha_entrega,
                        'fecha_creacion' => $pedido->fecha_creacion
                    ];
                }),
                'total' => $pedidos->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos entregados: ' . $e->getMessage()
            ]);
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
            
            // Verificar que sea un pedido con estado permitido
            $estadosPermitidos = ['Pendiente', 'Entregado', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA'];
            if (!in_array($datos['pedido']['estado'] ?? '', $estadosPermitidos)) {
                return redirect()->route('despacho.pendientes')
                    ->with('error', 'Este pedido no tiene un estado válido para despacho');
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

    /**
     * Verificar si todos los ítems de un pedido están entregados y actualizar el estado del pedido
     */
    private function verificarYActualizarEstadoPedido(PedidoProduccion $pedido)
    {
        try {
            // Obtener todos los ítems del pedido (prendas y EPP)
            $itemsPendientes = collect();
            
            // Obtener prendas
            $prendas = $pedido->prendas()->with(['tallas'])->get();
            foreach ($prendas as $prenda) {
                foreach ($prenda->tallas as $talla) {
                    $itemsPendientes->push([
                        'tipo' => 'prenda',
                        'item_id' => $talla->id,
                        'talla_id' => $talla->talla_id,
                    ]);
                }
            }
            
            // Obtener EPPs
            $epps = $pedido->epps()->get();
            foreach ($epps as $epp) {
                $itemsPendientes->push([
                    'tipo' => 'epp',
                    'item_id' => $epp->id,
                    'talla_id' => null,
                ]);
            }
            
            // Verificar cuántos ítems están entregados
            $itemsEntregados = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('entregado', true)
                ->count();
            
            $totalItems = $itemsPendientes->count();
            $itemsRestantes = $totalItems - $itemsEntregados;
            
            \Log::info('[DespachoController] Verificación de estado del pedido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'total_items' => $totalItems,
                'items_entregados' => $itemsEntregados,
                'items_restantes' => $itemsRestantes,
            ]);
            
            // Si todos los ítems están entregados, actualizar el estado del pedido
            if ($itemsRestantes === 0 && $totalItems > 0) {
                $estadoAnterior = $pedido->estado;
                
                $pedido->update([
                    'estado' => 'Entregado',
                    'updated_at' => now(),
                ]);
                
                // Disparar evento WebSocket para notificar en tiempo real
                event(new DespachoPedidoActualizado($pedido, [
                    'accion' => 'estado_cambiado',
                    'nuevo_estado' => 'Entregado',
                    'anterior_estado' => $estadoAnterior,
                    'mensaje' => 'Pedido marcado como entregado'
                ]));
                
                \Log::info('[Despacho] Pedido marcado como Entregado y evento WebSocket despacho disparado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'Entregado',
                ]);
            }
            
            // Si el pedido estaba en "Entregado" pero ya no todos los ítems están entregados, volver a "Pendiente"
            elseif ($pedido->estado === 'Entregado' && $itemsRestantes > 0) {
                $estadoAnterior = $pedido->estado;
                
                $pedido->update([
                    'estado' => 'Pendiente',
                    'updated_at' => now(),
                ]);
                
                // Disparar evento WebSocket para notificar en tiempo real
                event(new DespachoPedidoActualizado($pedido, [
                    'accion' => 'estado_cambiado',
                    'nuevo_estado' => 'Pendiente',
                    'anterior_estado' => $estadoAnterior,
                    'mensaje' => 'Pedido vuelto a pendiente'
                ]));
                
                \Log::info('[Despacho] Pedido vuelto a Pendiente y evento WebSocket despacho disparado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => 'Pendiente',
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('[DespachoController] Error verificando estado del pedido', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
