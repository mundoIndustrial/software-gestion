<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Confirmar Pedido
 */
class ConfirmarPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        $pedido = $this->pedidoRepository->porId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado");
        }

        $pedido->confirmar();
        $this->pedidoRepository->guardar($pedido);

        return new PedidoResponseDTO(
            id: $pedido->id(),
            numero: (string)$pedido->numero(),
            clienteId: $pedido->clienteId(),
            estado: $pedido->estado()->valor(),
            descripcion: $pedido->descripcion(),
            totalPrendas: $pedido->totalPrendas(),
            totalArticulos: $pedido->totalArticulos(),
            mensaje: 'Pedido confirmado exitosamente'
        );
    }
}
