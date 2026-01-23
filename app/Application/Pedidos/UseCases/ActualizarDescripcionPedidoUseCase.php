<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Actualizar Descripción del Pedido
 * 
 * Permite actualizar la descripción de un pedido que no esté finalizado
 */
class ActualizarDescripcionPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId, string $nuevaDescripcion): PedidoResponseDTO
    {
        if (empty($nuevaDescripcion)) {
            throw new \InvalidArgumentException('Descripción no puede estar vacía');
        }

        $pedido = $this->pedidoRepository->porId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado");
        }

        $pedido->actualizarDescripcion($nuevaDescripcion);
        $this->pedidoRepository->guardar($pedido);

        return new PedidoResponseDTO(
            id: $pedido->id(),
            numero: (string)$pedido->numero(),
            clienteId: $pedido->clienteId(),
            estado: $pedido->estado()->valor(),
            descripcion: $pedido->descripcion(),
            totalPrendas: $pedido->totalPrendas(),
            totalArticulos: $pedido->totalArticulos(),
            mensaje: 'Descripción actualizada exitosamente'
        );
    }
}
