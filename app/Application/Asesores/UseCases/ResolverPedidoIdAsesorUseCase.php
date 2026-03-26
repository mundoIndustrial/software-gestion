<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use DomainException;

final class ResolverPedidoIdAsesorUseCase
{
    public function __construct(
        private PedidoProduccionReadRepository $pedidoReadRepository
    ) {}

    public function ejecutar(string|int $identificador, int $asesorId): int
    {
        if (is_numeric($identificador)) {
            $porId = $this->pedidoReadRepository->obtenerPorIdYAsesor((int) $identificador, $asesorId);
            if ($porId !== null) {
                return $porId->pedidoId;
            }
        }

        $porNumero = $this->pedidoReadRepository->findByNumeroPedido((string) $identificador);
        if ($porNumero === null) {
            throw new DomainException('Pedido no encontrado.');
        }

        if ($porNumero->asesorId !== null && $porNumero->asesorId !== $asesorId) {
            throw new DomainException('No tienes permiso para operar este pedido.');
        }

        return $porNumero->pedidoId;
    }
}
