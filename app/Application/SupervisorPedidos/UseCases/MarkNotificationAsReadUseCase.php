<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\MarkNotificationAsReadRequest;
use App\Application\SupervisorPedidos\DTOs\MarkNotificationAsReadResponse;

class MarkNotificationAsReadUseCase
{
    public function execute(MarkNotificationAsReadRequest $request): MarkNotificationAsReadResponse
    {
        try {
            // Obtener el usuario desde el contexto de autenticación
            $user = \Illuminate\Support\Facades\Auth::user();

            if (!$user) {
                throw new \DomainException('Usuario no autenticado');
            }

            // Buscar la notificación
            $notification = $user->notifications()->find($request->getNotificationId());

            if (!$notification) {
                throw new \DomainException('Notificación no encontrada');
            }

            // Marcar como leída
            $notification->markAsRead();

            return new MarkNotificationAsReadResponse(
                success: true,
                message: 'Notificación marcada como leída'
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al marcar notificación: ' . $e->getMessage());
        }
    }
}
