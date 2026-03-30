<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Services\RegistroOrdenDeletionService;

/**
 * UseCase: Eliminar una orden
 * Responsabilidades:
 * - Validar que la orden existe
 * - Delegar eliminación al servicio
 * - Disparar eventos broadcast
 */
class DeleteOrderUseCase
{
    public function __construct(
        private RegistroOrdenDeletionService $deletionService,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $pedido): array
    {
        $this->deletionService->deleteOrder($pedido);
        
        // Broadcast evento
        $this->deletionService->broadcastOrderDeleted($pedido);

        return [
            'success' => true,
            'message' => 'Orden eliminada correctamente',
            'pedido' => $pedido
        ];
    }
}

