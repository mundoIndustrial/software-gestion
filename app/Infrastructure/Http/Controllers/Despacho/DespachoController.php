<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Events\DespachoPedidoActualizado;
use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PedidoObservacionesDespacho;
use App\Models\BodegaNota;
use App\Models\DesparChoParcialesModel;
use App\Models\Role;
use App\Models\ReciboPrenda;
use App\Application\Pedidos\Despacho\UseCases\ObtenerFilasDespachoUseCase;
use App\Application\Pedidos\Despacho\UseCases\GuardarDespachoUseCase;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Bodega\Services\BodegaNotaService;
use App\Domain\Pedidos\Despacho\Services\DespachoEstadoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\News;
use App\Models\NewsVisto;
use App\Models\PedidoVistoSupervisor;
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
        private BodegaNotaService $bodegaNotaService,
        private DespachoEstadoService $despachoEstadoService,
    ) {}

    public function obtenerNotasBodega(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
            ]);

            return $this->bodegaNotaService->obtenerNotas($validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    public function guardarNotaBodega(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->bodegaNotaService->guardarNota($validated, $request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarNotaBodega(Request $request, int $notaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->bodegaNotaService->actualizarNota($notaId, $validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    public function eliminarNotaBodega(Request $request, int $notaId): JsonResponse
    {
        try {
            return $this->bodegaNotaService->eliminarNota($notaId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }

    /**
     * Listar pedidos disponibles para despacho
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        
        $query = PedidoProduccion::query()
            ->whereIn('estado', ['Pendiente', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
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
            
            // Obtener fecha de entrega desde prenda_entregas (para vista principal)
            $fechaEntrega = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedido->id)
                ->whereHas('entrega', function ($q) {
                    $q->where('entregado', true)
                      ->whereNotNull('fecha_entrega');
                })
                ->with(['entrega' => function ($q) {
                    $q->where('entregado', true)
                      ->whereNotNull('fecha_entrega')
                      ->orderBy('fecha_entrega', 'desc');
                }])
                ->first();
                
            $pedido->fecha_entrega_prendas = $fechaEntrega && $fechaEntrega->entrega 
                ? $fechaEntrega->entrega->fecha_entrega->format('d/m/Y h:i A') 
                : '—';
            
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
        
        // Cargar datos de despacho parciales con fechas de entrega
        $despachos = DesparChoParcialesModel::where('pedido_id', $pedido->id)
            ->whereNotNull('fecha_entrega')
            ->get()
            ->keyBy(function($item) {
                return ($item->tipo_item === 'epp' ? 'epp-' : '') . $item->item_id
                       . ($item->talla_id ? '-' . $item->talla_id : '')
                       . ($item->talla_color_id ? '-' . $item->talla_color_id : '');
            });
        
        \Log::info('[DespachoController] Datos de despacho cargados', [
            'pedido_id' => $pedido->id,
            'despachos_count' => $despachos->count(),
            'despachos_data' => $despachos->toArray(),
        ]);

        [$pendientesBodegueroText, $observacionesAsesoraText] = $this->buildTextosPendientesYAsesora($pedido);
        
        return view('despacho.show', compact('pedido', 'prendas', 'epps', 'despachos', 'pendientesBodegueroText', 'observacionesAsesoraText'));
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
     * API para obtener pedidos con prendas que se sacan de bodega y NO tienen ningún proceso
     */
    public function obtenerPendientesBodegaSinProcesos(Request $request)
    {
        try {
            $search = $request->query('search', '');

            $query = PedidoProduccion::query()
                ->join('prendas_pedido', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
                ->whereNotNull('pedidos_produccion.numero_pedido')
                ->where('pedidos_produccion.numero_pedido', '!=', '')
                ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
                ->where('prendas_pedido.de_bodega', 1)
                ->whereNull('prendas_pedido.deleted_at')
                ->whereNull('pedidos_procesos_prenda_detalles.id') // NO debe tener procesos
                // Solo listar si existe al menos una talla marcada como Pendiente en bodega_detalles_talla
                // Y que esté vinculada a una prenda con de_bodega = 1 SIN procesos
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('bodega_detalles_talla as bdt')
                        ->join('prendas_pedido as pp', function($join) {
                            $join->on('pp.id', '=', 'bdt.prenda_id')
                                 ->where('pp.de_bodega', '=', 1)
                                 ->whereNull('pp.deleted_at');
                        })
                        ->leftJoin('pedidos_procesos_prenda_detalles as pppd', 'pppd.prenda_pedido_id', '=', 'pp.id')
                        ->whereColumn('bdt.pedido_produccion_id', 'pedidos_produccion.id')
                        ->where('bdt.estado_bodega', 'Pendiente')
                        ->whereNull('pppd.id'); // La prenda NO debe tener procesos
                })
                ->select('pedidos_produccion.*')
                ->distinct();

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
                })->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos de bodega sin procesos: ' . $e->getMessage()
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

        [$pendientesBodegueroText, $observacionesAsesoraText] = $this->buildTextosPendientesYAsesora($pedido);
        
        return view('despacho.print', compact('pedido', 'prendas', 'epps', 'pendientesBodegueroText', 'observacionesAsesoraText'));
    }

    private function buildTextosPendientesYAsesora(PedidoProduccion $pedido): array
    {
        $rows = PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $bodegaRows = BodegaNota::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $observacionesAsesora = $rows
            ->filter(function ($row) {
                $rol = strtolower((string) ($row->usuario_rol ?? ''));
                return str_contains($rol, 'asesor');
            })
            ->values();

        $pendientesBodegueroText = $bodegaRows->count() === 0
            ? '— Sin observaciones'
            : $bodegaRows->map(function ($row) {
                $fechaISO = $row->updated_at ?: $row->created_at;
                $fecha = $fechaISO ? \Carbon\Carbon::parse($fechaISO)->format('d/m/Y H:i') : '';
                $contenido = (string) ($row->contenido ?? '');
                return $contenido . ($fecha ? (' - ' . $fecha) : '');
            })->implode("\n");

        $observacionesAsesoraText = $observacionesAsesora->count() === 0
            ? '— Sin observaciones'
            : $observacionesAsesora->map(function ($row) {
                $fechaISO = $row->updated_at ?: $row->created_at;
                $fecha = $fechaISO ? \Carbon\Carbon::parse($fechaISO)->format('d/m/Y H:i') : '';
                $contenido = (string) ($row->contenido ?? '');
                return $contenido . ($fecha ? (' - ' . $fecha) : '');
            })->implode("\n");

        return [$pendientesBodegueroText, $observacionesAsesoraText];
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
                        'talla_color_id' => $despacho->talla_color_id,
                        'genero' => $despacho->genero,
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
                'talla_color_id' => 'nullable|integer',
            ]);

            $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('tipo_item', $validated['tipo_item'])
                ->where('item_id', $validated['item_id'])
                ->when($validated['talla_id'], function ($q) use ($validated) {
                    $q->where('talla_id', $validated['talla_id']);
                })
                ->when($validated['talla_color_id'], function ($q) use ($validated) {
                    $q->where('talla_color_id', $validated['talla_color_id']);
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
                    'talla_color_id' => $validated['talla_color_id'] ?? null,
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
                'fecha_entrega' => $despacho->fresh()->fecha_entrega?->format('Y-m-d'),
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
                        'talla_color_id' => $entrega->talla_color_id,
                        'entregado' => true,
                        'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d'),
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
                'talla_color_id' => 'nullable|integer',
            ]);

            $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                ->where('tipo_item', $validated['tipo_item'])
                ->where('item_id', $validated['item_id'])
                ->when($validated['talla_id'], function ($q) use ($validated) {
                    $q->where('talla_id', $validated['talla_id']);
                })
                ->when($validated['talla_color_id'], function ($q) use ($validated) {
                    $q->where('talla_color_id', $validated['talla_color_id']);
                })
                ->first();
            
            \Log::info('[DespachoController] Búsqueda para deshacer', [
                'pedido_id' => $pedido->id,
                'tipo_item' => $validated['tipo_item'],
                'item_id' => $validated['item_id'],
                'talla_id' => $validated['talla_id'],
                'talla_color_id' => $validated['talla_color_id'],
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
                    'talla_color_id' => $validated['talla_color_id'],
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

    /**
     * Marcar todos los ítems de un pedido como entregados
     */
    public function entregarTodo(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        \Log::info('[DespachoController] entregarTodo llamado', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
        
        try {
            DB::beginTransaction();
            
            // Obtener todas las filas de despacho para este pedido
            $filas = $this->obtenerFilas->obtenerTodas($pedido->id);
            
            $itemsProcesados = 0;
            $itemsCreados = 0;
            
            // Procesar cada ítem (prendas y EPP)
            foreach ($filas as $fila) {
                $tipoItem = $fila->tipo; // 'prenda' o 'epp'
                $itemId = $fila->id;
                $tallaId = $fila->tallaId; // Usar tallaId del DTO
                $tallaColorId = $fila->talla_color_id ?? null;
                $genero = $fila->genero ?? null;
                
                // Para prendas, el item_id debe ser el ID de la prenda, pero talla_id es el ID de la talla
                if ($tipoItem === 'prenda' && $tallaId) {
                    // Buscar registro específico de esta talla
                    $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                        ->where('tipo_item', $tipoItem)
                        ->where('item_id', $itemId)
                        ->where('talla_id', $tallaId)
                        ->first();
                } else {
                    // Para EPP o prendas sin talla específica
                    $despacho = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                        ->where('tipo_item', $tipoItem)
                        ->where('item_id', $itemId)
                        ->when($tallaId, function ($q) use ($tallaId) {
                            $q->where('talla_id', $tallaId);
                        })
                        ->when($tallaColorId, function ($q) use ($tallaColorId) {
                            $q->where('talla_color_id', $tallaColorId);
                        })
                        ->first();
                }
                
                if (!$despacho) {
                    // Crear nuevo registro de despacho
                    $despacho = DesparChoParcialesModel::create([
                        'pedido_id' => $pedido->id,
                        'tipo_item' => $tipoItem,
                        'item_id' => $itemId,
                        'talla_id' => $tallaId,
                        'talla_color_id' => $tallaColorId,
                        'genero' => $genero,
                        'entregado' => false,
                        'fecha_despacho' => now(),
                        'usuario_id' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $itemsCreados++;
                }
                
                // Marcar como entregado
                $despacho->update([
                    'entregado' => true,
                    'fecha_entrega' => now(),
                    'updated_at' => now(),
                ]);
                $itemsProcesados++;
            }
            
            // Cambiar el estado del pedido a "Entregado"
            $estadoAnterior = $pedido->estado;
            $pedido->update([
                'estado' => 'Entregado',
                'updated_at' => now(),
            ]);
            
            DB::commit();
            
            \Log::info('[DespachoController] Pedido marcado como entregado completamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado_anterior' => $estadoAnterior,
                'items_procesados' => $itemsProcesados,
                'items_creados' => $itemsCreados,
            ]);
            
            // Emitir evento para WebSocket
            event(new DespachoPedidoActualizado($pedido, [
                'action' => 'pedido_entregado_completo',
                'numero_pedido' => $pedido->numero_pedido,
                'nuevo_estado' => 'Entregado',
                'anterior_estado' => $estadoAnterior,
                'items_procesados' => $itemsProcesados,
                'usuario' => auth()->user()->name,
                'timestamp' => now()->toIso8601String(),
            ]));
            
            return response()->json([
                'success' => true,
                'message' => "Pedido #{$pedido->numero_pedido} marcado como entregado completamente ({$itemsProcesados} ítems procesados)",
                'items_procesados' => $itemsProcesados,
                'items_creados' => $itemsCreados,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => 'Entregado',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al marcar pedido como entregado completamente', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar: ' . $e->getMessage(),
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
            // IMPORTANTE: Solo incluir si los registros pendientes están vinculados a prendas con de_bodega = true
            // Y que esas prendas NO tengan procesos en pedidos_procesos_prenda_detalles
            // Si los registros pendientes son de prendas con de_bodega = false, no debe aparecer (son solo producción)
            $query = PedidoProduccion::query()
                ->join('bodega_detalles_talla', 'bodega_detalles_talla.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->join('prendas_pedido', function($join) {
                    $join->on('prendas_pedido.id', '=', 'bodega_detalles_talla.prenda_id')
                         ->where('prendas_pedido.de_bodega', '=', 1)
                         ->whereNull('prendas_pedido.deleted_at');
                })
                ->leftJoin('pedidos_procesos_prenda_detalles', 'pedidos_procesos_prenda_detalles.prenda_pedido_id', '=', 'prendas_pedido.id')
                ->whereNotNull('pedidos_produccion.numero_pedido')
                ->where('pedidos_produccion.numero_pedido', '!=', '')
                ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'Entregado', 'Anulada', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
                ->where('bodega_detalles_talla.area', 'Costura')
                ->where('bodega_detalles_talla.estado_bodega', 'Pendiente')
                ->whereNull('pedidos_procesos_prenda_detalles.id') // NO debe tener procesos
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

        // DESPACHO: El badge debe mostrar SIEMPRE el total de items que se visualizan en el modal
        // (observaciones despacho + notas de bodega), sin usar visto_at/estado.
        $resumenDespacho = PedidoObservacionesDespacho::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        $resumenBodega = BodegaNota::query()
            ->whereIn('pedido_produccion_id', $pedidoIds)
            ->selectRaw('pedido_produccion_id, COUNT(*) as total')
            ->groupBy('pedido_produccion_id')
            ->pluck('total', 'pedido_produccion_id')
            ->toArray();

        // Construir respuesta con todos los pedidos
        $resultado = [];
        foreach ($pedidoIds as $pedidoId) {
            $total = (int) ($resumenDespacho[$pedidoId] ?? 0) + (int) ($resumenBodega[$pedidoId] ?? 0);
            $resultado[$pedidoId] = [
                // Mantener nombre 'unread' por compatibilidad con JS actual
                'unread' => $total,
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

        // Marcar como leídas (visto_at = now()) las observaciones no leídas de otros usuarios
        PedidoObservacionesDespacho::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->whereNull('visto_at')
            ->where(function ($q) use ($usuarioId) {
                $q->whereNull('usuario_id')
                  ->orWhere('usuario_id', '!=', $usuarioId);
            })
            ->update(['visto_at' => now()]);

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

        $bodegaRows = BodegaNota::query()
            ->where('pedido_produccion_id', $pedido->id)
            ->orderByDesc('created_at')
            ->get();

        $observacionesDespacho = $rows->map(function ($row) {
            return [
                'source' => 'despacho',
                'id' => (string) $row->uuid,
                'contenido' => $row->contenido,
                'talla' => null,
                'usuario_id' => $row->usuario_id,
                'usuario_nombre' => $row->usuario_nombre,
                'usuario_rol' => $row->usuario_rol,
                'ip_address' => $row->ip_address,
                'estado' => (int) $row->estado,
                'created_at' => optional($row->created_at)->toISOString(),
                'updated_at' => optional($row->updated_at)->toISOString(),
            ];
        });

        $observacionesBodega = $bodegaRows->map(function ($row) {
            return [
                'source' => 'bodega',
                'id' => 'bodega-' . (string) $row->id,
                'contenido' => $row->contenido,
                'talla' => $row->talla,
                'usuario_id' => $row->usuario_id,
                'usuario_nombre' => $row->usuario_nombre,
                'usuario_rol' => $row->usuario_rol,
                'ip_address' => $row->ip_address,
                'estado' => null,
                'created_at' => optional($row->created_at)->toISOString(),
                'updated_at' => optional($row->updated_at)->toISOString(),
            ];
        });

        $unificado = $observacionesDespacho
            ->concat($observacionesBodega)
            ->sortByDesc(function ($item) {
                return $item['updated_at'] ?: $item['created_at'] ?: '';
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $unificado,
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
                ->whereIn('pedidos_produccion.estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS', 'PENDIENTE_SUPERVISOR', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'])
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
                })->toArray()
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
            
            // Obtener pedidos con prendas de bodega sin procesos
            if ($tipo === 'todos' || $tipo === 'costura') {
                \Log::info('[DEBUG] Obteniendo prendas de bodega sin procesos');
                try {
                    $bodegaResponse = $this->obtenerPendientesBodegaSinProcesos($request);
                    $bodegaData = $bodegaResponse->getData();
                    \Log::info('[DEBUG] Respuesta bodega sin procesos:', [
                        'success' => $bodegaData->success ?? 'NO_DATA',
                        'data_count' => $bodegaData->data ? count($bodegaData->data) : 0
                    ]);

                    if ($bodegaData->success) {
                        $bodegaPedidos = collect($bodegaData->data);

                        // Aplicar filtros si existen
                        if ($filter) {
                            $bodegaPedidos = $this->aplicarFiltros($bodegaPedidos, $filter);
                            \Log::info('[DEBUG] Bodega sin procesos después de filtros:', [
                                'total_antes' => count($bodegaData->data),
                                'total_despues' => $bodegaPedidos->count()
                            ]);
                        }

                        foreach ($bodegaPedidos as $pedido) {
                            $pedidoId = $pedido->id;
                            if (!isset($pedidosProcesados[$pedidoId])) {
                                $pendientes->push($pedido);
                                $pedidosProcesados[$pedidoId] = true;
                            }
                        }
                        \Log::info('[DEBUG] Pedidos bodega sin procesos agregados, total: ' . $pendientes->count());
                    }
                } catch (\Exception $e) {
                    \Log::error('[ERROR] Error obteniendo pedidos bodega sin procesos:', [
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
            
            // Ordenar por número de pedido descendente
            $pendientes = $pendientes->sortByDesc('numero_pedido')->values();
            
            \Log::info('[DEBUG] Pendientes antes de paginación - Números de pedido:', [
                'total_pendientes' => $pendientes->count(),
                'numeros_pedido' => $pendientes->pluck('numero_pedido')->toArray()
            ]);
            
            // Aplicar paginación
            $total = $pendientes->count();
            $offset = ($page - 1) * $perPage;
            $paginated = $pendientes->slice($offset, $perPage)->values(); // ← AGREGADO .values()
            
            \Log::info('[DEBUG] Pendientes finales:', [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'offset' => $offset,
                'paginated_count' => $paginated->count(),
                'costura_count' => $paginated->where('tipo', 'costura')->count(),
                'epp_count' => $paginated->where('tipo', 'epp')->count(),
                'paginated_numeros' => $paginated->pluck('numero_pedido')->toArray()
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
                // Obtener fecha de entrega desde despacho_parciales (para vista entregados)
                $fechaEntrega = DesparChoParcialesModel::where('pedido_id', $pedido->id)
                    ->where('entregado', true)
                    ->whereNotNull('fecha_entrega')
                    ->orderBy('fecha_entrega', 'desc')
                    ->first();
                    
                $pedido->fecha_entrega = $fechaEntrega ? $fechaEntrega->fecha_entrega->format('d/m/Y h:i A') : '—';
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
            // Obtener el recibo base para obtener numero_pedido
            $reciboPrenda = \App\Models\ReciboPrenda::findOrFail($id);
            $numeroPedido = $reciboPrenda->numero_pedido;
            
            // Obtener el pedido info
            $pedidoProduccion = \App\Models\PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$pedidoProduccion) {
                return redirect()->route('despacho.pendientes')
                    ->with('error', 'Pedido de producción no encontrado');
            }
            
            // Obtener datos del pedido
            $pedidoData = [
                'id' => $reciboPrenda->id,
                'numero_pedido' => $numeroPedido,
                'estado' => $pedidoProduccion->estado ?? 'Pendiente',
                'cliente' => $reciboPrenda->cliente ?? 'No especificado',
                'asesor' => $reciboPrenda->asesor?->nombre ?? $reciboPrenda->asesor?->name ?? null,
                'fecha_de_creacion_de_orden' => $pedidoProduccion->fecha_de_creacion_de_orden,
            ];
            
            // Usar el servicio para obtener todos los detalles del pedido con información completa
            $datosCompletos = $this->bodegaPedidoService->obtenerDetallePedido($pedidoProduccion->id);
            
            // Filtrar solo:
            // - EPP pendientes (area=EPP, estado_bodega=Pendiente)
            // - Prendas que se sacan de bodega y NO tienen procesos
            $itemsPendientes = [];
            if (isset($datosCompletos['items']) && is_array($datosCompletos['items'])) {
                foreach ($datosCompletos['items'] as $item) {
                    $tipo = $item['tipo'] ?? null;
                    $area = $item['area'] ?? null;
                    $estadoBodega = $item['estado_bodega'] ?? null;
                    $deBodega = (bool) ($item['de_bodega'] ?? ($item['descripcion']['de_bodega'] ?? ($item['objetoPrenda']['de_bodega'] ?? false)));
                    $procesos = $item['descripcion']['procesos'] ?? [];

                    $esEppPendiente = ($tipo === 'epp') && ($area === 'EPP') && ($estadoBodega === 'Pendiente');
                    // IMPORTANTE:
                    // Para prendas "de bodega" sin procesos, el item viene por talla.
                    // Solo debemos mostrar las tallas marcadas como Pendiente, no todas las tallas de la prenda.
                    $esPrendaDeBodegaSinProcesos = ($tipo === 'prenda')
                        && $deBodega
                        && ($estadoBodega === 'Pendiente')
                        && (empty($procesos) || (is_array($procesos) && count($procesos) === 0));

                    if ($esEppPendiente || $esPrendaDeBodegaSinProcesos) {
                        $itemsPendientes[] = $item;
                    }
                }
            }
            
            // NO agrupar - crear un item por cada talla (como bodega)
            $items = [];
            
            foreach ($itemsPendientes as $item) {
                $prendaNombre = $item['descripcion']['nombre_prenda'] ?? $item['prenda_nombre'] ?? '';
                $colorKey = $item['descripcion']['color'] ?? ($item['color'] ?? '');
                if (empty($colorKey)) {
                    $tallaColorId = $item['talla_color_id'] ?? null;
                    $variantes = $item['descripcion']['variantes'] ?? [];
                    if ($tallaColorId !== null && is_array($variantes) && !empty($variantes)) {
                        foreach ($variantes as $var) {
                            $coloresDetalle = $var['colores_detalle'] ?? null;
                            if (!is_array($coloresDetalle) || empty($coloresDetalle)) {
                                continue;
                            }
                            foreach ($coloresDetalle as $cd) {
                                $tcId = $cd['talla_color_id'] ?? ($cd['tallaColorId'] ?? null);
                                if ($tcId !== null && (string)$tcId === (string)$tallaColorId) {
                                    $colorKey = $cd['color'] ?? ($cd['color_nombre'] ?? '');
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if (!empty($colorKey) && (!isset($item['descripcion']['color']) || empty($item['descripcion']['color']))) {
                    $item['descripcion']['color'] = $colorKey;
                }
                
                $es_epp = strpos(strtoupper($prendaNombre), 'EPP') !== false;
                
                // Crear un item individual por cada talla/item
                $itemNormalizado = [
                    'numero_pedido' => $numeroPedido,
                    'asesor' => $item['asesor'] ?? $pedidoData['asesor'],
                    'empresa' => $item['empresa'] ?? $pedidoData['cliente'],
                    'prenda_nombre' => $prendaNombre,
                    'es_epp' => $es_epp,
                    'tipo' => $item['tipo'] ?? 'prenda',
                    'area' => $item['area'] ?? '',
                    'descripcion' => $item['descripcion'] ?? [
                        'nombre_prenda' => $prendaNombre,
                        'nombre' => $prendaNombre,
                        'tela' => null,
                        'color' => null,
                        'procesos' => [],
                        'variantes' => [],
                    ],
                    'genero' => $item['genero'] ?? null,
                    'talla' => $item['talla'] ?? '',
                    'talla_color_id' => $item['talla_color_id'] ?? null,
                    'cantidad' => $item['cantidad'] ?? 0,
                    'cantidad_total' => $item['cantidad'] ?? 0,
                    'pendientes' => $item['pendientes'] ?? '',
                    'estado_bodega' => $item['estado_bodega'] ?? 'Pendiente',
                    'fecha_entrega' => $item['fecha_entrega'] ?? '',
                    'observaciones' => $item['observaciones'] ?? '',
                    'observaciones_bodega' => $item['observaciones_bodega'] ?? '',
                    'pedido_produccion_id' => $item['pedido_produccion_id'] ?? $pedidoProduccion->id,
                    'recibo_prenda_id' => $item['recibo_prenda_id'] ?? $reciboPrenda->id,
                ];
                
                $items[] = $itemNormalizado;
            }
            
            // Verificar que sea un pedido con estado permitido
            $estadosPermitidos = ['Pendiente', 'Entregado', 'En Ejecución', 'No iniciado', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'DEVUELTO_A_ASESORA', 'pendiente_cartera', 'RECHAZADO_CARTERA'];
            if (!in_array($pedidoData['estado'] ?? '', $estadosPermitidos)) {
                return redirect()->route('despacho.pendientes')
                    ->with('error', 'Este pedido no tiene un estado válido para despacho');
            }
            
            return view('despacho.show-pendiente-bodega', [
                'pedido' => $pedidoData,
                'items' => $items,
            ]);
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

    /**
     * Marcar observaciones de un pedido como vistas (para badges)
     */
    public function marcarObservacionesComoVistas($pedidoId)
    {
        try {
            // Actualizar todas las observaciones no leídas del pedido
            $updated = \DB::table('pedido_observaciones_despacho')
                ->where('pedido_produccion_id', $pedidoId)
                ->whereNull('visto_at') // No leídas
                ->update(['visto_at' => now()]); // Marcar como leídas

            \Log::info('[DespachoController] Observaciones marcadas como vistas', [
                'pedido_id' => $pedidoId,
                'updated_count' => $updated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Observaciones marcadas como vistas',
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            \Log::error('[DespachoController] Error marcando observaciones como vistas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar observaciones como vistas'
            ], 500);
        }
    }

    /**
     * Obtener notificaciones para la campana de despacho
     */
    public function getNotifications(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            $pedidosVistosIds = PedidoVistoSupervisor::where('user_id', $user->id)->pluck('pedido_id')->toArray();

            $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'Anulada')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->with(['asesora:id,name'])
                ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'fecha_de_creacion_de_orden', 'estado', 'forma_de_pago'])
                ->orderBy('fecha_de_creacion_de_orden', 'desc')
                ->get();

            $notificaciones = $ordenesPendientes->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesor' => ($orden->asesora?->name) ?? 'N/A',
                    'fecha' => ($orden->fecha_de_creacion_de_orden?->format('d/m/Y H:i')) ?? '',
                    'estado' => $orden->estado,
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                ];
            });

            $totalOrdenesNoVistas = $notificaciones->where('visto', false)->count();

            $newsVistosIds = NewsVisto::where('user_id', $user->id)->pluck('news_id')->toArray();

            $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'order_status_changed'];
            $novedadesQuery = News::whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            $ordenesAnuladas = PedidoProduccion::where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('updated_at', '>=', now()->subDays(7))
                ->with(['asesora:id,name'])
                ->select(['id', 'numero_pedido', 'cliente', 'asesor_id', 'updated_at'])
                ->orderBy('updated_at', 'desc')
                ->limit(20)
                ->get();

            $novedades = $novedadesQuery->map(function($news) use ($newsVistosIds) {
                $icono = match($news->event_type) {
                    'pedido_creado', 'order_created' => 'add_shopping_cart',
                    'prenda_agregada' => 'checkroom',
                    'prenda_modificada' => 'edit',
                    'epp_agregado' => 'health_and_safety',
                    'epp_modificado' => 'edit',
                    'order_status_changed' => 'sync_alt',
                    default => 'notifications',
                };
                $color = match($news->event_type) {
                    'pedido_creado', 'order_created' => '#10b981',
                    'prenda_agregada' => '#3b82f6',
                    'prenda_modificada' => '#f59e0b',
                    'epp_agregado' => '#8b5cf6',
                    'epp_modificado' => '#f59e0b',
                    'order_status_changed' => '#6366f1',
                    default => '#6b7280',
                };
                return [
                    'id' => $news->id,
                    'tipo' => $news->event_type,
                    'descripcion' => $news->description,
                    'pedido' => $news->pedido,
                    'fecha' => $news->created_at->format('d/m/Y h:i A'),
                    'icono' => $icono,
                    'color' => $color,
                    'timestamp' => $news->created_at->toIso8601String(),
                    'visto' => in_array($news->id, $newsVistosIds),
                    'source' => 'news',
                ];
            });

            $novedadesAnuladas = $ordenesAnuladas->map(function($orden) use ($pedidosVistosIds) {
                return [
                    'id' => 'anulada_' . $orden->id,
                    'tipo' => 'pedido_anulado',
                    'descripcion' => "Orden #{$orden->numero_pedido} - {$orden->cliente} fue ANULADA",
                    'pedido' => $orden->numero_pedido,
                    'fecha' => $orden->updated_at->format('d/m/Y h:i A'),
                    'icono' => 'cancel',
                    'color' => '#ef4444',
                    'timestamp' => $orden->updated_at->toIso8601String(),
                    'visto' => in_array($orden->id, $pedidosVistosIds),
                    'source' => 'anulada',
                ];
            });

            $todasNovedades = $novedades->concat($novedadesAnuladas)->sortByDesc('timestamp')->values();
            $totalNovedadesNoVistas = $todasNovedades->where('visto', false)->count();

            return response()->json([
                'success' => true,
                'notificaciones' => $notificaciones->values(),
                'novedades' => $todasNovedades,
                'totalPendientes' => $notificaciones->count(),
                'totalOrdenesNoVistas' => $totalOrdenesNoVistas,
                'totalNovedades' => $todasNovedades->count(),
                'totalNovedadesNoVistas' => $totalNovedadesNoVistas,
                'totalGeneral' => $totalOrdenesNoVistas + $totalNovedadesNoVistas,
            ]);
        } catch (\Exception $e) {
            Log::error('Error notificaciones despacho: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function markAllNotificationsAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            $novedadesTipos = ['pedido_creado', 'order_created', 'prenda_agregada', 'prenda_modificada', 'epp_agregado', 'epp_modificado', 'order_status_changed'];
            $newsIds = News::whereIn('event_type', $novedadesTipos)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('id');
            foreach ($newsIds as $newsId) {
                NewsVisto::firstOrCreate(['news_id' => $newsId, 'user_id' => $user->id]);
            }

            $pedidoIds = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->where('estado', '!=', 'pendiente_cartera')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->pluck('id');
            foreach ($pedidoIds as $pedidoId) {
                PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
            }

            $anuladasIds = PedidoProduccion::where('estado', 'Anulada')
                ->whereNotNull('numero_pedido')
                ->where('numero_pedido', '>', 0)
                ->where('updated_at', '>=', now()->subDays(7))
                ->pluck('id');
            foreach ($anuladasIds as $anuladaId) {
                PedidoVistoSupervisor::firstOrCreate(['pedido_id' => $anuladaId, 'user_id' => $user->id]);
            }

            return response()->json(['success' => true, 'message' => 'Todas marcadas como leídas']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleNewsVisto($newsId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            $existing = NewsVisto::where('news_id', $newsId)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
                $visto = false;
            } else {
                NewsVisto::create(['news_id' => $newsId, 'user_id' => $user->id]);
                $visto = true;
            }

            return response()->json(['success' => true, 'visto' => $visto]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function togglePedidoVisto($pedidoId): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }

            $existing = PedidoVistoSupervisor::where('pedido_id', $pedidoId)->where('user_id', $user->id)->first();
            if ($existing) {
                $existing->delete();
                $visto = false;
            } else {
                PedidoVistoSupervisor::create(['pedido_id' => $pedidoId, 'user_id' => $user->id]);
                $visto = true;
            }

            return response()->json(['success' => true, 'visto' => $visto]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
