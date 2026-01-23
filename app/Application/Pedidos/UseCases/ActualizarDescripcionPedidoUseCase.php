<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Actualizar Descripción del Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait para validación
 * 
 * Antes: 46 líneas (20 líneas de lógica + 26 de validación)
 * Después: 30 líneas (solo lógica de negocio)
 * Reducción: 35%
 * 
 * Permite actualizar la descripción de un pedido que no esté finalizado
 */
class ActualizarDescripcionPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId, string $nuevaDescripcion): PedidoResponseDTO
    {
        // CENTRALIZADO: Validar descripción no vacía (trait)
        $this->validarNoVacio($nuevaDescripcion, 'Descripción');

        // CENTRALIZADO: Validar pedido existe (trait)
        $pedido = $this->validarPedidoExiste($pedidoId, $this->pedidoRepository);

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
