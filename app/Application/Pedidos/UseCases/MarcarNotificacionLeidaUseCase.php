<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\MarcarNotificacionLeidaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Application\Services\Asesores\NotificacionesService;

/**
 * MarcarNotificacionLeidaUseCase
 * 
 * Use Case para marcar notificaciones como leÃ­das
 * Encapsula la lógica de marcar una o todas las notificaciones
 */
class MarcarNotificacionLeidaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private NotificacionesService $notificacionesService
    ) {}

    public function ejecutar(MarcarNotificacionLeidaDTO $dto): array
    {
        if ($dto->marcarTodos) {
            $this->notificacionesService->marcarTodosLeidosPedidos();
            return [
                'success' => true,
                'message' => 'Notificaciones marcadas como leÃ­das'
            ];
        }

        $this->validarPositivo($dto->notificacionId, 'ID de notificación');
        $this->notificacionesService->marcarNotificacionLeida($dto->notificacionId);
        return [
            'success' => true,
            'message' => 'Notificación marcada como leÃ­da'
        ];
    }
}

