<?php

namespace App\Application\Pedidos\DTOs;

/**
 * GuardarPedidoOutputDTO
 * 
 * DTO de salida del UseCase
 * Informa al controller sobre el resultado sin exponer detalles internos
 */
final class GuardarPedidoOutputDTO
{
    public function __construct(
        public readonly string $tipo,          // 'logo' | 'produccion'
        public readonly string|int $id,        // ID del pedido creado
        public readonly string $mensaje,
        public readonly array $metadata = [],  // Metadata adicional si es necesaria
    ) {}
}
