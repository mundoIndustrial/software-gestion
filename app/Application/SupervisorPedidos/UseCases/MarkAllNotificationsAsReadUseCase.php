<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationsAsReadResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class MarkAllNotificationsAsReadUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(MarkNotificationsAsReadRequest $request): MarkNotificationsAsReadResponse
    {
        try {
            $totalMarked = $this->readService->markAllNotificationsAsRead($request->getUserId());

            return new MarkNotificationsAsReadResponse(
                success: true,
                message: 'Todas las notificaciones han sido marcadas como leidas',
                notificationsMarked: $totalMarked
            );
        } catch (\Throwable $e) {
            throw new \DomainException('Error al marcar las notificaciones como leidas: ' . $e->getMessage());
        }
    }
}
