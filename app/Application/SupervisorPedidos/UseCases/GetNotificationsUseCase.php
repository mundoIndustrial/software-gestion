<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetNotificationsResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;

class GetNotificationsUseCase
{
    public function __construct(
        private \Illuminate\Auth\AuthManager $auth,
        private readonly PedidoProduccionReadService $readService
    ) {}

    /**
     * Obtener notificaciones del supervisor (ordenes pendientes + novedades)
     */
    public function execute(): GetNotificationsResponse
    {
        try {
            $user = $this->auth->user();

            if (!$user) {
                return new GetNotificationsResponse(
                    success: false,
                    notifications: collect([]),
                    news: collect([]),
                    totalPending: 0,
                    totalOrdersNotViewed: 0,
                    totalNews: 0,
                    totalNewsNotViewed: 0,
                    totalGeneral: 0
                );
            }

            $data = $this->readService->getSupervisorNotificationsData((int) $user->id);

            return new GetNotificationsResponse(
                success: true,
                notifications: $data['notifications'],
                news: $data['news'],
                totalPending: $data['totalPending'],
                totalOrdersNotViewed: $data['totalOrdersNotViewed'],
                totalNews: $data['totalNews'],
                totalNewsNotViewed: $data['totalNewsNotViewed'],
                totalGeneral: $data['totalGeneral']
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[GetNotificationsUseCase] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return new GetNotificationsResponse(
                success: false,
                notifications: collect([]),
                news: collect([]),
                totalPending: 0,
                totalOrdersNotViewed: 0,
                totalNews: 0,
                totalNewsNotViewed: 0,
                totalGeneral: 0
            );
        }
    }
}
