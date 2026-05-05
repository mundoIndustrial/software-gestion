<?php

namespace App\Domain\Pedidos\ReadModels;

final class PedidoProduccionListItem
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $numero_pedido,
        public readonly ?string $cliente,
        public readonly ?string $estado,
        public readonly ?string $area,
        public readonly ?string $novedades,
        public readonly ?string $forma_pago,
        public readonly ?string $fecha_creacion,
        public readonly ?string $fecha_estimada,
        public readonly ?int $dia_de_entrega,
        public readonly ?int $asesor_id,
    ) {}
}
