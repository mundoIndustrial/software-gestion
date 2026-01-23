<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Listar Pedidos por Cliente
 * 
 * Query Side - CQRS básico
 * Obtiene todos los pedidos de un cliente
 */
class ListarPedidosPorClienteUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $clienteId): array
    {
        $pedidos = $this->pedidoRepository->porClienteId($clienteId);

        return array_map(
            fn($pedido) => new PedidoResponseDTO(
                id: $pedido->id(),
                numero: (string)$pedido->numero(),
                clienteId: $pedido->clienteId(),
                estado: $pedido->estado()->valor(),
                descripcion: $pedido->descripcion(),
                totalPrendas: $pedido->totalPrendas(),
                totalArticulos: $pedido->totalArticulos(),
                mensaje: 'Pedidos obtenidos exitosamente'
            ),
            $pedidos
        );
    }
}
