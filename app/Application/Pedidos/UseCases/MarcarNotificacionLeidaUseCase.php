<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Services\Asesores\NotificacionesService;

/**
 * MarcarNotificacionLeidaUseCase
 * 
 * Use Case para marcar notificaciones como leídas
 * Encapsula la lógica de marcar una o todas las notificaciones
 */
class MarcarNotificacionLeidaUseCase
{
    public function __construct(
        private NotificacionesService $notificacionesService
    ) {}

    public function ejecutar(MarcarNotificacionLeidaDTO $dto): array
    {
        if ($dto->marcarTodos) {
            $this->notificacionesService->marcarTodosLeidosPedidos();
            return [
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ];
        }

        if ($dto->notificacionId) {
            $this->notificacionesService->marcarNotificacionLeida($dto->notificacionId);
            return [
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ];
        }

        return [
            'success' => false,
            'message' => 'Debe especificar una notificación o marcar todas'
        ];
    }
}
