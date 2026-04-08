<?php

namespace App\Application\Pedidos\DTOs;

final class ActualizarPedidoCamposDTO
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly ?string $cliente,
        public readonly ?string $formaDePago,
        public readonly ?string $ordenCompra,
        public readonly ?string $novedades,
        public readonly ?string $justificacion,
        public readonly ?int $usuarioId,
        public readonly string $nombreUsuario,
        public readonly string $rolUsuario,
    ) {}

    public static function fromRequest(int $pedidoId, array $data, ?int $usuarioId, string $nombreUsuario, string $rolUsuario): self
    {
        return new self(
            pedidoId: $pedidoId,
            cliente: isset($data['cliente']) ? (string) $data['cliente'] : null,
            formaDePago: isset($data['forma_de_pago']) ? (string) $data['forma_de_pago'] : null,
            ordenCompra: isset($data['orden_compra']) ? (string) $data['orden_compra'] : null,
            novedades: isset($data['novedades']) ? (string) $data['novedades'] : null,
            justificacion: isset($data['justificacion']) ? (string) $data['justificacion'] : null,
            usuarioId: $usuarioId,
            nombreUsuario: $nombreUsuario,
            rolUsuario: $rolUsuario,
        );
    }
}

