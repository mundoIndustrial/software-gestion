<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingReflectiveReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingSewingReceiptsUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupervisorReceiptsApiController extends Controller
{
    public function __construct(
        private readonly GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase,
        private readonly GetPendingSewingReceiptsUseCase $getPendingSewingReceiptsUseCase,
        private readonly GetPendingReflectiveReceiptsUseCase $getPendingReflectiveReceiptsUseCase,
        private readonly GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase
    ) {}

    public function pendingEmbroideryStamping(Request $request): JsonResponse
    {
        $verTodos = filter_var($request->query('ver_todos', false), FILTER_VALIDATE_BOOLEAN);

        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute(
            new GetPendingEmbroideryStampingReceiptsRequest(
                busqueda: $request->input('busqueda')
            )
        );

        $procesos = collect($response->getProcesses())
            ->when(!$verTodos, function ($collection) {
                return $collection->filter(function ($proceso) {
                    $color = mb_strtolower(trim((string) data_get($proceso, 'color_bordado_estampado', '')));
                    return $color !== '#e0f2fe';
                });
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de bordado/estampado recuperados correctamente',
            'data' => [
                'procesosConCantidad' => $procesos,
            ],
        ]);
    }

    public function pendingSewing(Request $request): JsonResponse
    {
        $verTodos = filter_var($request->query('ver_todos', false), FILTER_VALIDATE_BOOLEAN);
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->input('numero_recibo') : null,
            cliente: $request->filled('cliente') ? $request->input('cliente') : null,
            asesor: $request->filled('asesor') ? $request->input('asesor') : null,
            prendas: $request->filled('prendas') ? $request->input('prendas') : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->input('fecha_creacion') : null,
            area: $request->input('area'),
            busqueda: $request->input('busqueda'),
        );

        $response = $this->getPendingSewingReceiptsUseCase->execute($requestDTO);
        $procesos = collect($response->getReceipts())
            ->when(!$verTodos, function ($collection) {
                return $collection->filter(function ($proceso) {
                    $color = mb_strtolower(trim((string) data_get($proceso, 'color_costura', '')));
                    return $color !== '#e0f2fe';
                });
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de costura recuperados correctamente',
            'data' => [
                'procesosConCantidad' => $procesos,
            ],
        ]);
    }

    public function pendingReflective(Request $request): JsonResponse
    {
        $verTodos = filter_var($request->query('ver_todos', false), FILTER_VALIDATE_BOOLEAN);
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->input('numero_recibo') : null,
            cliente: $request->filled('cliente') ? $request->input('cliente') : null,
            asesor: $request->filled('asesor') ? $request->input('asesor') : null,
            prendas: $request->filled('prendas') ? $request->input('prendas') : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->input('fecha_creacion') : null,
            area: $request->input('area'),
            busqueda: $request->input('busqueda'),
        );

        $response = $this->getPendingReflectiveReceiptsUseCase->execute($requestDTO);
        $procesos = collect($response->getReceipts())
            ->when(!$verTodos, function ($collection) {
                return $collection->filter(function ($proceso) {
                    $color = mb_strtolower(trim((string) data_get($proceso, 'color_reflectivo', '')));
                    return $color !== '#e0f2fe';
                });
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de reflectivo recuperados correctamente',
            'data' => [
                'procesosConCantidad' => $procesos,
            ],
        ]);
    }

    public function pendingQualityControl(Request $request): JsonResponse
    {
        $verTodos = filter_var($request->query('ver_todos', false), FILTER_VALIDATE_BOOLEAN);
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->input('numero_recibo') : null,
            cliente: $request->filled('cliente') ? $request->input('cliente') : null,
            asesor: $request->filled('asesor') ? $request->input('asesor') : null,
            prendas: $request->filled('prendas') ? $request->input('prendas') : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->input('fecha_creacion') : null,
            area: $request->input('area'),
            busqueda: $request->input('busqueda'),
        );

        $response = $this->getPendingQualityControlReceiptsUseCase->execute($requestDTO);
        $procesos = collect($response->getReceipts())
            ->when(!$verTodos, function ($collection) {
                return $collection->filter(function ($proceso) {
                    $color = mb_strtolower(trim((string) data_get($proceso, 'color_control_calidad', '')));
                    return $color !== '#e0f2fe';
                });
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de control de calidad recuperados correctamente',
            'data' => [
                'procesosConCantidad' => $procesos,
            ],
        ]);
    }

    public function pendingQualityControlCount(): JsonResponse
    {
        // Mantener el mismo criterio que la tabla de pendientes-control-calidad
        // para evitar desfases entre el listado visible y el badge del sidebar.
        $count = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->whereRaw('UPPER(TRIM(crp.tipo_recibo)) IN (?, ?)', ['COSTURA', 'REFLECTIVO'])
            ->where('crp.activo', 1)
            ->whereRaw('LOWER(TRIM(crp.area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Conteo de pendientes de control de calidad recuperado correctamente',
            'data' => [
                'count' => $count,
            ],
        ]);
    }
}
