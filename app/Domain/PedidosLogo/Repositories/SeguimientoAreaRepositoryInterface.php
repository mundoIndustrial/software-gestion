<?php

namespace App\Domain\PedidosLogo\Repositories;

interface SeguimientoAreaRepositoryInterface
{
    public function obtenerPorProceso(int $procesoPrendaDetalleId, ?int $pedidoParcialId = null): ?array;

    public function upsertSeguimiento(int $procesoPrendaDetalleId, int $prendaPedidoId, string $area, ?string $novedades, array $fechasAreas, string $timestamp, ?int $pedidoParcialId = null): void;
}
