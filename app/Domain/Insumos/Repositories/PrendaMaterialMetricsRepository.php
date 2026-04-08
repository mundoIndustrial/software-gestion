<?php

namespace App\Domain\Insumos\Repositories;

interface PrendaMaterialMetricsRepository
{
    public function obtenerAnchoMetrajePrenda(string $numeroPedido, int $prendaId): array;

    public function guardarAnchoMetrajePrenda(string $numeroPedido, int $prendaId, array $datos): array;

    public function eliminarAnchoMetrajePrenda(string $numeroPedido, int $prendaId): array;

    public function obtenerColoresPrenda(string $numeroPedido, int $prendaId): array;
}

