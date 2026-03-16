<?php

namespace App\Domain\RegistrosOrdenes\Contracts;

use App\Models\PedidoProduccion;

/**
 * DescripcionOrdenService
 * 
 * Contrato para construcción de descripciones de prendas
 * Responsabilidad: Generar descripciones formateadas según tipo de cotización
 */
interface DescripcionOrdenService
{
    /**
     * Construir descripción con tallas
     */
    public function construirConTallas(PedidoProduccion $orden): string;
    
    /**
     * Generar descripción dinámicamente desde prendas
     */
    public function generarDesdePrendas(PedidoProduccion $orden): string;
    
    /**
     * Procesar descripción reflectivo
     */
    public function procesarReflectivo(PedidoProduccion $orden): string;
    
    /**
     * Procesar descripción normal
     */
    public function procesarNormal(string $descripcionBase): string;
}
