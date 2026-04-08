<?php

namespace App\Domain\Cotizacion\Repositories;

interface CotizacionDetalleRepositoryInterface
{
    /**
     * Obtener cotizacion con relaciones completas requeridas por el modal de detalle.
     */
    public function obtenerCotizacionParaModal(int $cotizacionId): ?object;

    /**
     * Obtener datos base de una cotizacion para edicion (owner, tipo y metadata).
     */
    public function obtenerResumenCotizacion(int $cotizacionId): ?array;

    /**
     * Obtener datos de cotizacion con EPP e items
     */
    public function obtenerCotizacionConEpp(int $cotizacionId): array;

    /**
     * Obtener datos de cotizacion con prendas e items
     */
    public function obtenerCotizacionConPrendas(int $cotizacionId): array;
}
