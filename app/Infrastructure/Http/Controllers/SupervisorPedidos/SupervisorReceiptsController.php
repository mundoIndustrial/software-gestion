<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\CancelSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveReceiptArrivalDateUseCase;
use App\Application\SupervisorPedidos\UseCases\GetReceiptDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveSewingReceiptColorUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\DTOs\ActivateReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\CancelReceiptRequest;
use App\Application\SupervisorPedidos\DTOs\SaveReceiptArrivalDateRequest;
use App\Application\SupervisorPedidos\DTOs\SaveSewingReceiptColorRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\ApplicationException;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use \App\Application\SupervisorPedidos\DTOs\ApproveReceiptRequest;
/**
 * SupervisorReceiptsController
 * 
 * Gestiona todas las operaciones relacionadas con recibos de costura y procesos:
 * - Activar/anular recibos de costura
 * - Guardar fecha de llegada
 * - Obtener detalles y aprobar procesos
 * - Guardar color de costura
 * - Listar pendientes de costura, control de calidad y bordado/estampado
 * 
 * Responsabilidad: Orquestar use cases de recibos y traducir HTTP <-> DTOs
 * Manejo de errores: Centralizado en ExceptionHandler (sin try-catch)
 */
class SupervisorReceiptsController extends Controller
{
    private ActivateSewingReceiptUseCase $activateSewingReceiptUseCase;
    private CancelSewingReceiptUseCase $cancelSewingReceiptUseCase;
    private SaveReceiptArrivalDateUseCase $saveReceiptArrivalDateUseCase;
    private GetReceiptDetailsUseCase $getReceiptDetailsUseCase;
    private ApproveReceiptUseCase $approveReceiptUseCase;
    private SaveSewingReceiptColorUseCase $saveSewingReceiptColorUseCase;
    private GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase;
    private GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase;
    private GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase;

    public function __construct(
        ActivateSewingReceiptUseCase $activateSewingReceiptUseCase,
        CancelSewingReceiptUseCase $cancelSewingReceiptUseCase,
        SaveReceiptArrivalDateUseCase $saveReceiptArrivalDateUseCase,
        GetReceiptDetailsUseCase $getReceiptDetailsUseCase,
        ApproveReceiptUseCase $approveReceiptUseCase,
        SaveSewingReceiptColorUseCase $saveSewingReceiptColorUseCase,
        GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase,
        GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase,
        GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase
    ) {
        $this->activateSewingReceiptUseCase = $activateSewingReceiptUseCase;
        $this->cancelSewingReceiptUseCase = $cancelSewingReceiptUseCase;
        $this->saveReceiptArrivalDateUseCase = $saveReceiptArrivalDateUseCase;
        $this->getReceiptDetailsUseCase = $getReceiptDetailsUseCase;
        $this->approveReceiptUseCase = $approveReceiptUseCase;
        $this->saveSewingReceiptColorUseCase = $saveSewingReceiptColorUseCase;
        $this->getPendingSewingReceiptsUseCase = $getPendingSewingReceiptsUseCase;
        $this->getPendingQualityControlReceiptsUseCase = $getPendingQualityControlReceiptsUseCase;
        $this->getPendingEmbroideryStampingReceiptsUseCase = $getPendingEmbroideryStampingReceiptsUseCase;
    }

    /**
     * Activar recibo de costura
     */
    public function activarReciboCostura(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        $pedido = PedidoProduccion::findOrFail($pedidoId);
        
        $prenda = PrendaPedido::where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->first();

        if (!$prenda) {
            throw new ResourceNotFoundException('Prenda', $prendaId);
        }

        $activateRequest = new ActivateReceiptRequest($pedidoId, $prendaId);
        
        $response = $this->activateSewingReceiptUseCase->execute($activateRequest);
        
        return response()->json($response->toArray());
    }

    /**
     * Anular recibo de costura
     */
    public function anularReciboCostura(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        // Crear DTO de request
        $cancelRequest = new CancelReceiptRequest(
            $pedidoId,
            $prendaId,
            "ANULADO desde supervisor"
        );
        
        $response = $this->cancelSewingReceiptUseCase->execute($cancelRequest);

        return response()->json($response->toArray());
    }

    /**
     * Guardar fecha de llegada del recibo
     */
    public function guardarFechaLlegadaRecibo($id): JsonResponse
    {
        $fechaLlegada = request()->input('fecha_llegada');
        
        if (!$fechaLlegada) {
            throw new ValidationException(
                'Fecha de llegada requerida',
                ['fecha_llegada' => 'Campo obligatorio'],
                'MISSING_ARRIVAL_DATE'
            );
        }

        $saveRequest = new SaveReceiptArrivalDateRequest((int) $id, $fechaLlegada);

        $response = $this->saveReceiptArrivalDateUseCase->execute($saveRequest);

        return response()->json($response->toArray());
    }

