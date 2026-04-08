<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\EliminarPrendaPedidoRepository;
use App\Domain\Pedidos\UseCases\EliminarPrendaPedidoUseCaseContract;

/**
 * Use Case para eliminar una prenda de un pedido.
 *
 * Responsabilidad: orquestar la operación de dominio,
 * delegando la persistencia al repositorio de infraestructura.
 */
final class EliminarPrendaPedidoUseCase implements EliminarPrendaPedidoUseCaseContract
{
    public function __construct(
        private EliminarPrendaPedidoRepository $eliminarPrendaPedidoRepository,
    ) {
    }

    public function ejecutar(int $pedidoId, int $prendaId, string $motivo): array
    {
        return $this->eliminarPrendaPedidoRepository->eliminarDePedido($pedidoId, $prendaId, $motivo);
    }
}
