<?php

namespace App\Domain\RegistrosOrdenes\Contracts;

/**
 * ImagenesOrdenService
 * 
 * Contrato para manejo de imágenes de órdenes
 * Responsabilidad: Obtener y normalizar imágenes de registro/prendas/logos
 */
interface ImagenesOrdenService
{
    /**
     * Obtener imágenes de orden (cotización + prendas)
     */
    public function obtenerImagenesOrden($numeroPedido): array;
    
    /**
     * Obtener imágenes de logo
     */
    public function obtenerImagenesLogo($numeroPedido): array;
    
    /**
     * Normalizar ruta de imagen a URL pública
     */
    public function normalizarRuta($ruta): ?string;
    
    /**
     * Obtener imágenes por prenda
     */
    public function obtenerImagenesPrenda($prendaId): array;
}
