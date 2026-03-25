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
use App\Http\Controllers\Controller;
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
     * Mostrar vista de pendientes de bordados y estampados por recibos
     */
    public function pendientesBordadoEstampado()
    {
        $request = new GetPendingEmbroideryStampingReceiptsRequest();
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($request);
        $procesosConCantidad = $response->getProcesses();

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
        $procesosConCantidad = $response->getReceipts();

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
        $procesosConCantidad = $response->getReceipts();

        return view('supervisor-pedidos.pendientes-control-calidad', compact('procesosConCantidad'));
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
}
