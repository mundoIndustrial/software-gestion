<?php

namespace App\Application\RegistrosOrdenes\Contracts;

/**
 * BusquedaOrdenService
 * 
 * Contrato para búsquedas de órdenes
 * Responsabilidad: Aplicar filters de búsqueda a queries
 */
interface BusquedaOrdenService
{
    /**
     * Aplicar filtro de búsqueda
     */
    public function aplicar(&$query, $termino): void;
}
