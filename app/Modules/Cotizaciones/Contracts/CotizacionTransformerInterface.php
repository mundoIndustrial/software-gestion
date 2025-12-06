<?php

namespace App\Modules\Cotizaciones\Contracts;

use App\Models\Cotizacion;

/**
 * Interface CotizacionTransformerInterface
 * 
 * Contrato para transformación de datos de cotizaciones
 * Principio: Open/Closed (OCP)
 */
interface CotizacionTransformerInterface
{
    /**
     * Transformar una cotización para vista
     */
    public function transform(Cotizacion $cotizacion): array;

    /**
     * Transformar colección de cotizaciones
     */
    public function transformCollection($cotizaciones): array;
}
