<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ApproveOrderDetailedRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderDetailedResponse;
use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\ValueObjects\QuotationType;

class ApproveOrderDetailedUseCase
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    /**
     * Aprobar orden completa (cambiar estado según tipo de cotización)
     */
    public function execute(ApproveOrderDetailedRequest $request): ApproveOrderDetailedResponse
    {
        try {
            $order = $this->orderRepository->findByIdWithRelations($request->getOrderId());
            
            if (!$order) {
                throw new \DomainException('Orden no encontrada');
            }

            // Verificar que esté en estado PENDIENTE_SUPERVISOR
            if ($order['estado'] !== 'PENDIENTE_SUPERVISOR') {
                throw new \DomainException(
                    'Solo se pueden aprobar órdenes en estado "PENDIENTE_SUPERVISOR"'
                );
            }

            // Determinar si es cotización reflectiva
            $quotationType = $this->getQuotationType($order);
            $isReflective = QuotationType::isReflective($quotationType);

            if ($isReflective) {
                // Pedido reflectivo: va directamente a Costura en estado "En Ejecución"
                $resultado = $this->orderRepository->updateMultiple(
                    $request->getOrderId(),
                    [
                        'estado' => 'En Ejecución',
                        'area' => 'Costura',
                        'aprobado_por_supervisor_en' => now()
                    ]
                );

                return new ApproveOrderDetailedResponse(
                    success: true,
                    message: 'Pedido reflectivo aprobado. Enviado directamente a Costura.',
                    orderId: $request->getOrderId(),
                    quotationType: $quotationType,
                    newStatus: 'En Ejecución',
                    newArea: 'Costura',
                    order: $resultado
                );
            } else {
                // Pedido normal: va a Insumos en estado PENDIENTE_INSUMOS
                $resultado = $this->orderRepository->updateMultiple(
                    $request->getOrderId(),
                    [
                        'estado' => 'PENDIENTE_INSUMOS',
                        'area' => 'Insumos',
                        'aprobado_por_supervisor_en' => now()
                    ]
                );

                return new ApproveOrderDetailedResponse(
                    success: true,
                    message: 'Pedido aprobado. Disponible para el módulo de insumos.',
                    orderId: $request->getOrderId(),
                    quotationType: $quotationType,
                    newStatus: 'PENDIENTE_INSUMOS',
                    newArea: 'Insumos',
                    order: $resultado
                );
            }

        } catch (\Exception $e) {
            throw new \DomainException('Error al aprobar orden: ' . $e->getMessage());
        }
    }

    /**
     * Obtener tipo de cotización de la orden
     */
    private function getQuotationType(array $order): ?string
    {
        // Si la orden tiene relación con cotización
        if (isset($order['cotizacion']) && is_array($order['cotizacion'])) {
            if (isset($order['cotizacion']['tipoCotizacion']) && is_array($order['cotizacion']['tipoCotizacion'])) {
                return $order['cotizacion']['tipoCotizacion']['nombre'] ?? null;
            }
        }
        
        return null;
    }
}
