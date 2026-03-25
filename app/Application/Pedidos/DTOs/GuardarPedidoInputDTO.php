<?php

namespace App\Application\Pedidos\DTOs;

use App\Domain\Pedidos\ValueObjects\TipoPedido;

/**
 * GuardarPedidoInputDTO
 * 
 * DTO de entrada para el UseCase GuardarPedidoUseCase
 * Abstrae completamente al UseCase de:
 * - Request HTTP (Laravel)
 * - Estructura de formularios
 * - Datos técnicos de la web
 * 
 * Solo contiene datos de NEGOCIO, validados por el Domain
 */
final class GuardarPedidoInputDTO
{
    public function __construct(
        public readonly string $clienteId,
        public readonly TipoPedido $tipoPedido,
        public readonly array $datosCliente,           // nombre, email, etc
        public readonly ?array $imagenesProcesadas,    // Array de rutas de imágenes
        public readonly array $productos,              // Productos del pedido
    ) {}
}
