<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerProduccionPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerProduccionPedidoDTO $dto)
    {
        // Obtener el pedido del repositorio
        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);

        if (!$pedido) {
            throw new \Exception("Pedido con ID {$dto->pedidoId} no encontrado");
        }

        // Retornar el pedido con sus prendas cargadas
        return $pedido;
    }
}
