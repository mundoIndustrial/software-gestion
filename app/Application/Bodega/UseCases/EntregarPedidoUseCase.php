<?php

namespace App\Application\Bodega\UseCases;

use App\Domain\Bodega\Entities\Pedido;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\Events\DomainEventDispatcher;
use App\Domain\Bodega\Events\PedidoEntregado;

/**
 * Use Case para entregar un pedido
 * Orquesta la operación de negocio de entrega de pedidos
 */
class EntregarPedidoUseCase
{
    private PedidoRepositoryInterface $pedidoRepository;

    public function __construct(PedidoRepositoryInterface $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Ejecutar la entrega de un pedido
     */
    public function execute(int $pedidoId): array
    {
        try {
            // 1. Obtener el pedido
            $pedido = $this->pedidoRepository->findById($pedidoId);
            
            if (!$pedido) {
                throw new \InvalidArgumentException("Pedido no encontrado: {$pedidoId}");
            }

            // 2. Validar que puede ser entregado
            if (!$pedido->puedeSerEntregado()) {
                throw new \LogicException("El pedido {$pedido->getNumeroPedido()} no puede ser entregado en su estado actual");
            }

            // 3. Ejecutar la lógica de negocio
            $estadoAnterior = $pedido->getEstado();
            $pedido->entregar();

            // 4. Persistir los cambios
            $this->pedidoRepository->save($pedido);

            // 5. Procesar eventos de dominio
            DomainEventDispatcher::processQueue();

            return [
                'success' => true,
                'message' => "Pedido {$pedido->getNumeroPedido()} entregado correctamente",
                'pedido' => $pedido->toArray(),
                'estado_anterior' => $estadoAnterior->getValor(),
                'estado_nuevo' => $pedido->getEstado()->getValor(),
                'fecha_entrega' => $pedido->getFechaEntregaReal()->format('d/m/Y H:i:s')
            ];

        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'validation'
            ];

        } catch (\LogicException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_type' => 'business_logic'
            ];

        } catch (\Exception $e) {
            \Log::error("Error en EntregarPedidoUseCase: " . $e->getMessage(), [
                'pedido_id' => $pedidoId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error interno al entregar el pedido',
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Validar si un pedido puede ser entregado (sin ejecutar la operación)
     */
    public function puedeSerEntregado(int $pedidoId): array
    {
        $pedido = $this->pedidoRepository->findById($pedidoId);
        
        if (!$pedido) {
            return [
                'puede' => false,
                'motivo' => 'Pedido no encontrado'
            ];
        }

        if (!$pedido->puedeSerEntregado()) {
            return [
                'puede' => false,
                'motivo' => 'El pedido no puede ser entregado en su estado actual',
                'estado_actual' => $pedido->getEstado()->getValor()
            ];
        }

        return [
            'puede' => true,
            'motivo' => 'El pedido puede ser entregado',
            'estado_actual' => $pedido->getEstado()->getValor()
        ];
    }
}
