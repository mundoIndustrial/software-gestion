<?php

namespace App\Application\Pedidos\DTOs;

final class AgregarPrendaAlPedidoDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombrePrenda,
        public readonly int $cantidad,
        public readonly string $tipoManga,
        public readonly string $tipoBroche,
        public readonly int $colorId,
        public readonly int $telaId,
        public readonly ?string $descripcion = null,
        public readonly ?string $origen = null,
        public readonly ?array $tallas = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombrePrenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            cantidad: $data['cantidad'] ?? 1,
            tipoManga: $data['tipo_manga'] ?? throw new \InvalidArgumentException('tipo_manga requerido'),
            tipoBroche: $data['tipo_broche'] ?? throw new \InvalidArgumentException('tipo_broche requerido'),
            colorId: $data['color_id'] ?? throw new \InvalidArgumentException('color_id requerido'),
            telaId: $data['tela_id'] ?? throw new \InvalidArgumentException('tela_id requerido'),
            descripcion: $data['descripcion'] ?? null,
            origen: $data['origen'] ?? null,
            tallas: isset($data['tallas']) ? json_decode($data['tallas'], true) : null,
        );
    }
}
