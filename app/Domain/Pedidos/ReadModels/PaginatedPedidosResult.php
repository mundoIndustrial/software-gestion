<?php

namespace App\Domain\Pedidos\ReadModels;

final class PaginatedPedidosResult
{
    /**
     * @param PedidoProduccionListItem[] $items
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly string $path,
        public readonly array $query = [],
    ) {}
}
