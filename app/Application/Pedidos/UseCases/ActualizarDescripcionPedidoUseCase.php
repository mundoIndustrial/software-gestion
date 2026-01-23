<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Actualizar DescripciÃ³n del Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait para validaciÃ³n
 * 
 * Antes: 46 lÃ­neas (20 lÃ­neas de lÃ³gica + 26 de validaciÃ³n)
 * DespuÃ©s: 30 lÃ­neas (solo lÃ³gica de negocio)
 * ReducciÃ³n: 35%
 * 
 * Permite actualizar la descripciÃ³n de un pedido que no estÃ© finalizado
 */
class ActualizarDescripcionPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId, string $nuevaDescripcion): PedidoResponseDTO
    {
        // CENTRALIZADO: Validar descripciÃ³n no vacÃ­a (trait)
        $this->validarNoVacio($nuevaDescripcion, 'DescripciÃ³n');

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
            mensaje: 'DescripciÃ³n actualizada exitosamente'
        );
    }
}

