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
        $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest(
            busqueda: $request->input('busqueda')
        );
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
            fechaCreacion: $request->filled('fecha_creacion') ? $request->fecha_creacion : null,
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

        return view('supervisor-pedidos.pendientes-control-calidad', compact('procesosConCantidad'));
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

        $numeroPedidoNormalizado = str_replace('#', '', trim((string) $pedido->numero_pedido));

        $pendientesCount = DB::table('bodega_detalles_talla as bdt')
            ->whereNull('bdt.deleted_at')
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.estado_bodega, ''))) = 'pendiente'")
            ->where(function ($q) use ($pedidoId, $pedido, $numeroPedidoNormalizado) {
                $q->where('bdt.pedido_produccion_id', $pedidoId)
                    ->orWhere('bdt.numero_pedido', (string) $pedido->numero_pedido)
                    ->orWhereRaw("REPLACE(TRIM(COALESCE(bdt.numero_pedido, '')), '#', '') = ?", [$numeroPedidoNormalizado]);
            })
            ->count();

        $notesCount = DB::table('bodega_notas as bn')
            ->where(function ($q) use ($pedidoId, $pedido, $numeroPedidoNormalizado) {
                $q->where('bn.pedido_produccion_id', $pedidoId)
                    ->orWhere('bn.numero_pedido', (string) $pedido->numero_pedido)
                    ->orWhereRaw("REPLACE(TRIM(COALESCE(bn.numero_pedido, '')), '#', '') = ?", [$numeroPedidoNormalizado]);
            })
            ->count();

        return response()->json([
            'success' => true,
            'pedido_id' => $pedidoId,
            'numero_pedido' => (string) $pedido->numero_pedido,
            'pending_count' => (int) $pendientesCount,
            'has_pending' => $pendientesCount > 0,
            'notes_count' => (int) $notesCount,
            'has_notes' => $notesCount > 0,
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
            ->where(function ($q) use ($pedidoId, $numeroPedidoNormalizado) {
                $q->where('bdt.pedido_produccion_id', $pedidoId)
                    ->orWhereRaw("REPLACE(TRIM(COALESCE(bdt.numero_pedido, '')), '#', '') = ?", [$numeroPedidoNormalizado]);
            })
            ->select([
                DB::raw("LOWER(TRIM(COALESCE(bdt.talla, ''))) as talla_normalizada"),
                'bdt.talla_color_id',
                'bdt.prenda_id',
                DB::raw('COALESCE(NULLIF(TRIM(bdt.prenda_nombre), ""), NULLIF(TRIM(pp.nombre_prenda), ""), "-") as prenda_nombre'),
                DB::raw('COALESCE(pp.descripcion, "-") as prenda_descripcion'),
                DB::raw('COALESCE(bdt.cantidad, 0) as cantidad'),
                DB::raw('COALESCE(bdt.asesor, "") as asesor'),
                DB::raw('COALESCE(bdt.empresa, "") as empresa'),
            ]);

        $detallesCosturaParaNotas = (clone $baseDetallesQuery)
            ->whereRaw("LOWER(TRIM(COALESCE(bdt.area, ''))) = 'costura'")
            ->get();

        $detallesSource = 'costura';
        if ($detallesCosturaParaNotas->isEmpty()) {
            // Fallback defensivo: si no hay detalle marcado como Costura,
            // intentamos con cualquier área para no perder el nombre de prenda.
            $detallesCosturaParaNotas = (clone $baseDetallesQuery)->get();
            $detallesSource = 'fallback_any_area';
        }

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

        $novedades = DB::table('bodega_notas as bn')
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
                if ($detallesRelacionados->isEmpty()) {
                    // Ultimo fallback: usar el primer detalle de costura del pedido.
                    $detallesRelacionados = $detallesCosturaParaNotas->take(1);
                }

                $prendaNombre = $detallesRelacionados->pluck('prenda_nombre')->filter()->unique()->values()->implode(', ');
                $prendaDescripcion = $detallesRelacionados->pluck('prenda_descripcion')->filter()->unique()->values()->implode(' | ');
                $cantidad = (int) $detallesRelacionados->sum(function ($d) {
                    return (int) ($d->cantidad ?? 0);
                });

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

                return $nota;
            });

        \Log::info('[SupervisorPedidos][BodegaNovedades] Resultado de armado de modal', [
            'pedido_id' => $pedidoId,
            'numero_pedido' => (string) $pedido->numero_pedido,
            'detalles_costura_encontrados' => $detallesCosturaParaNotas->count(),
            'detalles_source' => $detallesSource,
            'detalles_tallas_fallback_encontrados' => $detallesTallasFallback->count(),
            'notas_encontradas' => $novedades->count(),
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
}
