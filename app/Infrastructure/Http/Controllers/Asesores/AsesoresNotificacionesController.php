<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Pedidos\DTOs\ObtenerNotificacionesDTO;
use App\Application\Pedidos\UseCases\MarcarNotificacionLeidaUseCase;
use App\Application\Pedidos\UseCases\ObtenerNotificacionesUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class AsesoresNotificacionesController extends Controller
{
    public function __construct(
        private readonly ObtenerNotificacionesUseCase $obtenerNotificacionesUseCase,
        private readonly MarcarNotificacionLeidaUseCase $marcarNotificacionLeidaUseCase
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    public function getNotificaciones(): JsonResponse
    {
        $dto = ObtenerNotificacionesDTO::crear();
        $notificaciones = $this->obtenerNotificacionesUseCase->ejecutar($dto);

        return $this->json($notificaciones);
    }

    public function getNotifications(): JsonResponse
    {
        return $this->getNotificaciones();
    }

    public function markAllAsRead(): JsonResponse
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::marcarTodos();
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);

            return $this->json($resultado);
        } catch (\Throwable $e) {
            Log::error('Error al marcar notificaciones', ['error' => $e->getMessage()]);

            return $this->failure('Error al marcar notificaciones', 500);
        }
    }

    public function markNotificationAsRead(int|string $notificationId): JsonResponse
    {
        try {
            $dto = MarcarNotificacionLeidaDTO::fromRequest($notificationId);
            $resultado = $this->marcarNotificacionLeidaUseCase->ejecutar($dto);

            return $this->json($resultado);
        } catch (\Throwable $e) {
            Log::error('Error al marcar notificacion', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al marcar notificacion', 500);
        }
    }
}
