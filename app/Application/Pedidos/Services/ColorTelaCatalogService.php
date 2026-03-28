<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\ColorTelaCatalogServiceContract;

/**
 * Resuelve colores y telas de catalogo para flujos de pedidos.
 */
class ColorTelaCatalogService
{
    public function __construct(private readonly ColorTelaCatalogServiceContract $service)
    {
    }

    public function obtenerOCrearColor(?string $nombreColor): ?int
    {
        return $this->service->obtenerOCrearColor($nombreColor);
    }

    public function obtenerOCrearTela(?string $nombreTela, ?string $referencia = null): ?int
    {
        return $this->service->obtenerOCrearTela($nombreTela, $referencia);
    }

    public function procesarTela(array $telaData): array
    {
        return $this->service->procesarTela($telaData);
    }

    public function procesarTelas(array $telas): array
    {
        return $this->service->procesarTelas($telas);
    }
}
