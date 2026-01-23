<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Agregado\PedidoAggregate;

/**
 * Use Case: Crear Pedido
 * 
 * Orquesta:
 * 1. Validar entrada (DTO)
 * 2. Crear agregado
 * 3. Persistir
 * 4. Disparar eventos
 * 5. Retornar respuesta
 */
class CrearPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(CrearPedidoDTO $dto): PedidoResponseDTO
    {
        try {
            $pedido = PedidoAggregate::crear(
                clienteId: $dto->clienteId,
                descripcion: $dto->descripcion,
                prendasData: $dto->prendas,
                observaciones: $dto->observaciones
            );

            $this->pedidoRepository->guardar($pedido);

            return new PedidoResponseDTO(
                id: $pedido->id(),
                numero: (string)$pedido->numero(),
                clienteId: $pedido->clienteId(),
                estado: $pedido->estado()->valor(),
                descripcion: $pedido->descripcion(),
                totalPrendas: $pedido->totalPrendas(),
                totalArticulos: $pedido->totalArticulos(),
                mensaje: 'Pedido creado exitosamente'
            );

        } catch (\Exception $e) {
            throw new \DomainException('Error al crear pedido: ' . $e->getMessage());
        }
    }
}

