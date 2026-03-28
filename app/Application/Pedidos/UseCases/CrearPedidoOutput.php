<?php

namespace App\Application\Pedidos\UseCases;

/**
 * DTO de salida para CrearPedidoCompleteUseCase
 * 
 * Encapsula el resultado de crear un pedido completo
 */
class CrearPedidoOutput
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly int $pedidoId,
        public readonly string $numeroPedido,
        public readonly int $clienteId,
        public readonly array $detalles = [],
    ) {}

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'pedido_id' => $this->pedidoId,
            'numero_pedido' => $this->numeroPedido,
            'cliente_id' => $this->clienteId,
            'detalles' => $this->detalles,
        ];
    }

    /**
     * Factory para casos de éxito
     */
    public static function success(
        int $pedidoId,
        string $numeroPedido,
        int $clienteId,
        array $detalles = []
    ): self {
        return new self(
            success: true,
            message: 'Pedido creado exitosamente',
            pedidoId: $pedidoId,
            numeroPedido: $numeroPedido,
            clienteId: $clienteId,
            detalles: $detalles,
        );
    }

    /**
     * Factory para casos de error
     */
    public static function failure(string $message): self
    {
        return new self(
            success: false,
            message: $message,
            pedidoId: 0,
            numeroPedido: '',
            clienteId: 0,
        );
    }
}

