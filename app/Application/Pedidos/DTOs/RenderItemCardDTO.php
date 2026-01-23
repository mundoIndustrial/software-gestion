<?php

namespace App\Application\Pedidos\DTOs;

final class RenderItemCardDTO
{
    public function __construct(
        public readonly array $item,
        public readonly int $index,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            item: $data['item'] ?? throw new \InvalidArgumentException('item requerido'),
            index: $data['index'] ?? throw new \InvalidArgumentException('index requerido'),
        );
    }
}
