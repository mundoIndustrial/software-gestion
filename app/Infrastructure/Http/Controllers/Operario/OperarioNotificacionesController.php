<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\UseCases\ListarNotificacionesRecibosUseCase;
use App\Application\Operario\UseCases\MarcarNotificacionReciboLeidaUseCase;
use App\Application\Operario\UseCases\MarcarTodasNotificacionesRecibosLeidasUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperarioNotificacionesController extends Controller
{
    public function __construct(
        private ListarNotificacionesRecibosUseCase $listarNotificacionesRecibosUseCase,
        private MarcarNotificacionReciboLeidaUseCase $marcarNotificacionReciboLeidaUseCase,
        private MarcarTodasNotificacionesRecibosLeidasUseCase $marcarTodasNotificacionesRecibosLeidasUseCase,
    ) {}

    public function listarNotificacionesRecibos(Request $request): JsonResponse
    {
        try {
            $result = $this->listarNotificacionesRecibosUseCase->execute($request);
            $items = collect($result['items'] ?? []);

            return response()->json([
                'success' => true,
                'total' => $items->count(),
                'notificaciones' => $items,
            ]);
        } catch (\Exception $e) {
            \Log::error('[OperarioNotificacionesController] Error listarNotificacionesRecibos', [
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
            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));

            $result = $this->marcarNotificacionReciboLeidaUseCase->execute((int) $id, $tipoRecibo);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
                'recibo_id' => $result['recibo_id'] ?? null,
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNotificacionesController] Error marcarNotificacionReciboLeida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como leidas',
            ], 500);
        }
    }

    public function marcarTodasNotificacionesRecibosLeidas(Request $request): JsonResponse
    {
        try {
            $tipoRecibo = strtoupper(trim((string) $request->input('tipo_recibo', 'COSTURA')));

            $result = $this->marcarTodasNotificacionesRecibosLeidasUseCase->execute($tipoRecibo);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
                'total' => (int) ($result['total'] ?? 0),
            ], (int) ($result['status'] ?? 200));
        } catch (\Exception $e) {
            \Log::error('[OperarioNotificacionesController] Error marcarTodasNotificacionesRecibosLeidas', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar todas como leidas',
            ], 500);
        }
    }
}
