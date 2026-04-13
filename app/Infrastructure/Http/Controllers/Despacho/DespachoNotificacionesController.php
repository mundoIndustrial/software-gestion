<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Application\Services\Despacho\DespachoNotificacionesApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DespachoNotificacionesController extends Controller
{
    public function __construct(
        private readonly DespachoNotificacionesApplicationService $service,
    ) {
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

            return response()->json($this->service->obtenerNotificaciones($user));
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

            $this->service->marcarTodasComoLeidas($user);

            return response()->json(['success' => true, 'message' => 'Todas marcadas como le�das']);
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

            $visto = $this->service->toggleNewsVisto($user, (int) $newsId);

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

            $visto = $this->service->togglePedidoVisto($user, (int) $pedidoId);

            return response()->json(['success' => true, 'visto' => $visto]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
