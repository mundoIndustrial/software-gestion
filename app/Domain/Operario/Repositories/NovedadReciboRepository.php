<?php

namespace App\Domain\Operario\Repositories;

use Illuminate\Support\Collection;

interface NovedadReciboRepository
{
    public function crear(array $data): void;

    public function obtenerPorId(int $id): ?object;

    /**
     * @return Collection<int, object>
     */
    public function obtenerPorPrenda(int $prendaPedidoId): Collection;

    public function actualizar(int $id, array $data): void;

    public function eliminar(int $id): void;

    public function marcarPedidoPendientePorNumero(int $numeroPedido, \DateTimeInterface $fecha): void;
}

