<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractEstadoTransicionUseCase;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * Use Case: Anular Producción Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractEstadoTransicionUseCase para eliminar duplicación
 * 
 * Antes: 45 líneas
 * Después: 10 líneas
 * Reducción: 78%
 */
class AnularProduccionPedidoUseCase extends AbstractEstadoTransicionUseCase
{
    private string $razon = 'Sin especificar';

    public function __construct(
        PedidoRepository $pedidoRepository,
        string $razon = 'Sin especificar'
    ) {
        parent::__construct($pedidoRepository);
        $this->razon = $razon;
    }

    /**
     * Ejecutar anulación con DTO que contiene id y razón
     */
    public function ejecutarConDTO(AnularProduccionPedidoDTO $dto): PedidoResponseDTO
    {
        $this->razon = $dto->razon;
        return $this->ejecutar($dto->id);
    }

    protected function aplicarTransicion($pedido): void
    {
        $pedido->anular($this->razon);
    }

    protected function obtenerMensaje(): string
    {
        return 'Producción del pedido anulada exitosamente';
    }
}

