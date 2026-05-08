<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\UseCases\ActivateSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\CancelSewingReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveReceiptArrivalDateUseCase;
use App\Application\SupervisorPedidos\UseCases\GetReceiptDetailsUseCase;
use App\Application\SupervisorPedidos\UseCases\ApproveReceiptUseCase;
use App\Application\SupervisorPedidos\UseCases\SaveSewingReceiptColorUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingReflectiveReceiptsUseCase;
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
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use \App\Application\SupervisorPedidos\DTOs\ApproveReceiptRequest;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Carbon\CarbonInterface;
use App\Services\DiasHabilesService;
use App\Jobs\GenerarReporteCosturaJob;
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
    private GetPendingReflectiveReceiptsUseCase $getPendingReflectiveReceiptsUseCase;
    private GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase;
    private GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase;
    private ReceiptRepository $receiptRepository;
    private DiasHabilesService $diasHabilesService;

    public function __construct(
        ActivateSewingReceiptUseCase $activateSewingReceiptUseCase,
        CancelSewingReceiptUseCase $cancelSewingReceiptUseCase,
        SaveReceiptArrivalDateUseCase $saveReceiptArrivalDateUseCase,
        GetReceiptDetailsUseCase $getReceiptDetailsUseCase,
        ApproveReceiptUseCase $approveReceiptUseCase,
        SaveSewingReceiptColorUseCase $saveSewingReceiptColorUseCase,
        GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase,
        GetPendingReflectiveReceiptsUseCase $getPendingReflectiveReceiptsUseCase,
        GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase,
        GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase,
        ReceiptRepository $receiptRepository,
        DiasHabilesService $diasHabilesService
    ) {
        $this->activateSewingReceiptUseCase = $activateSewingReceiptUseCase;
        $this->cancelSewingReceiptUseCase = $cancelSewingReceiptUseCase;
        $this->saveReceiptArrivalDateUseCase = $saveReceiptArrivalDateUseCase;
        $this->getReceiptDetailsUseCase = $getReceiptDetailsUseCase;
        $this->approveReceiptUseCase = $approveReceiptUseCase;
        $this->saveSewingReceiptColorUseCase = $saveSewingReceiptColorUseCase;
        $this->getPendingSewingReceiptsUseCase = $getPendingSewingReceiptsUseCase;
        $this->getPendingReflectiveReceiptsUseCase = $getPendingReflectiveReceiptsUseCase;
        $this->getPendingQualityControlReceiptsUseCase = $getPendingQualityControlReceiptsUseCase;
        $this->getPendingEmbroideryStampingReceiptsUseCase = $getPendingEmbroideryStampingReceiptsUseCase;
        $this->receiptRepository = $receiptRepository;
        $this->diasHabilesService = $diasHabilesService;
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
     * Guardar color de fila en vista de Reflectivo
     */
    public function guardarColorReflectivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'numero_recibo' => 'required|string',
            'color' => 'required|string|max:100',
        ]);

        $updated = DB::table('consecutivos_recibos_pedidos')
            ->where('consecutivo_actual', trim((string) $validated['numero_recibo']))
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', ['REFLECTIVO'])
            ->update([
                'color_reflectivo' => trim((string) $validated['color']),
                'updated_at' => now(),
            ]);

        if ($updated <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró recibo reflectivo para actualizar color',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Color de Reflectivo guardado correctamente',
            'receiptNumber' => $validated['numero_recibo'],
        ]);
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
        $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest(
            busqueda: $request->input('busqueda')
        );
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($requestDTO);

        $allProcesses = collect($response->getProcesses())
            ->sortByDesc(function ($proceso) {
                $fechaAprobacion = data_get($proceso, 'fecha_aprobacion');
                if (empty($fechaAprobacion)) {
                    return 0;
                }

                return strtotime((string) $fechaAprobacion) ?: 0;
            })
            ->values();
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
        $areaParam = $request->input('area');
        \Log::info('[DEBUG] Parámetro area en controlador', ['area' => $areaParam, 'type' => gettype($areaParam)]);

        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->input('numero_recibo'),
            cliente: $request->input('cliente'),
            asesor: $request->input('asesor'),
            prendas: $request->input('prendas'),
            fechaCreacion: $request->input('fecha_creacion'),
            area: $areaParam,
            busqueda: $request->input('busqueda')
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
     * Descargar reporte CSV de pendientes de costura, agrupado por area.
     */
    public function reportePendientesCostura(Request $request)
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->input('numero_recibo'),
            cliente: $request->input('cliente'),
            asesor: $request->input('asesor'),
            prendas: $request->input('prendas'),
            fechaCreacion: $request->input('fecha_creacion'),
            area: $request->input('area'),
            busqueda: $request->input('busqueda')
        );

        $response = $this->getPendingSewingReceiptsUseCase->execute($requestDTO);
        $diasAntiguedad = (int) $request->input('dias_antiguedad', 0);

        $areasPermitidas = ['insumos', 'corte', 'costura'];

        $receipts = collect($response->getReceipts())
            ->filter(function ($item) use ($areasPermitidas) {
                $area = mb_strtolower(trim((string) data_get($item, 'area', '')));
                $estado = mb_strtolower(trim((string) data_get($item, 'estado', '')));

                // Solo mostrar áreas permitidas
                if (!in_array($area, $areasPermitidas, true)) {
                    return false;
                }

                // Excluir estados terminados: anulada, entregado, etc
                $estadosExcluidos = ['anulada', 'anulado', 'entregado', 'entregada', 'cancelada', 'cancelado'];
                if (in_array($estado, $estadosExcluidos, true)) {
                    return false;
                }

                return true;
            })
            ->map(function ($item) {
                // Calcular días hábiles desde la fecha de creación (sin sábados, domingos, ni festivos)
                $fechaCreacion = data_get($item, 'fecha_creacion');
                if ($fechaCreacion) {
                    $fecha = \Carbon\Carbon::parse($fechaCreacion);
                    $diasHabiles = $this->calcularDiasHabiles($fecha, now());
                    $item['dias_transcurridos'] = $diasHabiles;
                } else {
                    $item['dias_transcurridos'] = 0;
                }
                return $item;
            })
            ->filter(function ($item) use ($diasAntiguedad) {
                // Filtrar por rango de antigüedad (de 1 a N días)
                if ($diasAntiguedad > 0) {
                    $diasTranscurridos = (int) data_get($item, 'dias_transcurridos', 0);
                    // Solo mostrar si están dentro del rango de 1 a N días
                    return $diasTranscurridos >= 1 && $diasTranscurridos <= $diasAntiguedad;
                }
                // Si no hay filtro, mostrar todos
                return true;
            })
            ->sortBy(function ($item) {
                $fecha = data_get($item, 'fecha_creacion');
                return $fecha ? strtotime((string) $fecha) : PHP_INT_MAX;
            })
            ->values();

        $grouped = $receipts
            ->groupBy(function ($item) {
                $dias = (int) data_get($item, 'dias_transcurridos', 0);
                return $dias;
            })
            ->sortByDesc(function ($group, $dias) {
                return (int) $dias;
            });

        $totalRecibos = $receipts->count();
        $filtros = $request->only([
            'numero_recibo',
            'cliente',
            'asesor',
            'prendas',
            'fecha_creacion',
            'area',
            'busqueda',
            'dias_antiguedad',
        ]);

        // Generar el PDF directamente
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('supervisor-pedidos.reporte-pendientes-costura-pdf', [
            'grouped' => $grouped,
            'totalRecibos' => $totalRecibos,
            'filtros' => $filtros,
            'fechaGeneracion' => now(),
            'diasAntiguedad' => $diasAntiguedad,
        ])->setPaper('a4', 'landscape');

        $filename = "reporte_pendientes_costura_" . now()->format('Ymd_His') . ".pdf";

        return $pdf->download($filename);
    }

    /**
     * Pendientes de Reflectivo
     */
    public function pendientesReflectivo(Request $request)
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->numero_recibo : null,
            cliente: $request->filled('cliente') ? $request->cliente : null,
            asesor: $request->filled('asesor') ? $request->asesor : null,
            prendas: $request->filled('prendas') ? $request->prendas : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null,
            busqueda: $request->input('busqueda')
        );

        $response = $this->getPendingReflectiveReceiptsUseCase->execute($requestDTO);
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

        return view('supervisor-pedidos.pendientes-reflectivo', compact('procesosConCantidad'));
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
            fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null,
            busqueda: $request->input('busqueda')
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
        $procesosConCantidad->setCollection(
            $this->adjuntarNovedadesControlCalidad($procesosConCantidad->getCollection())
        );

        return view('supervisor-pedidos.pendientes-control-calidad', compact('procesosConCantidad'));
    }

    /**
     * Evita consultas N+1 en Blade para novedades por recibo.
     */
    private function adjuntarNovedadesControlCalidad(Collection $procesos): Collection
    {
        if ($procesos->isEmpty()) {
            return $procesos;
        }

        $combinaciones = $procesos
            ->map(function ($proceso) {
                $prendaId = (int) data_get($proceso, 'prenda_id', 0);
                $numeroRecibo = trim((string) data_get($proceso, 'numero_recibo', ''));

                if ($prendaId <= 0 || $numeroRecibo === '') {
                    return null;
                }

                return [
                    'prenda_id' => $prendaId,
                    'numero_recibo' => $numeroRecibo,
                    'key' => $prendaId . '|' . $numeroRecibo,
                ];
            })
            ->filter()
            ->values();

        if ($combinaciones->isEmpty()) {
            return $procesos;
        }

        $prendaIds = $combinaciones->pluck('prenda_id')->unique()->values()->all();
        $numeroRecibos = $combinaciones->pluck('numero_recibo')->unique()->values()->all();
        $keysPermitidas = array_fill_keys($combinaciones->pluck('key')->all(), true);

        $rows = DB::table('prendas_pedido_novedades_recibo')
            ->select(['prenda_pedido_id', 'numero_recibo', 'novedad_texto', 'creado_en', 'created_at'])
            ->whereIn('prenda_pedido_id', $prendaIds)
            ->whereIn('numero_recibo', $numeroRecibos)
            ->orderByDesc(DB::raw('COALESCE(creado_en, created_at)'))
            ->get();

        $novedadesPorRecibo = [];

        foreach ($rows as $row) {
            $key = (int) ($row->prenda_pedido_id ?? 0) . '|' . trim((string) ($row->numero_recibo ?? ''));
            if (!isset($keysPermitidas[$key])) {
                continue;
            }

            $texto = trim(str_replace(["\r", "\n", "'", '"'], ' ', (string) ($row->novedad_texto ?? '')));
            if ($texto === '') {
                continue;
            }

            if (!isset($novedadesPorRecibo[$key])) {
                $novedadesPorRecibo[$key] = [];
            }
            $novedadesPorRecibo[$key][] = $texto;
        }

        return $procesos->map(function ($proceso) use ($novedadesPorRecibo) {
            $key = (int) data_get($proceso, 'prenda_id', 0) . '|' . trim((string) data_get($proceso, 'numero_recibo', ''));
            $novedadesTexto = isset($novedadesPorRecibo[$key]) && !empty($novedadesPorRecibo[$key])
                ? implode(' | ', $novedadesPorRecibo[$key])
                : '';

            if (is_array($proceso)) {
                $proceso['novedades_texto'] = $novedadesTexto;
                return $proceso;
            }

            if (is_object($proceso)) {
                $proceso->novedades_texto = $novedadesTexto;
                return $proceso;
            }

            return $proceso;
        });
    }

    /**
     * Resumen para dropdown "Ver":
     * cantidad de pendientes de bodega-costura para el pedido.
     */
    public function resumenNovedadesBodegaCostura(int $pedidoId): JsonResponse
    {
        $pedido = DB::table('pedidos_produccion')
            ->select('id', 'numero_pedido')
            ->where('id', $pedidoId)
            ->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        $pendientesCount = DB::table('bodega_detalles_talla as bdt')
            ->whereNull('bdt.deleted_at')
            ->where('bdt.area', 'costura')
            ->where('bdt.estado_bodega', 'pendiente')
            ->where('bdt.pedido_produccion_id', $pedidoId)
            ->whereNull('bdt.pedido_epp_id')
            ->whereNotNull('bdt.prenda_id')
            ->count();

        $notesCount = DB::table('bodega_notas as bn')
            ->where('bn.pedido_produccion_id', $pedidoId)
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('bodega_detalles_talla as bdt')
                    ->whereNull('bdt.deleted_at')
                    ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
                    ->whereNull('bdt.pedido_epp_id')
                    ->whereNotNull('bdt.prenda_id')
                    ->whereColumn('bdt.pedido_produccion_id', 'bn.pedido_produccion_id')
                    ->where(function ($match) {
                        $match->where(function ($byColor) {
                            $byColor->whereNotNull('bn.talla_color_id')
                                ->whereColumn('bdt.talla_color_id', 'bn.talla_color_id');
                        })->orWhere(function ($bySize) {
                            $bySize->whereRaw('(bn.talla_color_id IS NULL OR bdt.talla_color_id IS NULL)')
                                ->whereRaw("LOWER(TRIM(COALESCE(bdt.talla, ''))) = LOWER(TRIM(COALESCE(bn.talla, '')))");
                        });
                    });
            })
            ->count();

        // Si no hay nota pero sí pendiente en Costura, debe seguir mostrándose alerta.
        $alertCount = max((int) $notesCount, (int) $pendientesCount);

        return response()->json([
            'success' => true,
            'pedido_id' => $pedidoId,
            'numero_pedido' => (string) $pedido->numero_pedido,
            'pending_count' => (int) $pendientesCount,
            'has_pending' => $pendientesCount > 0,
            'notes_count' => (int) $alertCount,
            'has_notes' => $alertCount > 0,
            'raw_notes_count' => (int) $notesCount,
        ]);
    }

    public function resumenNovedadesBodegaBatch(Request $request): JsonResponse
    {
        $pedidoIds = $request->input('pedido_ids', []);
        if (!is_array($pedidoIds) || empty($pedidoIds)) {
            return response()->json([
                'success' => false,
                'message' => 'pedido_ids requerido y debe ser un array',
            ], 400);
        }

        $pedidos = DB::table('pedidos_produccion')
            ->select('id', 'numero_pedido')
            ->whereIn('id', array_slice($pedidoIds, 0, 50))
            ->get()
            ->keyBy('id');

        $notas = DB::table('bodega_notas as bn')
            ->whereIn('bn.pedido_produccion_id', $pedidoIds)
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('bodega_detalles_talla as bdt')
                    ->whereNull('bdt.deleted_at')
                    ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
                    ->whereNull('bdt.pedido_epp_id')
                    ->whereNotNull('bdt.prenda_id')
                    ->whereColumn('bdt.pedido_produccion_id', 'bn.pedido_produccion_id')
                    ->where(function ($match) {
                        $match->where(function ($byColor) {
                            $byColor->whereNotNull('bn.talla_color_id')
                                ->whereColumn('bdt.talla_color_id', 'bn.talla_color_id');
                        })->orWhere(function ($bySize) {
                            $bySize->whereRaw('(bn.talla_color_id IS NULL OR bdt.talla_color_id IS NULL)')
                                ->whereRaw("LOWER(TRIM(COALESCE(bdt.talla, ''))) = LOWER(TRIM(COALESCE(bn.talla, '')))");
                        });
                    });
            })
            ->select('bn.pedido_produccion_id')
            ->selectRaw('COUNT(DISTINCT bn.id) as notes_count')
            ->groupBy('bn.pedido_produccion_id')
            ->get()
            ->keyBy('pedido_produccion_id');

        $pendientes = DB::table('bodega_detalles_talla as bdt')
            ->whereNull('bdt.deleted_at')
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.estado_bodega, ''))) = 'pendiente'")
            ->whereNull('bdt.pedido_epp_id')
            ->whereNotNull('bdt.prenda_id')
            ->whereIn('bdt.pedido_produccion_id', $pedidoIds)
            ->select('bdt.pedido_produccion_id')
            ->selectRaw('COUNT(*) as pending_count')
            ->groupBy('bdt.pedido_produccion_id')
            ->get()
            ->keyBy('pedido_produccion_id');

        $resultado = [];
        foreach ($pedidoIds as $pedidoId) {
            if (!isset($pedidos[$pedidoId])) continue;

            $rawNotesCount = (int) ($notas[$pedidoId]->notes_count ?? 0);
            $pendingCount = (int) ($pendientes[$pedidoId]->pending_count ?? 0);
            $alertCount = max($rawNotesCount, $pendingCount);
            $resultado[] = [
                'pedido_id' => $pedidoId,
                'notes_count' => $alertCount,
                'has_notes' => $alertCount > 0,
                'raw_notes_count' => $rawNotesCount,
                'pending_count' => $pendingCount,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    /**
     * Listado de notas de bodega asociadas a pendientes de Costura del pedido.
     */
    public function obtenerNovedadesBodegaCostura(int $pedidoId): JsonResponse
    {
        $pedido = DB::table('pedidos_produccion')
            ->select('id', 'numero_pedido')
            ->where('id', $pedidoId)
            ->first();

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        $numeroPedidoNormalizado = str_replace('#', '', trim((string) $pedido->numero_pedido));

        // Para enriquecer notas (prenda/descripcion/cantidad) NO restringimos por estado_bodega,
        // porque la nota puede existir aunque el detalle ya no esté en Pendiente.
        // Priorizamos match por pedido_produccion_id y usamos numero_pedido como respaldo.
        $baseDetallesQuery = DB::table('bodega_detalles_talla as bdt')
            ->leftJoin('prendas_pedido as pp', 'pp.id', '=', 'bdt.prenda_id')
            ->whereNull('bdt.deleted_at')
            ->whereNull('bdt.pedido_epp_id')
            ->whereNotNull('bdt.prenda_id')
            ->where(function ($q) use ($pedidoId, $numeroPedidoNormalizado) {
                $q->where('bdt.pedido_produccion_id', $pedidoId)
                    ->orWhereRaw("REPLACE(TRIM(COALESCE(bdt.numero_pedido, '')), '#', '') = ?", [$numeroPedidoNormalizado]);
            })
            ->select([
                DB::raw("LOWER(TRIM(COALESCE(bdt.talla, ''))) as talla_normalizada"),
                'bdt.talla',
                DB::raw('COALESCE(NULLIF(TRIM(bdt.genero), ""), "-") as genero'),
                'bdt.talla_color_id',
                'bdt.prenda_id',
                DB::raw('COALESCE(NULLIF(TRIM(bdt.prenda_nombre), ""), NULLIF(TRIM(pp.nombre_prenda), ""), "-") as prenda_nombre'),
                DB::raw('COALESCE(pp.descripcion, "-") as prenda_descripcion'),
                DB::raw('COALESCE(bdt.cantidad, 0) as cantidad'),
                DB::raw('COALESCE(bdt.pendientes, 0) as pendientes'),
                DB::raw('COALESCE(bdt.asesor, "") as asesor'),
                DB::raw('COALESCE(bdt.empresa, "") as empresa'),
                'bdt.fecha_pendiente',
                'bdt.created_at',
                'bdt.updated_at',
            ]);

        $detallesCosturaParaNotas = (clone $baseDetallesQuery)
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
            ->get();

        $detallesSource = 'costura_only';

        // Fallback adicional desde tallas de prenda del pedido.
        // Esto cubre casos donde bodega_detalles_talla no tiene filas para el pedido.
        $detallesTallasFallback = DB::table('prenda_pedido_tallas as ppt')
            ->join('prendas_pedido as pp', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->whereNull('pp.deleted_at')
            ->where('pp.pedido_produccion_id', $pedidoId)
            ->select([
                DB::raw("LOWER(TRIM(COALESCE(ppt.talla, ''))) as talla_normalizada"),
                DB::raw('COALESCE(NULLIF(TRIM(pp.nombre_prenda), ""), "-") as prenda_nombre'),
                DB::raw('COALESCE(NULLIF(TRIM(pp.descripcion), ""), "-") as prenda_descripcion'),
                DB::raw('COALESCE(ppt.cantidad, 0) as cantidad'),
            ])
            ->get();

        $pedidoMeta = DB::table('pedidos_produccion as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.asesor_id')
            ->where('p.id', $pedidoId)
            ->select([
                DB::raw('COALESCE(u.name, "") as asesora'),
                DB::raw('COALESCE(p.cliente, "") as cliente'),
            ])
            ->first();

        $asesoraHeader = trim((string) ($pedidoMeta->asesora ?? ''));
        $clienteHeader = trim((string) ($pedidoMeta->cliente ?? ''));

        if ($asesoraHeader === '') {
            $asesoraHeader = trim((string) ($detallesCosturaParaNotas->first()->asesor ?? ''));
        }
        if ($clienteHeader === '') {
            $clienteHeader = trim((string) ($detallesCosturaParaNotas->first()->empresa ?? ''));
        }

        $notas = DB::table('bodega_notas as bn')
            ->where(function ($q) use ($pedidoId, $pedido, $numeroPedidoNormalizado) {
                $q->where('bn.pedido_produccion_id', $pedidoId)
                    ->orWhere('bn.numero_pedido', (string) $pedido->numero_pedido)
                    ->orWhereRaw("REPLACE(TRIM(COALESCE(bn.numero_pedido, '')), '#', '') = ?", [$numeroPedidoNormalizado]);
            })
            ->select([
                'bn.id',
                'bn.pedido_produccion_id',
                'bn.numero_pedido',
                'bn.talla',
                'bn.talla_color_id',
                'bn.contenido',
                'bn.usuario_nombre',
                'bn.usuario_rol',
                'bn.visto_at',
                'bn.created_at',
                'bn.updated_at',
            ])
            ->orderByDesc('bn.created_at')
            ->limit(200)
            ->get()
            ->filter(function ($nota) use ($detallesCosturaParaNotas) {
                $tallaNormalizada = mb_strtolower(trim((string) ($nota->talla ?? '')));
                $tallaColorId = $nota->talla_color_id;

                $coincidePorTallaColor = $detallesCosturaParaNotas->contains(function ($d) use ($tallaColorId) {
                    if ($tallaColorId === null) {
                        return false;
                    }

                    return (int) ($d->talla_color_id ?? 0) === (int) $tallaColorId;
                });

                if ($coincidePorTallaColor) {
                    return true;
                }

                if ($tallaNormalizada === '') {
                    return false;
                }

                return $detallesCosturaParaNotas->contains(function ($d) use ($tallaNormalizada) {
                    return (string) ($d->talla_normalizada ?? '') === $tallaNormalizada;
                });
            })
            ->map(function ($nota) use ($detallesCosturaParaNotas, $detallesTallasFallback) {
                $tallaNormalizada = mb_strtolower(trim((string) ($nota->talla ?? '')));
                $tallaColorId = $nota->talla_color_id;

                $detallesExactos = $detallesCosturaParaNotas->filter(function ($d) use ($tallaNormalizada, $tallaColorId) {
                    return (string) $d->talla_normalizada === (string) $tallaNormalizada
                        && (
                            ((int) ($d->talla_color_id ?? 0) === (int) ($tallaColorId ?? 0))
                            || ($d->talla_color_id === null && $tallaColorId === null)
                        );
                });

                $detallesPorTalla = $detallesCosturaParaNotas->filter(function ($d) use ($tallaNormalizada) {
                    return (string) $d->talla_normalizada === (string) $tallaNormalizada;
                });

                $detallesPorTallaColor = $detallesCosturaParaNotas->filter(function ($d) use ($tallaColorId) {
                    return (
                        ((int) ($d->talla_color_id ?? 0) === (int) ($tallaColorId ?? 0))
                        || ($d->talla_color_id === null && $tallaColorId === null)
                    );
                });

                $detallesRelacionados = $detallesExactos;
                if ($detallesRelacionados->isEmpty()) {
                    $detallesRelacionados = $detallesPorTalla;
                }
                if ($detallesRelacionados->isEmpty()) {
                    $detallesRelacionados = $detallesPorTallaColor;
                }

                $prendaNombre = $detallesRelacionados->pluck('prenda_nombre')->filter()->unique()->values()->implode(', ');
                $prendaDescripcion = $detallesRelacionados->pluck('prenda_descripcion')->filter()->unique()->values()->implode(' | ');
                $cantidad = (int) $detallesRelacionados->sum(function ($d) {
                    return (int) ($d->cantidad ?? 0);
                });
                $genero = $detallesRelacionados->pluck('genero')->filter(function ($value) {
                    $val = trim((string) $value);
                    return $val !== '' && $val !== '-';
                })->unique()->values()->implode(', ');

                // Si seguimos sin datos útiles desde bodega, usar tallas fallback.
                $sinPrendaUtil = $prendaNombre === '' || $prendaNombre === '-';
                $sinDescripcionUtil = $prendaDescripcion === '' || $prendaDescripcion === '-';
                $sinCantidadUtil = $cantidad <= 0;

                if ($sinPrendaUtil || $sinDescripcionUtil || $sinCantidadUtil) {
                    $fallbackPorTalla = $detallesTallasFallback->filter(function ($d) use ($tallaNormalizada) {
                        return (string) $d->talla_normalizada === (string) $tallaNormalizada;
                    });

                    if ($fallbackPorTalla->isNotEmpty()) {
                        if ($sinPrendaUtil) {
                            $prendaNombre = $fallbackPorTalla->pluck('prenda_nombre')->filter()->unique()->values()->implode(', ');
                        }
                        if ($sinDescripcionUtil) {
                            $prendaDescripcion = $fallbackPorTalla->pluck('prenda_descripcion')->filter()->unique()->values()->implode(' | ');
                        }
                        if ($sinCantidadUtil) {
                            $cantidad = (int) $fallbackPorTalla->sum(function ($d) {
                                return (int) ($d->cantidad ?? 0);
                            });
                        }
                    }
                }

                $nota->prenda_nombre = $prendaNombre !== '' ? $prendaNombre : '-';
                $nota->prenda_descripcion = $prendaDescripcion !== '' ? $prendaDescripcion : '-';
                $nota->cantidad = $cantidad;
                $nota->genero = $genero !== '' ? $genero : '-';

                return $nota;
            });

        $tallasConNota = $notas->map(function ($nota) {
            $tallaColorId = $nota->talla_color_id;
            if ($tallaColorId !== null) {
                return 'tc:' . (int) $tallaColorId;
            }

            $talla = mb_strtolower(trim((string) ($nota->talla ?? '')));
            return 't:' . $talla;
        })->unique()->values();

        $pendientesSinNota = $detallesCosturaParaNotas
            ->filter(function ($d) use ($tallasConNota) {
                $pendientes = (int) ($d->pendientes ?? 0);
                if ($pendientes <= 0) {
                    return false;
                }

                $tallaColorId = $d->talla_color_id;
                $key = $tallaColorId !== null
                    ? 'tc:' . (int) $tallaColorId
                    : 't:' . mb_strtolower(trim((string) ($d->talla ?? '')));

                return !$tallasConNota->contains($key);
            })
            ->map(function ($d) use ($pedidoId, $pedido) {
                $pendiente = (int) ($d->pendientes ?? 0);
                $createdAt = $d->fecha_pendiente ?? $d->updated_at ?? $d->created_at ?? now();

                return (object) [
                    'id' => 'pendiente-' . ($d->talla_color_id ?? md5((string) ($d->talla ?? ''))),
                    'pedido_produccion_id' => $pedidoId,
                    'numero_pedido' => (string) $pedido->numero_pedido,
                    'talla' => (string) ($d->talla ?? ''),
                    'talla_color_id' => $d->talla_color_id,
                    'contenido' => 'Pendiente registrado sin nota por parte del bodeguero.',
                    'usuario_nombre' => 'Sistema',
                    'usuario_rol' => 'Bodega',
                    'visto_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'prenda_nombre' => (string) ($d->prenda_nombre ?? '-'),
                    'prenda_descripcion' => (string) ($d->prenda_descripcion ?? '-'),
                    'cantidad' => $pendiente,
                    'genero' => (string) ($d->genero ?? '-'),
                ];
            });

        $novedades = $notas
            ->concat($pendientesSinNota)
            ->sortByDesc(function ($item) {
                return strtotime((string) ($item->created_at ?? '1970-01-01 00:00:00'));
            })
            ->values();

        \Log::info('[SupervisorPedidos][BodegaNovedades] Resultado de armado de modal', [
            'pedido_id' => $pedidoId,
            'numero_pedido' => (string) $pedido->numero_pedido,
            'detalles_costura_encontrados' => $detallesCosturaParaNotas->count(),
            'detalles_source' => $detallesSource,
            'detalles_tallas_fallback_encontrados' => $detallesTallasFallback->count(),
            'notas_encontradas' => $notas->count(),
            'pendientes_sin_nota' => $pendientesSinNota->count(),
            'novedades_totales' => $novedades->count(),
            'notas_sin_prenda' => $novedades->filter(fn ($n) => ($n->prenda_nombre ?? '-') === '-')->count(),
        ]);

        return response()->json([
            'success' => true,
            'pedido_id' => $pedidoId,
            'numero_pedido' => (string) $pedido->numero_pedido,
            'asesora' => $asesoraHeader !== '' ? $asesoraHeader : '-',
            'cliente' => $clienteHeader !== '' ? $clienteHeader : '-',
            'count' => $novedades->count(),
            'data' => $novedades,
        ]);
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

    public function obtenerOpcionesFiltroPendientesReflectivo($campo): JsonResponse
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
        try {
            $opciones = $this->receiptRepository->getSewingReceiptFilterOptions($campo);

            return response()->json([
                'success' => true,
                'opciones' => $opciones
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener opciones de filtro', [
                'campo' => $campo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'opciones' => [],
                'error' => 'Error al obtener opciones de filtro'
            ], 500);
        }
    }

    /**
     * Obtener observación de un recibo/proceso por pedido+prenda+tipo.
     */
    public function obtenerObservacionReciboProceso(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pedido_id' => 'required|integer|exists:pedidos_produccion,id',
            'prenda_id' => 'nullable|integer',
            'parcial_id' => 'nullable|integer',
            'tipo_proceso' => 'required|string|max:100',
        ]);

        $pedidoId = (int) $validated['pedido_id'];
        $prendaId = (int) ($validated['prenda_id'] ?? 0);
        $parcialId = (int) ($validated['parcial_id'] ?? 0);
        $tipoProceso = $this->normalizarTipoProceso($validated['tipo_proceso']);
        $prendaIdsCandidatas = [];
        if ($prendaId > 0) {
            $prendaIdsCandidatas[] = $prendaId;
        }
        if ($parcialId > 0) {
            $prendaParcialId = (int) DB::table('pedidos_parciales')
                ->where('id', $parcialId)
                ->where('pedido_produccion_id', $pedidoId)
                ->value('prenda_pedido_id');
            if ($prendaParcialId > 0) {
                $prendaIdsCandidatas[] = $prendaParcialId;
            }
        }
        $prendaIdsCandidatas = array_values(array_unique(array_filter($prendaIdsCandidatas, fn($id) => (int) $id > 0)));

        $row = null;
        foreach ($this->tiposProcesoCandidatos($tipoProceso) as $tipoCandidato) {
            if (!empty($prendaIdsCandidatas)) {
                foreach ($prendaIdsCandidatas as $prendaCandidataId) {
                    $row = DB::table('observaciones_recibos_procesos')
                        ->where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_pedido_id', (int) $prendaCandidataId)
                        ->where('tipo_proceso', $tipoCandidato)
                        ->orderByDesc('updated_at')
                        ->first();

                    if ($row) {
                        break 2;
                    }
                }
            } else {
                $row = DB::table('observaciones_recibos_procesos')
                    ->where('pedido_produccion_id', $pedidoId)
                    ->where('tipo_proceso', $tipoCandidato)
                    ->orderByDesc('updated_at')
                    ->first();
                if ($row) {
                    break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pedido_id' => $pedidoId,
                'prenda_id' => (int) ($row->prenda_pedido_id ?? $prendaId),
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

    /**
     * Permite recuperar observaciones de anexos aunque se hayan guardado con
     * etiqueta PARCIAL o COSTURA de forma indistinta.
     *
     * @return string[]
     */
    private function tiposProcesoCandidatos(string $tipoProceso): array
    {
        $tipo = $this->normalizarTipoProceso($tipoProceso);

        if ($tipo === 'PARCIAL') {
            return ['PARCIAL', 'COSTURA', 'COSTURA-BODEGA'];
        }

        if ($tipo === 'COSTURA' || $tipo === 'COSTURA-BODEGA') {
            return [$tipo, 'COSTURA', 'COSTURA-BODEGA', 'PARCIAL'];
        }

        return [$tipo];
    }

    /**
     * Verifica si el PDF de reporte está listo para descargar
     */
    public function verificarReporteCosturaListo(): JsonResponse
    {
        $timestamp = request()->input('timestamp');
        $userId = auth()->id();

        if (!$timestamp) {
            return response()->json([
                'success' => false,
                'message' => 'Timestamp requerido',
            ], 400);
        }

        $filename = "reporte_pendientes_costura_por_area_{$timestamp}.pdf";
        $filePath = "reportes/costura/{$userId}/{$filename}";

        $exists = \Storage::disk('local')->exists($filePath);

        return response()->json([
            'success' => true,
            'ready' => $exists,
            'file_path' => $exists ? $filePath : null,
        ]);
    }

    /**
     * Descargar el PDF del reporte de costura
     */
    public function descargarReporteCostura()
    {
        $filePath = request()->input('file_path');
        $userId = auth()->id();

        if (!$filePath || !str_starts_with($filePath, "reportes/costura/{$userId}/")) {
            abort(403, 'No autorizado');
        }

        if (!\Storage::disk('local')->exists($filePath)) {
            abort(404, 'Archivo no encontrado');
        }

        $fullPath = \Storage::disk('local')->path($filePath);
        return response()->download($fullPath, basename($filePath));
    }

    /**
     * Calcula los días hábiles (excluyendo sábados, domingos y festivos)
     * entre dos fechas usando el servicio DiasHabilesService.
     */
    private function calcularDiasHabiles(\Carbon\Carbon $fechaInicio, \Carbon\Carbon $fechaFin): int
    {
        return $this->diasHabilesService->calcularDiasHabiles($fechaInicio, $fechaFin);
    }
}