    /**
     * Obtener detalles de un recibo específico
     */
    public function obtenerDetallesProceso($id): JsonResponse
    {
        $response = $this->getReceiptDetailsUseCase->execute((int)$id);

        if (!$response->isSuccess()) {
            throw new ResourceNotFoundException('Recibo', (string)$id);
        }

        return response()->json([
            'success' => true,
            'data' => $response->getDetails()
        ]);
    }

    /**
     * Aprobar un recibo
     */
    public function aprobarProceso($id): JsonResponse
    {
        $approveRequest = new ApproveReceiptRequest((int)$id);
        
        $response = $this->approveReceiptUseCase->execute($approveRequest);

        if (!$response->isSuccess()) {
            throw new ApplicationException(
                $response->getMessage(),
                'approve_receipt',
                'RECEIPT_APPROVAL_FAILED'
            );
        }

        return response()->json($response->toArray());
    }

    /**
     * Guardar color de costura en recibo
     */
    public function guardarColorCostura(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_recibo' => 'required|string',
            'color' => 'required|string|max:100',
        ]);

        $colorRequest = new SaveSewingReceiptColorRequest(
            $validated['numero_recibo'],
            $validated['color']
        );

        $response = $this->saveSewingReceiptColorUseCase->execute($colorRequest);

        return response()->json($response->toArray());
    }

    /**
     * Guardar color de fila en vista de Control de Calidad
     */
    public function guardarColorControlCalidad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_recibo' => 'required|string',
            'color' => 'required|string|max:100',
        ]);

        $updated = DB::table('consecutivos_recibos_pedidos')
            ->where('consecutivo_actual', trim((string) $validated['numero_recibo']))
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA', 'REFLECTIVO'])
            ->update([
                'color_control_calidad' => trim((string) $validated['color']),
                'updated_at' => now(),
            ]);

        if ($updated <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró recibo para actualizar color de Control de Calidad',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Color de Control de Calidad guardado correctamente',
            'receiptNumber' => $validated['numero_recibo'],
        ]);
    }

    /**
     * Guardar color de fila en vista de Bordado y Estampado (Logo)
     */
    public function guardarColorBordadoEstampado(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_recibo' => 'required|string',
                'tipo_recibo' => 'required|string',
                'color' => 'required|string|max:100',
            ]);

            $numeroRecibo = trim((string) $validated['numero_recibo']);
            $tipoRecibo = trim((string) $validated['tipo_recibo']);
            $color = trim((string) $validated['color']);

            \Log::info('[GUARDAR-COLOR-BORDADO-START]', compact('numeroRecibo', 'tipoRecibo', 'color'));

            $updated = DB::table('consecutivos_recibos_pedidos')
                ->where('consecutivo_actual', $numeroRecibo)
                ->where('tipo_recibo', $tipoRecibo)
                ->update([
                    'color_bordado_estampado' => $color,
                    'updated_at' => now(),
                ]);

            \Log::info('[GUARDAR-COLOR-BORDADO-UPDATED]', ['updated' => $updated]);

            if ($updated <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró recibo para actualizar color de Bordado y Estampado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Color de Bordado y Estampado guardado correctamente',
                'receiptNumber' => $numeroRecibo,
            ]);
        } catch (\Exception $e) {
            \Log::error('[GUARDAR-COLOR-BORDADO-ERROR]', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar color: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar vista de pendientes de bordados y estampados por recibos
     */
    public function pendientesBordadoEstampado(Request $request)
    {
        $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest();
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($requestDTO);

        $allProcesses = collect($response->getProcesses());
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(10, min($perPage, 100));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = max(0, ($currentPage - 1) * $perPage);

        $procesosConCantidad = new LengthAwarePaginator(
            $allProcesses->slice($offset, $perPage)->values(),
            $allProcesses->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('supervisor-pedidos.pendientes-bordado-estampado', compact('procesosConCantidad'));
    }

    /**
     * Pendientes de Costura
     */
    public function pendientesCostura(Request $request)
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->numero_recibo : null,
            cliente: $request->filled('cliente') ? $request->cliente : null,
            asesor: $request->filled('asesor') ? $request->asesor : null,
            prendas: $request->filled('prendas') ? $request->prendas : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null
        );

        $response = $this->getPendingSewingReceiptsUseCase->execute($requestDTO);
        $allReceipts = collect($response->getReceipts());
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(10, min($perPage, 100));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = max(0, ($currentPage - 1) * $perPage);

        $procesosConCantidad = new LengthAwarePaginator(
            $allReceipts->slice($offset, $perPage)->values(),
            $allReceipts->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('supervisor-pedidos.pendientes-costura', compact('procesosConCantidad'));
    }

    /**
     * Pendientes de Control Calidad
     */
    public function pendientesControlCalidad(Request $request)
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->numero_recibo : null,
            cliente: $request->filled('cliente') ? $request->cliente : null,
            asesor: $request->filled('asesor') ? $request->asesor : null,
            prendas: $request->filled('prendas') ? $request->prendas : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null
        );

        $response = $this->getPendingQualityControlReceiptsUseCase->execute($requestDTO);
        $allReceipts = collect($response->getReceipts());
        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(10, min($perPage, 100));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = max(0, ($currentPage - 1) * $perPage);

        $procesosConCantidad = new LengthAwarePaginator(
            $allReceipts->slice($offset, $perPage)->values(),
            $allReceipts->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('supervisor-pedidos.pendientes-control-calidad', compact('procesosConCantidad'));
    }

    /**
     * Obtener contador de recibos COSTURA activos en control de calidad.
     * Endpoint: GET /supervisor-pedidos/pendientes-control-calidad-count
     */
    public function pendientesControlCalidadCount(): JsonResponse
    {
        $count = DB::table('consecutivos_recibos_pedidos')
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA', 'REFLECTIVO'])
            ->where('activo', 1)
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Obtener filtros por campo (opciones para dropdown)
     * 
     * Rutas de uso:
     * - GET /supervisor-pedidos/pendientes-costura/filtro-opciones/{campo}
     * - GET /supervisor-pedidos/pendientes-control-calidad/filtro-opciones/{campo}
     */
    public function obtenerOpcionesFiltroPendientesCostura($campo): JsonResponse
    {
        return $this->_obtenerOpcionesFiltroGenerico($campo);
    }

    public function obtenerOpcionesFiltroPendientesControlCalidad($campo): JsonResponse
    {
        return $this->_obtenerOpcionesFiltroGenerico($campo);
    }

    /**
     * Helper para obtener opciones de filtro de forma genérica
     */
    private function _obtenerOpcionesFiltroGenerico($campo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'opciones' => []
        ]);
    }

    /**
     * Obtener observación de un recibo/proceso por pedido+prenda+tipo.
     */
    public function obtenerObservacionReciboProceso(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
            'prenda_id' => 'required|integer|exists:prendas_pedido,id',
            'tipo_proceso' => 'required|string|max:100',
        ]);

        $pedidoId = (int) $validated['pedido_id'];
        $prendaId = (int) $validated['prenda_id'];
        $tipoProceso = $this->normalizarTipoProceso($validated['tipo_proceso']);

        $prenda = PrendaPedido::query()
            ->where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->first();

        if (!$prenda) {
            return response()->json([
                'success' => false,
                'message' => 'La prenda no pertenece al pedido indicado.',
            ], 422);
        }

        $row = DB::table('observaciones_recibos_procesos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->where('tipo_proceso', $tipoProceso)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'tipo_proceso' => $tipoProceso,
                'observacion' => $row?->observacion,
                'updated_at' => $row?->updated_at,
            ],
        ]);
    }

    /**
     * Guardar observación de un recibo/proceso por pedido+prenda+tipo.
     */
    public function guardarObservacionReciboProceso(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
            'prenda_id' => 'required|integer|exists:prendas_pedido,id',
            'tipo_proceso' => 'required|string|max:100',
            'observacion' => 'nullable|string|max:2000',
        ]);

        $pedidoId = (int) $validated['pedido_id'];
        $prendaId = (int) $validated['prenda_id'];
        $tipoProceso = $this->normalizarTipoProceso($validated['tipo_proceso']);
        $observacion = trim((string) ($validated['observacion'] ?? ''));

        $prenda = PrendaPedido::query()
            ->where('id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->first();

        if (!$prenda) {
            return response()->json([
                'success' => false,
                'message' => 'La prenda no pertenece al pedido indicado.',
            ], 422);
        }

        if ($observacion === '') {
            DB::table('observaciones_recibos_procesos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->where('tipo_proceso', $tipoProceso)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Observación eliminada correctamente.',
                'data' => [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId,
                    'tipo_proceso' => $tipoProceso,
                    'observacion' => null,
                ],
            ]);
        }

        $now = now();
        $existing = DB::table('observaciones_recibos_procesos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->where('tipo_proceso', $tipoProceso)
            ->first();

        if ($existing) {
            DB::table('observaciones_recibos_procesos')
                ->where('id', $existing->id)
                ->update([
                    'observacion' => $observacion,
                    'usuario_id' => auth()->id(),
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('observaciones_recibos_procesos')->insert([
                'pedido_produccion_id' => $pedidoId,
                'prenda_pedido_id' => $prendaId,
                'tipo_proceso' => $tipoProceso,
                'observacion' => $observacion,
                'usuario_id' => auth()->id(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Observación guardada correctamente.',
            'data' => [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'tipo_proceso' => $tipoProceso,
                'observacion' => $observacion,
            ],
        ]);
    }

    private function normalizarTipoProceso(string $tipoProceso): string
    {
        return mb_strtoupper(trim($tipoProceso), 'UTF-8');
    }
}
