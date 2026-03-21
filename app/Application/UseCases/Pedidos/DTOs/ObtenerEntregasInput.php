<?php

namespace App\Application\UseCases\Pedidos\DTOs;

/**
 * DTO para entrada de ObtenerEntregasUseCase
 * 
 * Responsabilidad: Encapsular parámetros para obtener entregas de una orden
 * Patrón: Transfer Object
 */
class ObtenerEntregasInput
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
