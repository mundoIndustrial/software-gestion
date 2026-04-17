<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingQualityControlReceiptsUseCase;
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
        private readonly GetPendingQualityControlReceiptsUseCase $getPendingQualityControlReceiptsUseCase
    ) {}

    public function pendingEmbroideryStamping(Request $request): JsonResponse
    {
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute(
            new GetPendingEmbroideryStampingReceiptsRequest(
                busqueda: $request->input('busqueda')
            )
        );

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de bordado/estampado recuperados correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function pendingSewing(Request $request): JsonResponse
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->input('numero_recibo') : null,
            cliente: $request->filled('cliente') ? $request->input('cliente') : null,
            asesor: $request->filled('asesor') ? $request->input('asesor') : null,
            prendas: $request->filled('prendas') ? $request->input('prendas') : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->input('fecha_creacion') : null,
            busqueda: $request->input('busqueda'),
        );

        $response = $this->getPendingSewingReceiptsUseCase->execute($requestDTO);

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de costura recuperados correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function pendingQualityControl(Request $request): JsonResponse
    {
        $requestDTO = new GetPendingSewingReceiptsRequest(
            numeroRecibo: $request->filled('numero_recibo') ? $request->input('numero_recibo') : null,
            cliente: $request->filled('cliente') ? $request->input('cliente') : null,
            asesor: $request->filled('asesor') ? $request->input('asesor') : null,
            prendas: $request->filled('prendas') ? $request->input('prendas') : null,
            fechaCreacion: $request->filled('fecha_creacion') ? $request->input('fecha_creacion') : null,
            busqueda: $request->input('busqueda'),
        );

        $response = $this->getPendingQualityControlReceiptsUseCase->execute($requestDTO);

        return response()->json([
            'success' => true,
            'message' => 'Pendientes de control de calidad recuperados correctamente',
            'data' => $response->toArray(),
        ]);
    }

    public function pendingQualityControlCount(): JsonResponse
    {
        $count = DB::table('consecutivos_recibos_pedidos')
            ->whereRaw('UPPER(TRIM(tipo_recibo)) IN (?, ?, ?)', ['COSTURA', 'COSTURA-BODEGA', 'REFLECTIVO'])
            ->where('activo', 1)
            ->whereRaw('LOWER(TRIM(area)) IN (?, ?)', ['control calidad', 'control de calidad'])
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
