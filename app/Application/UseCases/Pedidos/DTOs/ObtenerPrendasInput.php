<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para entrada de ObtenerPrendasUseCase
 * 
 * Responsabilidad: Encapsular parámetros para obtener prendas de una orden
 * Patrón: Transfer Object
 */
class ObtenerPrendasInput
{
    public function __construct(
        public int $numero_pedido,
    ) {}

    /**
     * Factory: Crear desde número de pedido
     */
    public static function fromNumeroPedido(int $numeroPedido): self
    {
        return new self(
            numero_pedido: $numeroPedido,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
        ];
    }
}
