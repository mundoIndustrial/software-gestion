<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Pedidos\DTOs\ObtenerNotificacionesDTO;
use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class AsesoresNotificacionesController extends Controller
{
    public function __construct(
        private readonly ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase,
        private readonly MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase
    ) {
    }

    public function getNotificaciones()
    {
        $dto = ObtenerNotificacionesDTO::crear();
        $notificaciones = $this->obtenerNotificacionesUseCase->ejecutar($dto);

        return response()->json($notificaciones);
    }

    public function getNotifications()
    {
        return $this->getNotificaciones();
    }

    public function markAllAsRead()
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::marcarTodos();
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
        } catch (\Throwable $e) {
            Log::error('Error al marcar notificaciones', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error al marcar notificaciones',
            ], 500);
        }
    }

    public function markNotificationAsRead($notificationId)
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::fromRequest($notificationId);
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);
            return response()->json($resultado);
        } catch (\Throwable $e) {
            Log::error('Error al marcar notificación', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'Error al marcar notificación',
            ], 500);
        }
    }
}

