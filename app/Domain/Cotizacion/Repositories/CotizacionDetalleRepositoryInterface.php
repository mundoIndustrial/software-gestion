<?php

namespace App\Domain\Cotizacion\Repositories;

interface CotizacionDetalleRepositoryInterface
{
    /**
     * Obtener datos de cotización con EPP e items
     */
    public function obtenerCotizacionConEpp(int $cotizacionId): array;

    /**
     * Obtener datos de cotización con prendas e items
     */
    public function obtenerCotizacionConPrendas(int $cotizacionId): array;
}
