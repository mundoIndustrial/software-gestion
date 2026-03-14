<?php

namespace App\Application\Services\Pedidos\Contracts;

use App\Models\Cotizacion;

/**
 * ObtenerItemsServiceInterface
 * 
 * Contrato para servicios que obtienen items de cotizaciones
 * Permite reemplazar implementaciones sin modificar el controller
 */
interface ObtenerItemsServiceInterface
{
    /**
     * Obtener items de una cotización
     * 
     * @param Cotizacion $cotizacion
     * @return array ['items' => Collection, 'count_items' => int, 'count_epps' => int]
     */
    public function ejecutar(Cotizacion $cotizacion): array;
}
