<?php

namespace App\Domain\Talleres\Repositories;

use Illuminate\Support\Collection;

interface OrdenTallerRepositoryInterface
{
    /**
     * Obtiene todas las órdenes asignadas a talleres
     * 
     * @param string|null $search
     * @return Collection
     */
    public function obtenerAsignadas(?string $search = null): Collection;

    /**
     * Obtiene órdenes normales (no parciales)
     * 
     * @param string|null $search
     * @return Collection
     */
    public function obtenerNormales(?string $search = null): Collection;

    /**
     * Obtiene órdenes parciales
     * 
     * @param string|null $search
     * @return Collection
     */
    public function obtenerParciales(?string $search = null): Collection;

    /**
     * Obtiene entregas por talla para un recibo
     * 
     * @param int $reciboId
     * @param bool $esParcial
     * @return array
     */
    public function obtenerEntregasPorTalla(int $reciboId, bool $esParcial = false): array;

    /**
     * Obtiene cantidades totales por recibo
     * 
     * @param array $reciboIds
     * @param array $prendaIds
     * @param bool $esParcial
     * @return array
     */
    public function obtenerCantidadesTotales(array $reciboIds, array $prendaIds = [], bool $esParcial = false): array;
}
